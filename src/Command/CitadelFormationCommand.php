<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Citadel\Formation\FormationCognition;
use App\Imperium\Runtime\Citadel\Formation\FormationJournal;
use App\Imperium\Runtime\Citadel\Formation\FormationPersonnel;
use App\Imperium\Runtime\Citadel\Formation\FormationSignatures;
use App\Imperium\Runtime\Citadel\Formation\CuriaFormationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:citadel:formation', description: 'Apply one exact mission-formation operation from a JSON request file')]
final class CitadelFormationCommand extends Command
{
    public function __construct(
        private readonly FormationCognition $cognition,
        private readonly FormationPersonnel $personnel,
        private readonly FormationSignatures $signatures,
        private readonly CuriaFormationService $formation,
    ) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addArgument('request-file', InputArgument::REQUIRED, 'JSON object: operation and arguments; no root, clock, verifier or transport options');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $path = (string) $input->getArgument('request-file');
            if (!is_file($path) || filesize($path) > 8388608) { throw new \RuntimeException('CMF100_COMMAND_INPUT_INVALID'); }
            $request = json_decode((string) file_get_contents($path), true, 128, JSON_THROW_ON_ERROR);
            if (!FormationJournal::keys($request, ['operation', 'arguments']) || !is_array($request['arguments'])) { throw new \RuntimeException('CMF100_COMMAND_INPUT_INVALID'); }
            [$service, $method, $fields] = match ($request['operation']) {
                'personnel-authority-source' => [$this->personnel, 'authoritySource', ['role']],
                'delegate-personnel-evidence' => [$this->personnel, 'delegate', ['delegation', 'decision']],
                'record-personnel-evidence' => [$this->personnel, 'record', ['envelope']],
                'appoint-castellan' => [$this->personnel, 'appointCastellan', ['candidate', 'decision']],
                'appoint-locksmith' => [$this->personnel, 'appointLocksmith', ['candidate', 'decision']],
                'revoke-decision' => [$this->signatures, 'revoke', ['envelope', 'nonce']],
                'reply' => [$this->cognition, 'reply', ['intakeId', 'content', 'changedIntent', 'decision']],
                'drafting-request' => [$this->cognition, 'draftingRequest', ['intakeId', 'charter']],
                'authorization-source' => [$this->cognition, 'authorizationSource', ['intakeId', 'phase']],
                'grant' => [$this->cognition, 'grant', ['intakeId', 'phase', 'terms', 'decision']],
                'control-session' => [$this->cognition, 'control', ['sessionId', 'disposition', 'decision']],
                'call' => [$this->cognition, 'call', ['sessionId', 'attemptId']],
                'recover-response' => [$this->cognition, 'recover', ['sessionId', 'attemptId']],
                'present-mission' => [$this->formation, 'presentation', ['intakeId', 'version', 'appointments', 'expiresAt']],
                'review-mission' => [$this->formation, 'review', ['terms', 'disposition', 'lineDigests', 'rationale', 'decision']],
                'reserve-mission' => [$this->formation, 'reserve', ['reviewId']],
                'deliver-handoff' => [$this->formation, 'deliver', ['intakeId']],
                'route-mission' => [$this->formation, 'route', ['missionId', 'decision']],
                'expire-unused-reservation' => [$this->formation, 'expireUnused', ['intakeId']],
                'validate-step-one' => [$this->formation, 'validateStepOne', ['intakeId']],
                default => throw new \RuntimeException('CMF101_OPERATION_UNSUPPORTED'),
            };
            if (!FormationJournal::keys($request['arguments'], $fields)) { throw new \RuntimeException('CMF100_COMMAND_INPUT_INVALID'); }
            $result = $service->$method(...$request['arguments']);
            $output->writeln(json_encode(['result' => $result, 'execution_authority' => false], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);
            return self::SUCCESS;
        } catch (\Throwable $error) {
            $output->writeln('REFUSED '.$error->getMessage(), OutputInterface::OUTPUT_RAW);
            return self::FAILURE;
        }
    }
}
