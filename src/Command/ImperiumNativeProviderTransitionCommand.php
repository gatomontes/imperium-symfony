<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\ProviderTransition\{NativeConsumer, NativeState};
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'imperium:provider-transition:execute', description: 'Consume an exact native authority into a pre-effect transition')]
final class ImperiumNativeProviderTransitionCommand extends Command
{
    public function __construct(#[Autowire('%kernel.project_dir%')] private readonly string $projectDir)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('authority-id', InputArgument::REQUIRED, 'Existing exact native authority identifier');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = (new NativeConsumer(new NativeState($this->projectDir)))->execute((string) $input->getArgument('authority-id'));
            $output->writeln(json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        } catch (\Throwable $error) {
            // Never echo input, source bytes, paths or arbitrary exception text.
            $code = $error->getMessage();
            $output->writeln(preg_match('/^(?:NIR_[A-Z_]+|EAT_[A-Z_]+|UNKNOWN_REPLAY_PROHIBITED)$/D', $code) ? $code : 'NIR_TRANSITION_REFUSED');
            return self::FAILURE;
        }
    }
}
