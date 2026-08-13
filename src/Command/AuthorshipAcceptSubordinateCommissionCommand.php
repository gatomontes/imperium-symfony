<?php
declare(strict_types=1);
namespace App\Command;
use App\Imperium\Runtime\Authorship\SubordinateAuthorshipCommissionAcceptanceService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name:'imperium:authorship:accept-subordinate',description:'Have the addressed resident Officer accept one exact subordinate Persona authorship commission')]
final class AuthorshipAcceptSubordinateCommissionCommand extends Command{
public function __construct(private readonly SubordinateAuthorshipCommissionAcceptanceService$service){
parent::__construct();

}
protected function configure():void{
$this->addArgument('office',InputArgument::REQUIRED)->addArgument('commission-id',InputArgument::REQUIRED)->addArgument('binding-id',InputArgument::REQUIRED)->addOption('json',null,InputOption::VALUE_NONE);

}
protected function execute(InputInterface$i,OutputInterface$o):int{
try{
$a=$this->service->accept((string)$i->getArgument('office'),(string)$i->getArgument('commission-id'),(string)$i->getArgument('binding-id'));

}
catch(\Throwable$e){
$o->writeln('<error>REFUSED</error> '.$e->getMessage());
return self::FAILURE;

}
if((bool)$i->getOption('json')){
$o->writeln(json_encode($a,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
return self::SUCCESS;

}
$o->writeln('<info>SUBORDINATE_AUTHORSHIP_COMMISSION_ACCEPTED</info> '.$a['acceptance_id']);
$o->writeln('Office: '.$a['office']);
$o->writeln('Officer: '.$a['actor']['manifestation_id']);
$o->writeln('Class: '.$a['authorship_class']);
$o->writeln('Authorship authority: EXERCISABLE FOR EXACT COMMISSION');
$o->writeln('Persona assembly authority: NOT GRANTED');
$o->writeln('Persona approval authority: NOT GRANTED');
$o->writeln('Profile approval authority: NOT GRANTED');
$o->writeln('Spawning authority: NOT GRANTED');
$o->writeln('Admission authority: NOT GRANTED');
$o->writeln('Execution authority: NOT GRANTED');
return self::SUCCESS;

}

}

