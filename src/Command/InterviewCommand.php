<?php

namespace App\Command;

use App\Atheneum\AuthorizationRecords;
use App\Atheneum\ExecutionRecords;
use App\Atheneum\InterviewRecords;
use App\Atheneum\ProposalRecords;
use App\Curia\AuthorizationService;
use App\Curia\LocalFileExecutionService;
use App\Curia\InterviewService;
use App\Curia\ProposalService;
use App\Entity\Authorization;
use App\Entity\ExecutionAttempt;
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
        private ExecutionRecords $executionRecords,
        private LocalFileExecutionService $localFileExecution,
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

                $authorization = $this->prepareAuthorization($io, $interview);
                if (null === $authorization) {
                    continue;
                }
            }

            $this->displayAuthorization($io, $authorization);

            if (Authorization::PENDING !== $authorization->getStatus()) {
                $io->success('Authorization v'.$authorization->getVersion().' is '.$authorization->getStatus().'.');

                if (Authorization::AUTHORIZED === $authorization->getStatus()
                    && null !== $authorization->getExecutionScope()) {
                    return $this->executionStage($io, $interview, $authorization);
                }

                $action = $io->choice('Authorization', [
                    1 => 'Prepare replacement authorization',
                    0 => 'Back',
                ], 0);
                if ('Back' === $action) {
                    $io->note('The existing authorization remains unchanged.');

                    return Command::SUCCESS;
                }

                $authorization = $this->prepareAuthorization($io, $interview);
                if (null === $authorization) {
                    continue;
                }

                continue;
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

            $io->success('Authorization v'.$authorization->getVersion().' '.$authorization->getStatus().'.');
            if (Authorization::AUTHORIZED === $authorization->getStatus()) {
                $io->note('No execution occurred merely by authorizing the scope.');
                if (null !== $authorization->getExecutionScope()) {
                    return $this->executionStage($io, $interview, $authorization);
                }
                $io->warning('This authorization contains no executable scope. Prepare a replacement authorization to execute.');

                continue;
            }

            return Command::SUCCESS;
        }
    }

    private function prepareAuthorization(SymfonyStyle $io, Interview $interview): ?Authorization
    {
        $io->section('Authorization request');
        $io->text('Declare the intended external effect. Canonical executable authority, when supported, is generated by the application and displayed before your decision.');
        $effects = (string) $io->ask('Requested external effects; separate multiple effects with semicolons (blank for none)', '');

        try {
            return $this->authorizations->request($interview->getId(), $effects);
        } catch (\DomainException $exception) {
            $io->warning($exception->getMessage());

            return null;
        }
    }

    private function executionStage(SymfonyStyle $io, Interview $interview, Authorization $authorization): int
    {
        try {
            $attempt = $this->localFileExecution->reconcile($interview->getId());
        } catch (\DomainException $exception) {
            $io->warning($exception->getMessage());

            return Command::SUCCESS;
        }

        if (null !== $attempt) {
            $this->displayExecutionAttempt($io, $attempt);
            if (ExecutionAttempt::PREPARED === $attempt->getStatus()) {
                $io->warning('A prepared execution attempt already exists. No automatic retry was performed.');
            }

            return Command::SUCCESS;
        }

        $action = $io->choice('Execution', [1 => 'Create authorized local file', 0 => 'Back'], 0);
        if ('Back' === $action) {
            $io->success('Authorization remains recorded. No execution attempt was created.');

            return Command::SUCCESS;
        }

        $filename = (string) $io->ask('Filename only; no directories', 'imperium-test.txt');
        $content = (string) $io->ask('File contents', 'Imperium bounded execution test.');

        try {
            $attempt = $this->localFileExecution->execute($interview->getId(), $filename, $content);
        } catch (\DomainException $exception) {
            $io->warning($exception->getMessage());

            return Command::SUCCESS;
        }

        $this->displayExecutionAttempt($io, $attempt);
        if (ExecutionAttempt::SUCCEEDED === $attempt->getStatus()) {
            $io->success('Authorized local-file effect completed and evidence was recorded.');
        } else {
            $io->warning('The execution attempt did not complete successfully. No automatic retry will occur.');
        }

        return Command::SUCCESS;
    }

    private function displayExecutionAttempt(SymfonyStyle $io, ExecutionAttempt $attempt): void
    {
        $io->title('Execution attempt — '.$attempt->getStatus());
        $io->text('Operation: '.$attempt->getOperation());
        $io->text('Target: '.$this->display($attempt->getTargetPath()));
        $io->text('SHA-256: '.$attempt->getContentSha256());
        if (null !== $attempt->getBytesWritten()) {
            $io->text('Bytes written: '.$attempt->getBytesWritten());
        }
        if (null !== $attempt->getFailureCode()) {
            $io->text('Failure: '.$attempt->getFailureCode());
        }
    }

    private function displayAuthorization(SymfonyStyle $io, Authorization $authorization): void
    {
        $io->title('Authorization v'.$authorization->getVersion().' — '.$authorization->getStatus());
        $scope = $authorization->getExecutionScope();
        $io->section('Executable scope');
        if (null === $scope) {
            $io->writeln('None. This authorization is historical/non-executable.');
        } else {
            $io->writeln('Capability: '.$this->display($scope['capability']));
            $io->writeln('Effect: '.$this->display($scope['effect']));
            $io->writeln('Root: '.$this->display($scope['root']));
            $io->writeln('Visibility: '.$this->display($scope['visibility']));
            $io->writeln('Allowed extensions: '.implode(', ', array_map($this->display(...), $scope['allowedExtensions'])));
            $io->writeln('Max files: '.$scope['maxFiles']);
            $io->writeln('Overwrite: '.($scope['overwrite'] ? 'true' : 'false'));
            $io->writeln('Max bytes: '.$scope['maxBytes']);
        }

        foreach ([
            'Proposal resource context' => $authorization->getResources(),
            'Operator effect context' => $authorization->getEffects(),
            'Proposal limit context' => $authorization->getLimits(),
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
