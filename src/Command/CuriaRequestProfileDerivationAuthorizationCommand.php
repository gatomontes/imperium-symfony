<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Curia\ProfileDerivationAuthorizationRequestService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:curia:request-profile-derivation', description: 'Request Imperator authorization for an exact reserved Persona Profile scope')]
final class CuriaRequestProfileDerivationAuthorizationCommand extends Command
{
    public function __construct(private readonly ProfileDerivationAuthorizationRequestService $service) { parent::__construct(); }
    protected function configure(): void
    {
        $this->addArgument('reservation-disposition-id', InputArgument::REQUIRED)
            ->addArgument('plan-turn-sequence', InputArgument::REQUIRED)
            ->addOption('json', null, InputOption::VALUE_NONE);
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $request = $this->service->request((string) $input->getArgument('reservation-disposition-id'), (int) $input->getArgument('plan-turn-sequence'));
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE;
        }
        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS;
        }
        $output->writeln('<info>PROFILE_DERIVATION_AUTHORIZATION_REQUESTED</info> '.$request['request_id']);
        $output->writeln('Persona: '.$request['profile_scope']['persona']['persona_id']);
        $output->writeln('Profession: '.$request['profile_scope']['profession']);
        $output->writeln('Profile derivation authority: NOT GRANTED');
        $output->writeln('Retrieval authority: NOT GRANTED');
        return self::SUCCESS;
    }
}
