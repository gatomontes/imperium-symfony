<?php
declare(strict_types=1);
namespace App\Command;
use App\Imperium\Runtime\Foundry\AdversarialReviewerConstructionAuthorizationAcceptanceService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
#[
    AsCommand(
        name: "imperium:foundry:accept-adversarial-reviewer-construction",
        description: "Have the occupied Artificer accept exact Adversarial Reviewer Persona construction authority",
    ),
]
final class FoundryAcceptAdversarialReviewerConstructionAuthorizationCommand
    extends Command
{
    public function __construct(
        private readonly AdversarialReviewerConstructionAuthorizationAcceptanceService $service,
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->addArgument("delivery-id", InputArgument::REQUIRED)
            ->addArgument("binding-id", InputArgument::REQUIRED)
            ->addOption("json", null, InputOption::VALUE_NONE);
    }
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        try {
            $acceptance = $this->service->accept(
                (string) $input->getArgument("delivery-id"),
                (string) $input->getArgument("binding-id"),
            );
        } catch (\Throwable $e) {
            $output->writeln("<error>REFUSED</error> " . $e->getMessage());
            return self::FAILURE;
        }
        if ($input->getOption("json")) {
            $output->writeln(
                json_encode(
                    $acceptance,
                    JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES |
                        JSON_THROW_ON_ERROR,
                ),
            );
            return self::SUCCESS;
        }
        $output->writeln(
            "<info>ADVERSARIAL_REVIEWER_CONSTRUCTION_AUTHORIZATION_ACCEPTED</info> " .
                $acceptance["acceptance_id"],
        );
        $output->writeln(
            "Artificer: " . $acceptance["actor"]["manifestation_id"],
        );
        $output->writeln("Candidate: " . $acceptance["candidate_id"]);
        $output->writeln(
            "Construction authority: EXERCISABLE FOR EXACT REVIEWER PERSONA",
        );
        $output->writeln(
            "Admission, spawning, Seat binding, review, and candidate approval: NOT GRANTED",
        );
        return self::SUCCESS;
    }
}
