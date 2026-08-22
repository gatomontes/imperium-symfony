<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Senate\ProfileExaminationReconciliationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name:'imperium:senate:reconcile-profile-examination-findings',description:'Reconcile three sealed Profile examination findings without disposition')]
final class SenateReconcileProfileExaminationFindingsCommand extends Command
{
    public function __construct(private readonly ProfileExaminationReconciliationService $service){parent::__construct();}
    protected function configure():void{$this->addArgument('deliberation-id',InputArgument::REQUIRED)->addArgument('lord-speaker-binding-id',InputArgument::REQUIRED)->addOption('json',null,InputOption::VALUE_NONE);}
    protected function execute(InputInterface $input,OutputInterface $output):int{try{$record=$this->service->reconcile((string)$input->getArgument('deliberation-id'),(string)$input->getArgument('lord-speaker-binding-id'));}catch(\Throwable $exception){$output->writeln('<error>REFUSED</error> '.$exception->getMessage());return self::FAILURE;}$output->writeln($input->getOption('json')?json_encode($record,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR):'<info>PROFILE_EXAMINATION_FINDINGS_RECONCILED</info> '.$record['reconciliation_id']);return self::SUCCESS;}
}
