<?php
declare(strict_types=1);
namespace App\Command;
use App\Imperium\Runtime\Curia\AdversarialReviewerBootstrapReviewAuthorizationRequestService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
#[
    AsCommand(
        name: "imperium:curia:request-adversarial-reviewer-bootstrap-review",
        description: "Request an exact one-time bootstrap review after authenticated Reviewer absence",
    ),
]
final class CuriaRequestAdversarialReviewerBootstrapReviewCommand extends
    Command
{
    public function __construct(
        private readonly AdversarialReviewerBootstrapReviewAuthorizationRequestService $service,
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->addArgument(
            "availability-response-id",
            InputArgument::REQUIRED,
        )->addOption("json", null, InputOption::VALUE_NONE);
    }
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        try {
            $request = $this->service->request(
                (string) $input->getArgument("availability-response-id"),
            );
        } catch (\Throwable $e) {
            $output->writeln("<error>REFUSED</error> " . $e->getMessage());
            return self::FAILURE;
        }
        if ($input->getOption("json")) {
            $output->writeln(
                json_encode(
                    $request,
                    JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES |
                        JSON_THROW_ON_ERROR,
                ),
            );
            return self::SUCCESS;
        }
        $output->writeln(
            "<info>ADVERSARIAL_REVIEWER_BOOTSTRAP_REVIEW_REQUESTED</info> " .
                $request["request_id"],
        );
        $output->writeln("Status: " . $request["status"]);
        $output->writeln(
            "Protocol, reviewer, findings, and every downstream authority: NOT GRANTED",
        );
        return self::SUCCESS;
    }
}
