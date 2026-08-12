<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Conscription\ArtificerConscriptionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:conscription:fulfill-artificer', description: 'Instantiate and qualify the exact canonical Artificer')]
final class ConscriptionFulfillArtificerCommand extends Command
{
    public function __construct(private readonly ArtificerConscriptionService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('commission-id', InputArgument::REQUIRED, 'Exact Artificer construction commission')->addOption('json', null, InputOption::VALUE_NONE, 'Emit complete qualified manifestation packet'); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $result = $this->service->fulfill((string) $input->getArgument('commission-id')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) { $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $delivery = $result['delivery'];
        $output->writeln('<info>ARTIFICER_MANIFESTATION_QUALIFIED</info> '.$delivery['delivery_id']);
        $output->writeln('Recruiter: '.$result['recruiter']['manifestation_id']);
        $output->writeln('Candidate: '.$delivery['candidate']['manifestation_id']);
        $output->writeln('Status: '.$delivery['candidate']['status']);
        $output->writeln('Foundry construction authority: NOT GRANTED');
        $output->writeln('Seat binding authority: NOT GRANTED');
        $output->writeln('Recipient acceptance: NOT RECORDED');
        $output->writeln('Execution authority: NOT GRANTED');
        return self::SUCCESS;
    }
}
