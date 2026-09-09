<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Citadel\Formation\CitadelIntakeService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:courtyard:intake', aliases: ['imperium:citadel:intake'], description: 'Receive an exact request at Courtyard before mission formation cognition')]
final class CitadelIntakeCommand extends Command
{
    public function __construct(private readonly CitadelIntakeService $intake) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addArgument('submission-id', InputArgument::REQUIRED, 'Stable client retry identity, 8–80 safe characters')
            ->addArgument('request-file', InputArgument::REQUIRED, 'UTF-8 request file; bytes are preserved without trimming');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $path = (string) $input->getArgument('request-file');
            if (!is_file($path) || filesize($path) > 1048576) {
                throw new \RuntimeException('CMF010_INTAKE_INPUT_INVALID');
            }
            $request = file_get_contents($path);
            if (false === $request) { throw new \RuntimeException('CMF010_INTAKE_INPUT_INVALID'); }
            $record = $this->intake->receive($request, (string) $input->getArgument('submission-id'));
            $output->writeln(json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);
            return self::SUCCESS;
        } catch (\Throwable $error) {
            $output->writeln('REFUSED '.$error->getMessage(), OutputInterface::OUTPUT_RAW);
            return self::FAILURE;
        }
    }
}
