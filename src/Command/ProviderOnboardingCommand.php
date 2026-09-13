<?php
declare(strict_types=1);
namespace App\Command;

use App\Imperium\Runtime\Onboarding\Console\{Gateway,PublicResult};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;
use App\Imperium\Runtime\Onboarding\Ledger\{CommandLedger,Recovery};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument,InputInterface,InputOption};
use Symfony\Component\Console\Output\{OutputInterface,ConsoleOutputInterface};

#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
abstract class ProviderOnboardingCommand extends Command
{
    public function __construct(private readonly Gateway $gateway) { parent::__construct(); }
    abstract protected function operation(): string;

    protected function configure(): void
    {
        $this->addArgument($this->operation() === 'status'?'sequence-id':'public-request-file',InputArgument::REQUIRED)
            ->addOption('format',null,InputOption::VALUE_REQUIRED,'Output format: human or json.','human');
    }

    protected function execute(InputInterface $input,OutputInterface $output): int
    {
        $operation = $this->operation();
        $result = PublicResult::empty(null,null,$operation === 'onboard'?'preview':$operation);
        set_error_handler(static function(int $severity,string $message): bool {
            if (!(error_reporting() & $severity)) { return false; }
            throw new \ErrorException('O5_STORAGE_OBSERVATION_FAILED',0,$severity);
        });
        try {
            if (!in_array($input->getOption('format'),['human','json'],true)) { throw new \InvalidArgumentException('O5_FORMAT_INVALID'); }
            if ($operation === 'status') {
                $sequence = R::id($input->getArgument('sequence-id'));
                $result['sequence_id'] = $sequence;
                $result = $this->gateway->status($sequence);
            } else {
                $path = $input->getArgument('public-request-file');
                if (!is_string($path) || str_contains($path,'://') || !is_file($path) || !is_readable($path)) { throw new \RuntimeException('O5_REQUEST_FILE_UNAVAILABLE'); }
                $handle = @fopen($path,'rb');
                if ($handle === false) { throw new \RuntimeException('O5_REQUEST_FILE_UNAVAILABLE'); }
                try { $raw = stream_get_contents($handle,1048577); } finally { fclose($handle); }
                if (!is_string($raw)) { throw new \RuntimeException('O5_REQUEST_FILE_UNAVAILABLE'); }
                $q = $operation === 'onboard'?CommandLedger::request($raw):Recovery::request($raw);
                $result = PublicResult::empty($q['sequence_id'],$q['command_id'],$q['mode'] ?? 'resume');
                $result = $operation === 'onboard'?$this->gateway->onboard($raw):$this->gateway->resume($raw);
            }
        } catch (\Throwable $error) { $result = PublicResult::refusal($error,$result); }
        finally { restore_error_handler(); }
        if ($input->getOption('format') === 'json') {
            $output->writeln(json_encode($result,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),OutputInterface::OUTPUT_RAW);
        } else {
            $lines = [$result['status'],$result['next_action']['explanation']];
            foreach ($result['facts'] as $name=>$fact) { $lines[] = $name.': '.$fact['state']; }
            $lines[] = 'Next: '.$result['next_action']['code'].($result['next_action']['step_id'] === null?'':' '.$result['next_action']['step_id']);
            $lines[] = 'New effects: '.($result['effects']['new_effects_this_command']?'yes':'no');
            $lines[] = 'Evidence: '.json_encode($result['evidence_refs'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
            $output->writeln($lines,OutputInterface::OUTPUT_RAW);
        }
        if ($result['reason_codes'] !== []) {
            $diagnostics = $output instanceof ConsoleOutputInterface?$output->getErrorOutput():$output;
            // Machine stdout remains a single envelope even with non-console test output.
            if ($diagnostics !== $output || $input->getOption('format') !== 'json') {
                $diagnostics->writeln(implode(', ',$result['reason_codes']),OutputInterface::OUTPUT_RAW);
            }
        }
        return PublicResult::exitCode($result);
    }
}
