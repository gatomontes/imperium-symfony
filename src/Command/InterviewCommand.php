<?php

namespace App\Command;

use App\Atheneum\InterviewRecords;
use App\Curia\InterviewService;
use App\Entity\Interview;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'imperium:interview', description: 'Start or resume a local interview with Seneschal.')]
class InterviewCommand extends Command
{
    public function __construct(private InterviewRecords $records, private InterviewService $interviews)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('id', InputArgument::OPTIONAL, 'Interview UUID to resume')
            ->addOption('list', null, InputOption::VALUE_NONE, 'List the 20 most recently updated interviews');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            if ($input->getOption('list')) {
                $io->table(['ID', 'Status', 'Attempts', 'Updated'], array_map(static fn (Interview $i): array => [
                    $i->getId(), $i->getStatus(), $i->getAttempts(), $i->getUpdatedAt()->format('Y-m-d H:i:s'),
                ], $this->records->recent()));

                return Command::SUCCESS;
            }
            if (!$input->isInteractive()) {
                $io->error('The interview requires an interactive terminal. --list works non-interactively.');

                return Command::INVALID;
            }
            $id = $input->getArgument('id');
            $interview = null === $id ? $this->records->create() : $this->records->get($id);
            $id = $interview->getId();
            $io->title('Imperium — Seneschal');
            $io->text('Interview: '.$id);
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
            $io->success('Permission to draft recorded. Proposal generation is the next milestone; no proposal was generated or execution authorized.');

            return Command::SUCCESS;
        } catch (\DomainException $exception) {
            $io->error($exception->getMessage());

            return Command::INVALID;
        } catch (\Throwable) {
            $io->error('Interview storage or configuration is unavailable. Check the PostgreSQL connection and run migrations. Resume using --list once resolved.');

            return Command::FAILURE;
        }
    }

    private function display(string $text): string
    {
        // Treat operator/model text as text, never Console markup or terminal controls.
        return OutputFormatter::escape(preg_replace('/[\x00-\x08\x0B-\x1F\x7F]/', '', $text) ?? '');
    }
}
