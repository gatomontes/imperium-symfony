<?php
declare(strict_types=1);
namespace App\Command;
use App\Imperium\Runtime\Curia\AdversarialReviewerAvailabilityInquiryService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
#[
    AsCommand(
        name: "imperium:curia:inquire-adversarial-reviewer-availability",
        description: "Ask Garrison for exact existing admitted Reviewer availability facts",
    ),
]
final class CuriaInquireAdversarialReviewerAvailabilityCommand extends Command
{
    public function __construct(
        private readonly AdversarialReviewerAvailabilityInquiryService $service,
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->addArgument(
            "review-case-id",
            InputArgument::REQUIRED,
        )->addOption("json", null, InputOption::VALUE_NONE);
    }
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        try {
            $inquiry = $this->service->inquire(
                (string) $input->getArgument("review-case-id"),
            );
        } catch (\Throwable $e) {
            $output->writeln("<error>REFUSED</error> " . $e->getMessage());
            return self::FAILURE;
        }
        if ($input->getOption("json")) {
            $output->writeln(
                json_encode(
                    $inquiry,
                    JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES |
                        JSON_THROW_ON_ERROR,
                ),
            );
            return self::SUCCESS;
        }
        $output->writeln(
            "<info>ADVERSARIAL_REVIEWER_AVAILABILITY_INQUIRY_ROUTED</info> " .
                $inquiry["inquiry_id"],
        );
        $output->writeln("Status: " . $inquiry["status"]);
        $output->writeln(
            "Candidate-under-construction satisfaction and every downstream authority: PROHIBITED",
        );
        return self::SUCCESS;
    }
}
