<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Curia\SubordinateConstructionAuthorizationDeliveryService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:curia:deliver-subordinate-construction-authorization', description: 'Deliver an exact subordinate-construction authorization act to Foundry')]
final class CuriaDeliverSubordinateConstructionAuthorizationCommand extends Command
{
    public function __construct(private readonly SubordinateConstructionAuthorizationDeliveryService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('act-id', InputArgument::REQUIRED)->addOption('json', null, InputOption::VALUE_NONE); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $delivery = $this->service->deliver((string) $input->getArgument('act-id')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) { $output->writeln(json_encode($delivery, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $output->writeln('<info>SUBORDINATE_CONSTRUCTION_AUTHORIZATION_DELIVERED</info> '.$delivery['delivery_id']); $output->writeln('Foundry: '.$delivery['status']);
        foreach ($delivery['authorized_resolutions'] as $resolution) $output->writeln('- '.$resolution['office'].' '.$resolution['subordinate_staff_class'].' '.$resolution['resolution_id']);
        $output->writeln('Recipient acceptance: NOT RECORDED'); $output->writeln('Construction authority: PRESENT; NOT EXERCISABLE PENDING ACCEPTANCE');
        $output->writeln('Selection authority: NOT GRANTED'); $output->writeln('Profile approval authority: NOT GRANTED');
        $output->writeln('Spawning authority: NOT GRANTED'); $output->writeln('Seat binding authority: NOT GRANTED'); $output->writeln('Execution authority: NOT GRANTED');
        return self::SUCCESS;
    }
}
