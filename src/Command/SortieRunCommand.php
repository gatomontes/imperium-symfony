<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\LaCortine\RawExternalPayloadCodec;
use App\Imperium\Runtime\Sortie\OneShotSortieRunner;
use App\Imperium\Runtime\Sortie\SortieManifestCodec;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:sortie:run', description: 'Run exactly one externally commissioned sortie and emit one raw payload')]
final class SortieRunCommand extends Command
{
    public function __construct(
        private readonly SortieManifestCodec $manifestCodec,
        private readonly OneShotSortieRunner $runner,
        private readonly RawExternalPayloadCodec $payloadCodec,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('manifest-file', InputArgument::REQUIRED, 'Path to the sealed sortie manifest envelope')
            ->addArgument('expected-digest', InputArgument::REQUIRED, 'Iron Gate issued SHA-256 digest for the exact manifest')
            ->addArgument('output-file', InputArgument::REQUIRED, 'New file to receive the single raw external payload');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $manifestFile = (string) $input->getArgument('manifest-file');
            $expectedDigest = strtolower((string) $input->getArgument('expected-digest'));
            $outputFile = (string) $input->getArgument('output-file');

            if (!is_file($manifestFile) || !is_readable($manifestFile)) {
                throw new \RuntimeException('SORTIE_MANIFEST_UNREADABLE: manifest file is absent or unreadable.');
            }
            if (file_exists($outputFile)) {
                throw new \RuntimeException('SORTIE_OUTPUT_EXISTS: one-shot runtime will not overwrite an existing return artifact.');
            }

            $encoded = file_get_contents($manifestFile);
            if (false === $encoded) {
                throw new \RuntimeException('SORTIE_MANIFEST_UNREADABLE: manifest bytes could not be read.');
            }

            $envelope = $this->manifestCodec->decode($encoded, $expectedDigest);
            $payload = $this->runner->run($envelope, new \DateTimeImmutable());
            $bytes = $this->payloadCodec->encode($payload);

            if (false === file_put_contents($outputFile, $bytes, LOCK_EX)) {
                throw new \RuntimeException('SORTIE_OUTPUT_FAILED: raw payload could not be written.');
            }
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());
            return self::FAILURE;
        }

        $output->writeln('<info>RETURNED</info>');
        return self::SUCCESS;
    }
}
