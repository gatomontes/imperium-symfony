<?php
declare(strict_types=1);

namespace App\Tests\Imperium\Runtime\Support;

use App\Bootstrap\CanonicalJson;
use App\Command\CitadelFormationCommand;
use App\Command\CitadelIntakeCommand;
use App\Imperium\Runtime\Citadel\Formation\{FormationJournal, FormationInstitution, FormationSignatures, FormationPersonnel, FormationCognition, CuriaFormationService, CitadelIntakeService, FormationPlan, BoundedFormationTransport};
use App\Imperium\Runtime\Clavium\ProviderResponseEnvelopeService;
use App\Imperium\Runtime\Clavium\FormationSessionLeaseService;
use App\Imperium\Runtime\Clock;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Console\Tester\CommandTester;

/** Synthetic keys never leave memory. No authority records are seeded into storage. */
final class CitadelFormationFixture
{
    public string $root;
    public FormationJournal $journal;
    public FormationSignatures $signatures;
    public FormationPersonnel $personnel;
    public FormationCognition $cognition;
    public CuriaFormationService $formation;
    public CitadelIntakeService $intake;
    public SyntheticFormationClock $clock;
    public SyntheticFormationTransport $transport;
    public ContainerBuilder $container;
    private string $secret;
    private array $delegates = [];

    public function __construct()
    {
        $this->root = sys_get_temp_dir().'/imperium-citadel-proof-'.bin2hex(random_bytes(12));
        mkdir($this->root, 0700);
        $this->clock = new SyntheticFormationClock();
        $this->transport = new SyntheticFormationTransport();
        $c = $this->container = new ContainerBuilder();
        $c->register(Clock::class, SyntheticFormationClock::class)->setSynthetic(true)->setPublic(true);
        $c->register(BoundedFormationTransport::class, SyntheticFormationTransport::class)->setSynthetic(true)->setPublic(true);
        $c->register(FormationInstitution::class)->setArguments([$this->root])->setPublic(true);
        $c->register(FormationJournal::class)->setArguments([$this->root])->setPublic(true);
        $c->register(\App\Imperium\Runtime\Curia\ReceivingFormationHandoffService::class)->setArguments([$this->root, new Reference(FormationJournal::class)]);
        $c->register(ProviderResponseEnvelopeService::class)->setArguments([$this->root])->setPublic(true);
        foreach ([FormationSignatures::class, FormationPersonnel::class, FormationCognition::class, FormationSessionLeaseService::class, CitadelIntakeService::class, CitadelFormationCommand::class, CitadelIntakeCommand::class] as $class) {
            $c->register($class)->setAutowired(true)->setPublic(true);
        }
        $c->register(CuriaFormationService::class)->setArguments([$this->root, new Reference(FormationJournal::class), new Reference(FormationSignatures::class), new Reference(FormationPersonnel::class), new Reference(Clock::class)])->setPublic(true);
        $c->compile();
        $c->set(Clock::class, $this->clock);
        $c->set(BoundedFormationTransport::class, $this->transport);
        $this->journal = $c->get(FormationJournal::class);
        $this->signatures = $c->get(FormationSignatures::class);
        $this->personnel = $c->get(FormationPersonnel::class);
        $this->cognition = $c->get(FormationCognition::class);
        $this->formation = $c->get(CuriaFormationService::class);
        $this->intake = $c->get(CitadelIntakeService::class);
        // Establish synthetic pre-existing institutional incumbents through the real
        // root-installation producer, exclusively in this isolated root.
        $members = [];
        foreach (FormationInstitution::SEATS as $seat) {
            $office = explode('.', $seat)[0];
            $role = substr($seat, strlen($office) + 1);
            $members[] = ['personnel_type' => 'OFFICER', 'office' => $office, 'role' => $role, 'seat' => $seat,
                'persona' => ['id' => 'synthetic-persona-'.$seat, 'version' => '1'],
                'profile' => ['id' => 'synthetic-profile-'.$seat, 'version' => '1'],
                'officer' => ['id' => 'synthetic-officer-'.$seat, 'version' => '1']];
        }
        (new \App\Imperium\Runtime\Bootstrap\OperatorRootPersonnelInstallationService($this->root))->install([
            'schema' => 'imperium.operator-root-personnel-package/v3', 'instance_id' => 'synthetic-parent-imperium', 'personnel' => $members]);
        $pair = sodium_crypto_sign_keypair();
        $this->secret = sodium_crypto_sign_secretkey($pair);
        $public = sodium_crypto_sign_publickey($pair);
        $this->signatures->enrollPublicTrust(['public_key' => base64_encode($public), 'not_before' => $this->clock->at - 1, 'expires_at' => $this->clock->at + 86400], hash('sha256', $public));
    }

