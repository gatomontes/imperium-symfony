<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Citadel\Formation\FormationPreparation;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:citadel:prepare', description: 'Inspect supplied public evidence or prepare unsigned exact owner bytes; no runtime effects')]
final class CitadelPreparationCommand extends Command
{
    public function __construct(private readonly FormationPreparation $preparation) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addArgument('mode', InputArgument::REQUIRED, 'inspect, decision or assemble (public signature only)');
        $this->addArgument('public-file', InputArgument::REQUIRED, 'Explicit public JSON input only');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $path = (string) $input->getArgument('public-file');
            if (!is_file($path) || filesize($path) > 8388608) { throw new \RuntimeException('CRP008_PUBLIC_INPUT_INVALID'); }
            $public = json_decode((string) file_get_contents($path), true, 128, JSON_THROW_ON_ERROR);
            $result = match ($input->getArgument('mode')) {
                'inspect' => $this->preparation->inspect($public),
                'decision' => $this->preparation->prepare($public),
                'assemble' => $this->preparation->assemble($public),
                default => throw new \RuntimeException('CRP009_MODE_UNSUPPORTED'),
            };
            $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);
            // Successful inspection is not a successful readiness gate.
            return $input->getArgument('mode') === 'inspect' ? 2 : self::SUCCESS;
        } catch (\Throwable $error) {
            $output->writeln('REFUSED '.$error->getMessage(), OutputInterface::OUTPUT_RAW);
            return self::FAILURE;
        }
    }
}
