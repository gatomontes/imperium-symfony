<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Curia\CurianAudience;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:curia:request', description: 'Open a durable Curian proceeding from an Imperator request')]
final class CuriaRequestCommand extends Command
{
    public function __construct(private readonly CurianAudience $audience)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('request', InputArgument::REQUIRED, 'Imperator request under the local development authority')
            ->addOption('json', null, \Symfony\Component\Console\Input\InputOption::VALUE_NONE, 'Emit the complete proceeding record');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $record = $this->audience->open((string) $input->getArgument('request'));
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());

            return self::FAILURE;
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        $output->writeln('<info>'.$record['status'].'</info> '.$record['proceeding_id']);
        $output->writeln('Chamberlain: '.$record['chamberlain']['disposition']);
        $output->writeln('Isolde: '.$record['secretary']['disposition']);
        $output->writeln('Seneschal: '.$record['seneschal']['disposition']);
        $output->writeln($record['seneschal']['decision']);
        if (null !== $record['seneschal']['question']) {
            $output->writeln('Question: '.$record['seneschal']['question']);
        }
        $demands = $record['resource_demands'];
        $output->writeln('Resources: '.([] === $demands ? 'none declared' : implode(', ', $demands)));
        $output->writeln('Authorization: '.($record['authorization_required'] ? 'required' : 'not currently required'));

        return self::SUCCESS;
    }
}
