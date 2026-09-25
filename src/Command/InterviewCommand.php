<?php

namespace App\Command;

use App\Atheneum\AuthorizationRecords;
use App\Atheneum\InterviewRecords;
use App\Atheneum\ProposalRecords;
use App\Curia\AuthorizationService;
use App\Curia\InterviewService;
use App\Curia\ProposalService;
use App\Entity\Authorization;
use App\Entity\Interview;
use App\Entity\Proposal;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'imperium:interview', description: 'Browse, start, or resume a local interview with Seneschal.')]
class InterviewCommand extends Command
{
    public function __construct(
        private InterviewRecords $records,
        private InterviewService $interviews,
        private ProposalRecords $proposalRecords,
        private ProposalService $proposals,
        private AuthorizationRecords $authorizationRecords,
        private AuthorizationService $authorizations,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('id', InputArgument::OPTIONAL, 'Interview UUID to resume')
            ->addOption('list', null, InputOption::VALUE_NONE, 'Show the interview list (the default view)')
            ->addOption('new', null, InputOption::VALUE_NONE, 'Start a new interview directly');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $id = $input->getArgument('id');
            if ($input->getOption('new') && (null !== $id || $input->getOption('list'))) {
                throw new \DomainException('Use --new on its own, without an interview ID or --list.');
            }
            if ($input->getOption('list') || (null === $id && !$input->getOption('new'))) {
                $selection = $this->selectInterview($io, $input->isInteractive());
                if (null === $selection) {
                    return Command::SUCCESS;
                }
                $id = 'new' === $selection ? null : $selection;
            }
            if (!$input->isInteractive()) {
                $io->error('The interview requires an interactive terminal. --list works non-interactively.');

                return Command::INVALID;
            }
            $interview = null === $id ? $this->records->create() : $this->records->get($id);
            $id = $interview->getId();
            $io->title('Imperium — Seneschal');
            $io->text('Interview: '.$interview->getShortId().' — '.$this->display($interview->getAlias()));
            $io->text('Resume: php bin/console imperium:interview '.$id);
            $io->note('Messages are stored in PostgreSQL and sent to DeepSeek when requesting a reply.');
            $io->text('/quit saves and exits; /retry retries a pending reply; /approve or /decline answers a drafting request.');
            foreach ($interview->getExchanges() as $entry) {
                $io->section('assistant' === $entry['role'] ? 'Seneschal' : 'Operator');
                $io->writeln($this->display($entry['text']));
            }
            if ([] === $interview->getExchanges()) {
                $io->text('What would you like to accomplish?');
            }
            if ($interview->hasPendingReply()) {
                $io->warning('A saved message is awaiting a reply. Use /retry when ready.');
            }

            while (Interview::DRAFT_AUTHORIZED !== $interview->getStatus()) {
                if (Interview::AWAITING_PERMISSION === $interview->getStatus()) {
                    $io->text('Review the summary above. Type a correction, /approve to permit drafting, or /decline.');
                }
                $text = trim((string) $io->ask('You', '/quit'));
                if ('/quit' === $text) {
                    $io->success('Saved. Resume using the interview ID above.');

                    return Command::SUCCESS;
                }
                try {
                    if ('/retry' === $text || !str_starts_with($text, '/')) {
                        $io->text('Waiting for Seneschal...');
                    }
                    $interview = match ($text) {
                        '/approve' => $this->interviews->decideDraftPermission($id, true, $interview->getVersion()),
                        '/decline' => $this->interviews->decideDraftPermission($id, false, $interview->getVersion()),
                        '/retry' => $this->interviews->retry($id),
                        default => str_starts_with($text, '/')
                            ? throw new \DomainException('Unknown command. Use /quit, /retry, /approve, or /decline.')
                            : $this->interviews->submit($id, $text),
                    };
                    $io->text('Mission: '.$this->display($interview->getAlias()));
                    $entries = $interview->getExchanges();
                    $entry = end($entries);
                    $io->section(Interview::AWAITING_PERMISSION === $interview->getStatus() ? 'Seneschal — review before drafting' : ('assistant' === $entry['role'] ? 'Seneschal' : 'Recorded'));
                    $io->writeln($this->display($entry['text']));
                    if ('/decline' === $text) {
                        $io->text('Tell Seneschal what needs to change, or use /quit to return later.');
                    }
                } catch (\DomainException $exception) {
                    $io->warning($exception->getMessage());
                    $interview = $this->records->get($id);
                    $entries = $interview->getExchanges();
                    if ([] !== $entries) {
                        $io->section('Latest saved exchange');
                        $io->writeln($this->display(end($entries)['text']));
                    }
                }
            }
            $io->success('Permission to draft recorded. No proposal approval or execution authority has been granted.');

            return $this->proposalStage($io, $interview);
        } catch (\DomainException $exception) {
            $io->error($exception->getMessage());

            return Command::INVALID;
        } catch (\Throwable) {
            $io->error('Interview storage or configuration is unavailable. Check the PostgreSQL connection and run migrations. Resume using --list once resolved.');

            return Command::FAILURE;
        }
    }

    private function proposalStage(SymfonyStyle $io, Interview $interview): int
    {
        while (true) {
            $proposal = $this->proposalRecords->latest($interview);
            if (null === $proposal) {
                $action = $io->choice('Proposal', [1 => 'Generate proposal', 0 => 'Back'], 0);
                if ('Back' === $action) {
                    $io->success('Saved. Reopen this interview to generate its proposal later.');

                    return Command::SUCCESS;
                }

                $io->text('Waiting for Seneschal to draft...');
                try {
                    $this->proposals->generate($interview->getId());
                } catch (\DomainException $exception) {
                    $io->warning($exception->getMessage());
                    $interview = $this->records->get($interview->getId());
                }

                continue;
            }

            $this->displayProposal($io, $proposal);
            if (Proposal::APPROVED === $proposal->getStatus()) {
                $io->success('Proposal v'.$proposal->getVersion().' is approved.');
                $io->note('Proposal approval does not authorize resources, external effects, or execution.');

                return $this->authorizationStage($io, $interview, $proposal);
            }

            $io->note('Draft review only: no resource authority or execution authority has been granted.');
            $action = $io->choice('Review proposal v'.$proposal->getVersion(), [
                1 => 'Approve proposal',
                2 => 'Request revision',
                0 => 'Back',
            ], 0);
            if ('Back' === $action) {
                $io->success('Saved. Reopen this interview to continue proposal review.');

                return Command::SUCCESS;
            }

            try {
                if ('Approve proposal' === $action) {
                    $proposal = $this->proposals->approve($interview->getId(), $proposal->getVersion());
                    $io->success('Proposal v'.$proposal->getVersion().' approved.');
                    $io->note('This records plan approval only. Resource authority and execution authority remain ungranted.');

                    return $this->authorizationStage($io, $interview, $proposal);
                }

                $guidance = trim((string) $io->ask('Revision request', ''));
                $io->text('Waiting for Seneschal to revise...');
                $this->proposals->revise($interview->getId(), $proposal->getVersion(), $guidance);
            } catch (\DomainException $exception) {
                $io->warning($exception->getMessage());
                $interview = $this->records->get($interview->getId());
            }
        }
    }

    private function authorizationStage(SymfonyStyle $io, Interview $interview, Proposal $proposal): int
    {
        while (true) {
            $authorization = $this->authorizationRecords->forProposal($proposal);
            if (null === $authorization) {
                $action = $io->choice('Authorization', [1 => 'Prepare authorization request', 0 => 'Back'], 0);
                if ('Back' === $action) {
                    $io->success('Proposal remains approved. No resource/effect authorization request was created.');

                    return Command::SUCCESS;
                }

                $io->section('Authorization request');
                $io->text('Resources are copied from the approved proposal. Declare any intended external effects before deciding.');
                $effects = (string) $io->ask('Requested external effects; separate multiple effects with semicolons (blank for none)', '');
                try {
                    $authorization = $this->authorizations->request($interview->getId(), $effects);
                } catch (\DomainException $exception) {
                    $io->warning($exception->getMessage());

                    continue;
                }
            }

            $this->displayAuthorization($io, $authorization);
            if (Authorization::PENDING !== $authorization->getStatus()) {
                $io->success('Authorization is '.$authorization->getStatus().'.');
                $io->note('Execution remains unavailable in this campaign. This record only establishes the permitted or refused scope.');

                return Command::SUCCESS;
            }

            $action = $io->choice('Authorization decision', [
                1 => 'Authorize requested scope',
                2 => 'Refuse requested scope',
                0 => 'Back',
            ], 0);
            if ('Back' === $action) {
                $io->success('Authorization request saved. Reopen this mission to decide it later.');

                return Command::SUCCESS;
            }

            try {
                $authorization = $this->authorizations->decide(
                    $interview->getId(),
                    $authorization->getId(),
                    'Authorize requested scope' === $action,
                );
            } catch (\DomainException $exception) {
                $io->warning($exception->getMessage());

                continue;
            }

            $io->success('Requested scope '.$authorization->getStatus().'.');
            $io->note('No execution occurred. Execution remains a separate future gate.');

            return Command::SUCCESS;
        }
    }

    private function displayAuthorization(SymfonyStyle $io, Authorization $authorization): void
    {
        $io->title('Authorization — '.$authorization->getStatus());
        foreach ([
            'Resources / capabilities' => $authorization->getResources(),
            'External effects' => $authorization->getEffects(),
            'Limits' => $authorization->getLimits(),
        ] as $heading => $items) {
            $io->section($heading);
            if ([] === $items) {
                $io->writeln('None requested.');
                continue;
            }
            foreach ($items as $index => $item) {
                $io->writeln(($index + 1).'. '.$this->display($item));
            }
        }
    }

    private function displayProposal(SymfonyStyle $io, Proposal $proposal): void
    {
        $content = $proposal->getContent();
        $io->title('Proposal v'.$proposal->getVersion().' — '.$proposal->getStatus());
        $io->section('Objective');
        $io->writeln($this->display($content['objective']));
        $io->section('Deliverable');
        $io->writeln($this->display($content['deliverable']));
        foreach ([
            'Steps' => 'steps',
            'Acceptance criteria' => 'acceptanceCriteria',
            'Resource requirements' => 'resourceRequirements',
            'Limits' => 'limits',
            'Unresolved assumptions' => 'unresolvedAssumptions',
        ] as $heading => $field) {
            $io->section($heading);
            if ([] === $content[$field]) {
                $io->writeln('None recorded.');
                continue;
            }
            foreach ($content[$field] as $index => $item) {
                $io->writeln(($index + 1).'. '.$this->display($item));
            }
        }
    }

    private function selectInterview(SymfonyStyle $io, bool $interactive): ?string
    {
        while (true) {
            $interviews = $this->records->recent();
            if ([] === $interviews) {
                $io->text('No saved interviews.');
            }
            $rows = [];
            foreach ($interviews as $index => $interview) {
                $rows[] = [$index + 1, $interview->getShortId(), $this->display($interview->getAlias()), $interview->getStatus(), $interview->getAttempts(), $interview->getUpdatedAt()->format('Y-m-d H:i:s')];
            }
            if ([] !== $rows) {
                $io->table(['#', 'ID', 'Alias', 'Status', 'Attempts', 'Updated'], $rows);
            }
            if (!$interactive) {
                return null;
            }
            $io->text('new - New interview');
            $selection = trim((string) $io->ask('Select an interview number, new, or /quit', '/quit'));
            if ('new' === strtolower($selection)) {
                return 'new';
            }
            if ('/quit' === $selection) {
                return null;
            }
            if (!ctype_digit($selection) || !isset($interviews[(int) $selection - 1])) {
                $io->warning('Choose a number from the displayed list.');

                continue;
            }
            // Resolve against the displayed snapshot, not a reordered database query.
            $id = $interviews[(int) $selection - 1]->getId();
            $selected = $interviews[(int) $selection - 1];
            $io->text('Selected interview: '.$selected->getShortId().' — '.$this->display($selected->getAlias()));
            $action = $io->choice('Action', [1 => 'Continue', 2 => 'Delete permanently', 0 => 'Back'], 0);
            if ('Continue' === $action) {
                return $id;
            }
            if ('Delete permanently' === $action) {
                try {
                    $this->interviews->delete($id);
                    $io->success('Interview deleted: '.$selected->getShortId());
                } catch (\DomainException $exception) {
                    $io->warning($exception->getMessage());
                }
            }
        }
    }

    private function display(string $text): string
    {
        // Treat operator/model text as text, never Console markup or terminal controls.
        return OutputFormatter::escape(preg_replace('/[\x00-\x08\x0B-\x1F\x7F]/', '', $text) ?? '');
    }
}
