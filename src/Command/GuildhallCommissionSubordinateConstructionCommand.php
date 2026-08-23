<?php
declare(strict_types=1);
namespace App\Command;
use App\Imperium\Runtime\Guildhall\SubordinatePersonnelConstructionCommissionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
#[AsCommand(name: "imperium:guildhall:commission-subordinate-construction", description: "Commission Foundry for exact authorized subordinate personnel")]
final class GuildhallCommissionSubordinateConstructionCommand extends Command
{
    public function __construct(private readonly SubordinatePersonnelConstructionCommissionService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument("authorization-delivery-id", InputArgument::REQUIRED); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    { try { $r = $this->service->commission((string) $input->getArgument("authorization-delivery-id")); } catch (\Throwable $e) { $output->writeln("<error>REFUSED</error> " . $e->getMessage()); return self::FAILURE; } $output->writeln("<info>SUBORDINATE_CONSTRUCTION_COMMISSIONED</info> " . $r["commission_id"]); return self::SUCCESS; }
}
