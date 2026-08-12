<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Guildhall\GuildhallDeliberationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:guildhall:deliberate', description: 'Convene Guildhall and seal its profession determination')]
final class GuildhallDeliberateCommand extends Command
{
    public function __construct(private readonly GuildhallDeliberationService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('acceptance-id', InputArgument::REQUIRED, 'Exact Guildmaster commission-acceptance identifier')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the complete sealed determination');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $record = $this->service->deliberate(
                (string) $input->getArgument('acceptance-id'),
                static function (string $seat, string $status) use ($output): void {
                    $label = str_replace('_', '-', $seat);
                    $output->writeln(sprintf('<comment>Guildhall:</comment> %s %s', $label, $status));
                },
            );
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());
            return self::FAILURE;
        }
        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        }
        $output->writeln('<info>GUILDHALL_PROFESSION_DETERMINED</info> '.$record['determination_id']);
        $output->writeln('Disposition: '.$record['guildmaster_synthesis']['disposition']);
        foreach ($record['guildmaster_synthesis']['required_professions'] as $profession) {
            $output->writeln('- Profession: '.$profession);
        }
        foreach ($record['guildmaster_synthesis']['garrison_inventory_queries'] as $query) {
            $output->writeln('- Garrison inquiry: '.$query);
        }
        $output->writeln('Final Personnel Disposition: PENDING GARRISON FACTS');
        $output->writeln('Spawning authority: NOT GRANTED');
        $output->writeln('Execution authority: NOT GRANTED');
        return self::SUCCESS;
    }
}