    public function sign(string $effect, mixed $object): array
    {
        $state = $this->journal->read()['state'];
        $payload = ['schema' => 'imperium.citadel-owner-decision/v1', 'citadel_id' => $state['citadel_id'],
            'trust_fingerprint' => $state['trust']['fingerprint'], 'effect' => $effect,
            'object_digest' => FormationJournal::digest($object), 'issued_at' => $this->clock->at,
            'expires_at' => $this->clock->at + 3600, 'nonce' => bin2hex(random_bytes(24))];
        return ['payload' => $payload, 'signature' => base64_encode(sodium_crypto_sign_detached(CanonicalJson::encode($payload), $this->secret))];
    }

    public function run(string $operation, array $arguments): mixed
    {
        $path = $this->root.'/command.json';
        file_put_contents($path, json_encode(['operation' => $operation, 'arguments' => $arguments], JSON_THROW_ON_ERROR));
        $tester = new CommandTester($this->container->get(CitadelFormationCommand::class));
        $status = $tester->execute(['request-file' => $path]);
        if ($status !== 0) { throw new \RuntimeException(trim($tester->getDisplay())); }
        return json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR)['result'];
    }

    /** Sign only with this isolated fixture's ephemeral key; never a production signer. */
    public function signPrepared(array $packet): array
    {
        $bytes = base64_decode($packet['signing_bytes_base64'], true);
        if ($bytes !== CanonicalJson::encode($packet['payload']) || hash('sha256', $bytes) !== $packet['signing_bytes_sha256']
            || FormationJournal::digest($packet['object']) !== $packet['payload']['object_digest']) {
            throw new \RuntimeException('Synthetic signing packet mismatch');
        }
        return ['payload' => $packet['payload'], 'signature' => base64_encode(sodium_crypto_sign_detached($bytes, $this->secret))];
    }

    public function receive(string $text = "  Synthetic mission request.\r\nPreserve exact bytes.\n", string $id = 'synthetic-request-0001'): array
    {
        $path = $this->root.'/request.txt';
        file_put_contents($path, $text);
        $tester = new CommandTester($this->container->get(CitadelIntakeCommand::class));
        if ($tester->execute(['submission-id' => $id, 'request-file' => $path]) !== 0) { throw new \RuntimeException($tester->getDisplay()); }
        return json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function candidate(string $scope, string $seat, string $suffix = 'one'): array
    {
        $identity = ['persona_id' => 'persona-'.$seat.'-'.$suffix, 'persona_version' => '1.0',
            'persona_digest' => 'sha256:'.hash('sha256', $seat.$suffix), 'admission_state' => 'admitted', 'evidence_record' => 'synthetic-admission-'.$suffix];
        $persona = $this->evidence('garrison', $scope, 'ADMITTED_PERSONA', $identity['persona_id'], [], ['identity' => $identity]);
        $suitable = $this->evidence('guildhall', $scope, 'SUITABLE_CANDIDATE', $identity['persona_id'], [$persona], ['seat' => $seat, 'rationale' => 'Synthetic Guildhall suitability determination.']);
        $artifact = ['contract_version' => '1.0.0', 'profile_id' => 'profile-'.$seat.'-'.$suffix, 'profile_version' => '1.0',
            'artifact_class' => 'officer', 'source_persona' => $identity, 'steward' => ['kind' => 'office', 'id' => 'laboratorium'],
            'target' => ['kind' => 'seat', 'id' => $seat],
            'transformation' => ['case_id' => 'synthetic-case-'.$suffix, 'specification_version' => '1', 'alchemist_disposition_id' => 'synthetic-derived-'.$suffix],
            'cognitive_payload' => ['instructions' => 'Synthetic '.$seat.' Profile: preserve intent and dissent; respect exact grants; never execute.'],
            'qualification_contract' => ['contract_id' => 'citadel-formation-officer/v1', 'criteria' => ['exact_identity', 'bounded_authority', 'return_contract']],
            'lineage' => ['derived_from' => $identity['persona_digest']],
            'digest_spec' => ['algorithm' => 'sha256', 'canonicalization' => 'rfc8785', 'omitted_fields' => ['content_digest']]];
        $artifact['content_digest'] = 'sha256:'.FormationJournal::digest($artifact);
        $profile = $this->evidence('laboratorium', $scope, 'DERIVED_PROFILE', $artifact['profile_id'], [$persona, $suitable], ['seat' => $seat, 'artifact' => $artifact]);
        $findings = [];
        foreach (['consistency', 'governance', 'practice', 'security'] as $criterion) {
            $findings[$criterion] = $this->evidence('senate-'.$criterion, $scope, 'SENATOR_FINDING', $artifact['profile_id'], [$profile],
                ['disposition' => 'PASS', 'rationale' => 'Synthetic '.$criterion.' examination of the exact Profile.']);
        }
        $examination = $this->evidence('senate', $scope, 'EXAMINED_PROFILE', $artifact['profile_id'], [$profile, ...array_values($findings)],
            ['disposition' => 'APPROVED', 'security_block' => false, 'findings' => $findings]);
        $approval = $this->sign('APPROVE_FORMATION_PROFILE', ['profile' => $profile, 'examination' => $examination, 'scope' => $scope, 'seat' => $seat]);
        $qualification = $this->evidence('conscription', $scope, 'QUALIFIED_MANIFESTATION', $artifact['profile_id'], [$persona, $suitable, $profile, $examination],
            ['seat' => $seat, 'disposition' => 'QUALIFIED', 'profile_approval_digest' => FormationJournal::digest($approval),
                'criteria_results' => array_fill_keys($artifact['qualification_contract']['criteria'], true)]);
        return ['persona' => $persona, 'suitability' => $suitable, 'profile' => $profile, 'examination' => $examination, 'qualification' => $qualification, 'profile_approval' => $approval];
    }

    public function appoint(string $suffix = 'one'): array
    {
        $state = $this->journal->read()['state'];
        if (!isset($state['locksmith'])) {
            $locksmith = $this->candidate($state['citadel_id'], 'clavium.locksmith');
            $terms = ['candidate' => $locksmith, 'scope' => $state['citadel_id'], 'seat' => 'clavium.locksmith', 'generation' => 1];
            $this->run('appoint-locksmith', ['candidate' => $locksmith, 'decision' => $this->sign('APPOINT_FORMATION_LOCKSMITH', $terms)]);
        }
        $candidate = $this->candidate($state['citadel_id'], 'citadel.castellan', $suffix);
        $terms = ['candidate' => $candidate, 'scope' => $state['citadel_id'], 'seat' => 'citadel.castellan', 'generation' => ($state['castellan']['generation'] ?? 0) + 1];
        return $this->run('appoint-castellan', ['candidate' => $candidate, 'decision' => $this->sign('APPOINT_CASTELLAN', $terms)]);
    }

    public function terms(string $id, string $phase): array
    {
        return ['source' => $this->run('authorization-source', ['intakeId' => $id, 'phase' => $phase]),
            'provider' => 'synthetic-offline', 'model' => 'scripted-v1', 'destination' => 'in-process:no-network',
            'pricing' => ['input_microusd_per_token' => 1, 'output_microusd_per_token' => 1],
            'per_call' => ['calls' => 1, 'input_tokens' => 1000000, 'output_tokens' => 100000, 'cost_microusd' => 1100000, 'milliseconds' => 1000],
            'total' => ['calls' => 5, 'input_tokens' => 5000000, 'output_tokens' => 500000, 'cost_microusd' => 5500000, 'milliseconds' => 5000],
            'visible_intakes' => array_keys($this->journal->read()['state']['intakes']),
            'disclosure' => 'Only the evolving synthetic exchange, exact Profile and listed registry intakes may reach the in-process fake.',
            'expires_at' => $this->clock->at + 600];
    }

    public function grant(string $id, string $phase, ?array $terms = null): string
    {
        $terms ??= $this->terms($id, $phase);
        $effect = match ($phase) { 'interview' => 'AUTHORIZE_INTERVIEW_SESSION', 'drafting' => 'AUTHORIZE_EXACT_DRAFTING', 'acceptance' => 'AUTHORIZE_RECEIVING_ASSESSMENT' };
        return $this->run('grant', ['intakeId' => $id, 'phase' => $phase, 'terms' => $terms, 'decision' => $this->sign($effect, $terms)]);
    }

    public function understand(string $id): array
    {
        $session = $this->grant($id, 'interview');
        $this->transport->response = self::understanding();
        return $this->run('call', ['sessionId' => $session, 'attemptId' => 'understanding-0001']);
    }

    public static function understanding(): array
    {
        return ['disposition' => 'UNDERSTOOD', 'understood_intent' => 'I understand the synthetic bounded objective.', 'question' => '',
            'dissent' => 'I disagree with the assumed deadline.', 'unknowns' => 'No live competence or readiness established.',
            'overlap' => 'The disclosed synthetic registry shows no conflicting commitment; independent work is deliberate.', 'ready_to_request_drafting' => true];
    }

    public function draft(string $id): array
    {
        $this->run('drafting-request', ['intakeId' => $id, 'charter' => ['scope' => 'Draft from present synthetic material only.',
            'questions' => 'Express the bounded requested plan.', 'inputs' => 'Exact interview and authorized registry context.',
            'offices' => [], 'external_effects' => [], 'disclosure' => 'Synthetic material to the same explicitly authorized fake.',
            'expected_return' => 'One numbered Mission Plan with complete Step 1 fields.', 'stop_conditions' => 'Stop on scope change.',
            'amendment_triggers' => 'New evidence or external investigation.', 'retention' => 'Preserve versions for review.', 'expires_at' => $this->clock->at + 600]]);
        $session = $this->grant($id, 'drafting');
        $this->transport->response = self::plan();
        return $this->run('call', ['sessionId' => $session, 'attemptId' => 'drafting-000001']);
    }

    public static function plan(): array
    {
        $plan = ['objective' => 'Prepare a bounded synthetic report.'];
        foreach (['scope', 'deliverables', 'constraints', 'required_inputs', 'capability_requirements', 'expected_outcomes', 'data_requirements', 'tool_requirements', 'credential_requirements', 'perimeter_requirements', 'stop_conditions', 'return_conditions', 'unbinding_conditions', 'custody_restoration_conditions', 'retirement_conditions'] as $field) { $plan[$field] = ['Synthetic '.$field.'; no real effects.']; }
        $plan['mission_seat'] = 'mission.synthetic.delegate';
        $plan['bounded_duration'] = ['maximum' => 1, 'unit' => 'hours', 'starts_when' => 'Separately commissioned later', 'expires_when' => 'One hour after authorized start'];
        return ['mission_plan' => $plan, 'disclosures' => array_fill_keys(FormationPlan::DISCLOSURES, ['Synthetic explicit disclosure; no live activation.']), 'formation_dependencies' => 'Exact eligible synthetic Seneschal, Chamberlain and Isolde appointments required.'];
    }

    public function present(string $id, int $version = 1): array
    {
        $state = $this->journal->read()['state'];
        $mission = 'mission-'.substr(FormationJournal::digest([$state['citadel_id'], $id]), 0, 32);
        $appointments = [];
        foreach (['curia.seneschal', 'curia.chamberlain', 'curia.secretary'] as $seat) { $appointments[$seat] = $this->candidate($mission, $seat, substr($id, -8)); }
        return $this->run('present-mission', ['intakeId' => $id, 'version' => $version, 'appointments' => $appointments, 'expiresAt' => $this->clock->at + 600]);
    }

    public function approve(array $terms): array
    {
        $lines = array_column($terms['dossier']['lines'], 'line_digest');
        $rationale = 'Approve exact synthetic terms including separately typed constitution and appointments.';
        return $this->run('review-mission', ['terms' => $terms, 'disposition' => 'APPROVE', 'lineDigests' => $lines, 'rationale' => $rationale,
            'decision' => $this->sign('APPROVE_MISSION_AND_CONSTITUTION', ['terms' => $terms, 'line_digests' => $lines, 'rationale' => $rationale])]);

    }

    private function evidence(string $role, string $scope, string $kind, string $subject, array $sources, array $content): string
    {
        $key = $role.'|'.$scope;
        if (!isset($this->delegates[$key])) {
            $pair = sodium_crypto_sign_keypair();
            $terms = ['role' => $role, 'public_key' => base64_encode(sodium_crypto_sign_publickey($pair)), 'scope' => $scope, 'expires_at' => $this->clock->at + 3600, 'actor' => $this->run('personnel-authority-source', ['role' => $role])];
            $delegation = $this->run('delegate-personnel-evidence', ['delegation' => $terms, 'decision' => $this->sign('DELEGATE_PERSONNEL_EVIDENCE', $terms)]);
            $this->delegates[$key] = [$delegation, sodium_crypto_sign_secretkey($pair)];
        }
        [$delegation, $secret] = $this->delegates[$key];
        $payload = ['delegation' => $delegation, 'scope' => $scope, 'kind' => $kind, 'subject' => $subject, 'sources' => $sources, 'content' => $content, 'expires_at' => $this->clock->at + 3600];
        return $this->run('record-personnel-evidence', ['envelope' => ['payload' => $payload, 'signature' => base64_encode(sodium_crypto_sign_detached(CanonicalJson::encode($payload), $secret))]]);
    }

    public function close(): void
    {
        $resolved = realpath($this->root);
        if ($resolved === false || !str_starts_with(str_replace('\\', '/', $resolved), str_replace('\\', '/', realpath(sys_get_temp_dir())).'/imperium-citadel-proof-')) { throw new \RuntimeException('Synthetic root cleanup refused.'); }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($resolved, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) { $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
        rmdir($resolved);
        sodium_memzero($this->secret);
    }
}

final class SyntheticFormationClock implements Clock
{
    public int $at = 1788796800;
    public function now(): \DateTimeImmutable { return new \DateTimeImmutable('@'.$this->at); }
}

final class SyntheticFormationTransport implements BoundedFormationTransport
{
    public array $response = [];
    public array $calls = [];
    public bool $unknown = false;
    public ?\Closure $duringCall = null;

    public function inspect(array $request, array $terms): array
    {
        if ($terms['provider'] !== 'synthetic-offline' || $terms['model'] !== 'scripted-v1'
            || $terms['destination'] !== 'in-process:no-network'
            || $terms['pricing'] !== ['input_microusd_per_token' => 1, 'output_microusd_per_token' => 1]) { throw new \RuntimeException('FAKE_UNSUPPORTED_LIMITS_OR_PRICING'); }
        $input = strlen(CanonicalJson::encode($request));
        return ['calls' => 1, 'input_tokens' => $input, 'output_tokens' => 100000, 'cost_microusd' => $input + 100000, 'milliseconds' => 1000];
    }

    public function invoke(array $claim, array $request, array $terms): array
    {
        $maximum = $this->inspect($request, $terms);
        if ($claim['request_digest'] !== FormationJournal::digest($request) || $maximum !== $claim['maximum']) { throw new \RuntimeException('FAKE_TRANSPORT_BINDING_INVALID'); }
        $this->calls[] = ['claim' => $claim, 'request' => $request, 'terms' => $terms];
        if ($this->duringCall) { ($this->duringCall)(); }
        if ($this->unknown) { throw new \RuntimeException('Synthetic unknown outcome.'); }
        $response = json_encode($this->response, JSON_THROW_ON_ERROR);
        if (strlen($response) > $maximum['output_tokens']) { throw new \RuntimeException('FAKE_OUTPUT_LIMIT'); }
        return ['response' => $response, 'usage' => ['calls' => 1, 'input_tokens' => $maximum['input_tokens'], 'output_tokens' => strlen($response), 'cost_microusd' => $maximum['input_tokens'] + strlen($response), 'milliseconds' => 1], 'provider_response_id' => 'synthetic-response-'.count($this->calls)];
    }
}
