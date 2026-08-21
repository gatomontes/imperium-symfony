<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Curia\PersonnelUseAuthorizationRequestService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:curia:request-personnel-use-authorization', description: 'Present exact capability-slot personnel commitments to Imperator')]
final class CuriaRequestPersonnelUseAuthorizationCommand extends Command
{
    public function __construct(private readonly PersonnelUseAuthorizationRequestService $service) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addArgument('disposition-id', InputArgument::REQUIRED, 'Opaque exact Guildhall personnel-use disposition')
            ->addOption('json', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $request = $this->service->request((string) $input->getArgument('disposition-id'));
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());
            return self::FAILURE;
        }
        $output->writeln((bool) $input->getOption('json')
            ? json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            : '<info>PERSONNEL_USE_AUTHORIZATION_REQUESTED</info> '.$request['request_id']);
        return self::SUCCESS;
    }
}
