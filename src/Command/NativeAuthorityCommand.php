<?php
declare(strict_types=1);
namespace App\Command;
use App\Imperium\Runtime\Citadel\Authority\AuthorityInput as A;
use App\Imperium\Runtime\Citadel\NativeAuthority\NativeProtocol;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:native-authority', description: 'Native institutional protocol; no enrollment, keys, provider or root overrides')]
final class NativeAuthorityCommand extends Command
{
    public function __construct(private readonly NativeProtocol $protocol) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('input', InputArgument::REQUIRED, 'Explicit public operation JSON'); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $i = A::read((string) $input->getArgument('input')); A::keys($i, ['operation', 'arguments']);
            $a = $i['arguments']; A::require(is_array($a), 'NAT021_INPUT_INVALID');
            $result = match ($i['operation']) {
                'prepare' => $this->protocol->prepare($a), 'assemble' => $this->protocol->assemble($a), 'apply' => $this->protocol->apply($a),
                'snapshot' => $this->snapshot($a), 'resolve' => $this->resolve($a), 'admit' => $this->admit($a), 'inventory' => $this->inventory($a),
                default => throw new \RuntimeException('NAT020_EFFECT_UNSUPPORTED'),
            };
            $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);
            return in_array($i['operation'], ['prepare', 'assemble', 'snapshot'], true) ? 2 : 0;
        } catch (\Throwable $e) {
            $code = preg_match('/^(NAT|CAI)[0-9]{3}_[A-Z0-9_]+$/D', $e->getMessage()) ? $e->getMessage() : 'NAT099_INPUT_OR_STORAGE_REFUSED';
            $output->writeln(json_encode(['disposition' => 'REFUSED', 'code' => $code, ...A::flags()], JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW); return 1;
        }
    }
    private function snapshot(array $a): array { A::keys($a, []); return $this->protocol->snapshot(); }
    private function resolve(array $a): array { A::keys($a, ['seat']); return $this->protocol->resolve($a['seat']); }
    private function admit(array $a): array { A::keys($a, ['delivery_id', 'binding_id']); return $this->protocol->admit($a['delivery_id'], $a['binding_id']); }
    private function inventory(array $a): array { A::keys($a, ['inquiry_id']); return $this->protocol->inventory($a['inquiry_id']); }
}
