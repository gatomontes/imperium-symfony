<?php

declare(strict_types=1);

namespace App\Command;

use App\Bootstrap\Launcher;
use App\Bootstrap\ValidationException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:bootstrap:validate', description: 'Validate the immutable bootstrap composition without mutation')]
final class BootstrapValidateCommand extends Command
{
    public function __construct(private readonly Launcher $launcher)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $receipt = $this->launcher->validate();
        } catch (ValidationException $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());
            return self::FAILURE;
        }
        $output->writeln('<info>VALID</info> '.$receipt->manifestId);
        return self::SUCCESS;
    }
}
