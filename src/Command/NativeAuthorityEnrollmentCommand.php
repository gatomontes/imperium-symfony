<?php
declare(strict_types=1);
namespace App\Command;
use App\Imperium\Runtime\Citadel\Authority\AuthorityInput as A;
use App\Imperium\Runtime\Citadel\NativeAuthority\NativeTrust;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:native-authority:enroll', description: 'Separate deployment-owner public trust enrollment and legacy fence; requires explicit owner authorization')]
final class NativeAuthorityEnrollmentCommand extends Command
{
    public function __construct(private readonly NativeTrust $trust) { parent::__construct(); }
    protected function configure(): void
    {
        $this->addArgument('policy', InputArgument::REQUIRED, 'Explicit public enrollment policy JSON');
        $this->addArgument('confirmed-fingerprint', InputArgument::REQUIRED, 'Independently confirmed public fingerprint');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = $this->trust->enroll(A::read((string) $input->getArgument('policy')), (string) $input->getArgument('confirmed-fingerprint'));
            $output->writeln(json_encode($result, JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW); return 0;
        } catch (\Throwable $e) {
            $code = preg_match('/^(NAT|CAI)[0-9]{3}_[A-Z0-9_]+$/D', $e->getMessage()) ? $e->getMessage() : 'NAT099_INPUT_OR_STORAGE_REFUSED';
            $output->writeln(json_encode(['disposition' => 'REFUSED', 'code' => $code, ...A::flags()], JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW); return 1;
        }
    }
}
