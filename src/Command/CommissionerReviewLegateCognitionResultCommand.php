<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Citadel\CommissionerCognitionResultReviewService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:commissioner:review-legate-cognition-result', description: 'Accept or reject one exact delivered Legate cognition result and close its commission.')]
final class CommissionerReviewLegateCognitionResultCommand extends Command
{
    public function __construct(private readonly CommissionerCognitionResultReviewService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('delivery-id', InputArgument::REQUIRED)
            ->addArgument('review-authority-id', InputArgument::REQUIRED)
            ->addArgument('commissioner-binding-id', InputArgument::REQUIRED)
            ->addArgument('disposition', InputArgument::REQUIRED, 'ACCEPTED or REJECTED')
            ->addArgument('rationale', InputArgument::REQUIRED)
            ->addOption('json', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $review = $this->service->review(
                (string) $input->getArgument('delivery-id'),
                (string) $input->getArgument('review-authority-id'),
                (string) $input->getArgument('commissioner-binding-id'),
                (string) $input->getArgument('disposition'),
                (string) $input->getArgument('rationale'),
                new \DateTimeImmutable(),
            );
        } catch (\Throwable $error) {
            $output->writeln('<error>REFUSED</error> '.$error->getMessage());

            return self::FAILURE;
        }
        $output->writeln($input->getOption('json')
            ? json_encode($review, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            : '<info>'.$review['status'].'</info> '.$review['review_id']);

        return self::SUCCESS;
    }
}
