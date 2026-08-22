<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Conscription\ExaminationAssemblyAuthorizationRequestService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:conscription:request-examination-assembly-authorization', description: 'Request Senate authority for one examination-only assembly')]
final class ConscriptionRequestExaminationAssemblyAuthorizationCommand extends Command
{
    public function __construct(private readonly ExaminationAssemblyAuthorizationRequestService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('acceptance-id', InputArgument::REQUIRED)->addOption('json', null, InputOption::VALUE_NONE); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $request = $this->service->request((string) $input->getArgument('acceptance-id')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) { $output->writeln(json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $output->writeln('<info>EXAMINATION_ASSEMBLY_AUTHORIZATION_REQUESTED</info> '.$request['request_id']);
        $output->writeln('Status: '.$request['status']);
        $output->writeln('Assembly: NOT PERFORMED');
        $output->writeln('Senate intake: PENDING');
        return self::SUCCESS;
    }
}
