<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Citadel\Authority\{AuthorityInput, GarrisonAuthorityRequest, RecruiterEvidence};
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:citadel:authority', description: 'Native authority preparation/refusal; private Recruiter export requires separate owner authorization')]
final class CitadelAuthorityCommand extends Command
{
    public function __construct(private readonly RecruiterEvidence $recruiter, private readonly GarrisonAuthorityRequest $garrison) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addArgument('mode', InputArgument::REQUIRED, 'recruiter-export, recruiter-inspect, recruiter-compare, garrison-request, garrison-inspect, garrison-verify');
        $this->addArgument('input', InputArgument::REQUIRED, 'Exact instance ID for export; otherwise explicit public JSON file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $mode = $input->getArgument('mode'); $argument = (string) $input->getArgument('input');
            AuthorityInput::require(in_array($mode, ['recruiter-export', 'recruiter-inspect', 'recruiter-compare', 'garrison-request', 'garrison-inspect', 'garrison-verify'], true), 'CAI040_MODE_UNSUPPORTED');
            $data = $mode === 'recruiter-export' ? null : AuthorityInput::read($argument);
            $result = match ($mode) {
                'recruiter-export' => $this->recruiter->export($argument),
                'recruiter-inspect' => $this->recruiter->inspect($data),
                'recruiter-compare' => $this->compare($data),
                'garrison-request' => $this->garrison->prepare($data),
                'garrison-inspect' => $this->garrison->inspect($data),
                'garrison-verify' => $this->garrison->verifyRevision($data),
            };
            $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);
            return 2; // Complete preparation/refusal is never positive authority.
        } catch (\Throwable $e) {
            $code = preg_match('/^CAI[0-9]{3}_[A-Z0-9_]+$/D', $e->getMessage()) ? $e->getMessage() : 'CAI041_INPUT_OR_SOURCE_REFUSED';
            $output->writeln(json_encode(['disposition' => 'REFUSED', 'code' => $code, ...AuthorityInput::flags()], JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);
            return self::FAILURE;
        }
    }

    private function compare(array $input): array
    {
        AuthorityInput::keys($input, ['projection', 'instance_id']); AuthorityInput::id($input['instance_id']);
        return $this->recruiter->compareAtSource($input['projection'], $input['instance_id']);
    }
}
