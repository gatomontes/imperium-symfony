<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Senate\ProfileExaminationDeliberationOpeningService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name:'imperium:senate:open-profile-examination-deliberation',description:'Open bounded reconciliation over three sealed Profile examination findings')]
final class SenateOpenProfileExaminationDeliberationCommand extends Command
{
    public function __construct(private readonly ProfileExaminationDeliberationOpeningService $service){parent::__construct();}
    protected function configure():void{$this->addArgument('readiness-id',InputArgument::REQUIRED)->addArgument('lord-speaker-binding-id',InputArgument::REQUIRED)->addOption('json',null,InputOption::VALUE_NONE);}
    protected function execute(InputInterface $input,OutputInterface $output):int{try{$record=$this->service->open((string)$input->getArgument('readiness-id'),(string)$input->getArgument('lord-speaker-binding-id'));}catch(\Throwable $exception){$output->writeln('<error>REFUSED</error> '.$exception->getMessage());return self::FAILURE;}$output->writeln($input->getOption('json')?json_encode($record,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR):'<info>PROFILE_EXAMINATION_DELIBERATION_OPENED</info> '.$record['deliberation_id']);return self::SUCCESS;}
}
