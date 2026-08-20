<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Foundry\SubordinatePersonaDirectSenateConfirmationRequestService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[
    AsCommand(
        name: "imperium:foundry:request-subordinate-persona-confirmation",
        description: "Send an exact production-approved Persona directly to Senate",
    ),
]
final class FoundryRequestDirectSubordinatePersonaConfirmationCommand extends
    Command
{
    public function __construct(
        private readonly SubordinatePersonaDirectSenateConfirmationRequestService $service,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            "production-approval-id",
            InputArgument::REQUIRED,
        )->addOption("json", null, InputOption::VALUE_NONE);
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        try {
            $record = $this->service->request(
                (string) $input->getArgument("production-approval-id"),
            );
        } catch (\Throwable $exception) {
            $output->writeln(
                "<error>REFUSED</error> " . $exception->getMessage(),
            );
            return self::FAILURE;
        }
        $output->writeln(
            $input->getOption("json")
                ? json_encode(
                    $record,
                    JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES |
                        JSON_THROW_ON_ERROR,
                )
                : "<info>SUBORDINATE_PERSONA_SENT_DIRECTLY_TO_SENATE</info> " .
                    $record["confirmation_request_id"],
        );
        return self::SUCCESS;
    }
}
