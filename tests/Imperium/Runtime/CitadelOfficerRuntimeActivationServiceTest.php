<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Conscription\CitadelOfficerRuntimeActivationService;
use PHPUnit\Framework\TestCase;

final class CitadelOfficerRuntimeActivationServiceTest extends TestCase
{
    public function testMechanicallyActivatesExactBindingForCommissionIntakeOnly(): void
    {
        $root = sys_get_temp_dir().'/imperium-citadel-runtime-'.bin2hex(random_bytes(5));
        try {
            $decisionId = $this->fixtures($root, true, '2026-08-24T20:00:00+00:00');
            $service = new CitadelOfficerRuntimeActivationService($root, new StateStore($root));
            $at = new \DateTimeImmutable('2026-08-23T20:00:00+00:00');
            $activation = $service->activate($decisionId, $at);
            self::assertSame('MODEL_BOUND_CITADEL_MANIFESTATION_RUNTIME_ACTIVE_PENDING_GOVERNED_COMMISSION', $activation['status']);
            self::assertTrue($activation['runtime_active']);
            self::assertTrue($activation['commission_intake_available']);
            self::assertSame('foundry.artificer', $activation['seat']);
            self::assertSame(1, $activation['occupancy_generation']);
            self::assertTrue($activation['runtime_activation_authority']['consumed']);
            self::assertFalse($activation['runtime_activation_authority']['continuing_authority']);
            foreach (['operational_use_permitted', 'autonomous_cognition_authority', 'governed_cognition_authority', 'tool_use_authority', 'credential_use_authority', 'provider_invocation_authority', 'external_action_authority', 'execution_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($activation[$field]);
            }
            self::assertSame($activation, $service->activate($decisionId, $at->modify('+1 hour')));
        } finally {
            $this->remove($root);
        }
    }

    public function testNonAuthorizingDecisionCannotActivateRuntime(): void
    {
        $root = sys_get_temp_dir().'/imperium-citadel-runtime-refused-'.bin2hex(random_bytes(5));
        try {
            $decisionId = $this->fixtures($root, false, '2026-08-24T20:00:00+00:00');
            $this->expectExceptionMessage('R233_CITADEL_RUNTIME_ACTIVATION_CHAIN_INVALID');
            (new CitadelOfficerRuntimeActivationService($root, new StateStore($root)))->activate($decisionId, new \DateTimeImmutable('2026-08-23T20:00:00+00:00'));
        } finally {
            $this->remove($root);
        }
    }

    public function testExpiredAccessCannotActivateRuntime(): void
    {
        $root = sys_get_temp_dir().'/imperium-citadel-runtime-expired-'.bin2hex(random_bytes(5));
        try {
            $decisionId = $this->fixtures($root, true, '2026-08-23T19:59:59+00:00');
            $this->expectExceptionMessage('R233_CITADEL_RUNTIME_ACTIVATION_CHAIN_INVALID');
            (new CitadelOfficerRuntimeActivationService($root, new StateStore($root)))->activate($decisionId, new \DateTimeImmutable('2026-08-23T20:00:00+00:00'));
        } finally {
            $this->remove($root);
        }
    }

    private function fixtures(string $root, bool $authorized, string $expiresAt): string
    {
        $profile = ['profile_id' => 'profile-foundry-artificer', 'content_digest' => 'sha256:'.str_repeat('a', 64), 'target' => ['kind' => 'seat', 'id' => 'foundry.artificer']];
        $assemblyId = 'model-bound-operational-manifestation-assembly-'.str_repeat('b', 20);
        $assembly = $this->record(['schema' => 'imperium.conscription-model-bound-operational-manifestation-assembly/v1', 'assembly_id' => $assemblyId, 'sealed' => true]);
        $qualificationId = 'model-bound-operational-profile-qualification-'.str_repeat('c', 20);
        $qualification = $this->record(['schema' => 'imperium.conscription-model-bound-operational-profile-qualification/v1', 'qualification_id' => $qualificationId, 'sealed' => true]);
        $approvalId = 'model-bound-profile-approval-decision-'.str_repeat('d', 20);
        $approval = $this->record(['schema' => 'imperium.imperator-model-bound-profile-approval-decision/v1', 'decision_id' => $approvalId, 'sealed' => true]);
        $modelBindingId = 'profile-model-binding-'.str_repeat('e', 20);
        $modelBinding = $this->record(['schema' => 'imperium.conscription-profile-model-binding/v1', 'binding_id' => $modelBindingId, 'sealed_profile' => $profile, 'sealed' => true]);
        $attestationId = 'profile-model-access-attestation-'.str_repeat('f', 20);
        $attestation = $this->record(['schema' => 'imperium.clavium-profile-model-access-attestation/v1', 'attestation_id' => $attestationId, 'status' => 'ACCESS_AVAILABLE', 'provider_access_evidence' => ['expires_at' => $expiresAt], 'sealed' => true]);
        $bindingId = 'model-bound-operational-seat-binding-'.str_repeat('1', 20);
        $manifestation = ['manifestation_id' => 'manifestation-artificer', 'profile' => $profile, 'intended_seat' => $profile['target']];
        $binding = $this->record(['schema' => 'imperium.model-bound-operational-manifestation-seat-binding/v1', 'binding_id' => $bindingId, 'instance_id' => 'imperium-test', 'case_id' => 'case', 'case_digest' => str_repeat('2', 64), 'seat' => 'foundry.artificer', 'manifestation_id' => 'manifestation-artificer', 'manifestation' => $manifestation, 'source_assembly' => ['id' => $assemblyId, 'digest' => $assembly['record_digest']], 'source_qualification' => ['id' => $qualificationId, 'digest' => $qualification['record_digest']], 'source_imperator_approval' => ['id' => $approvalId, 'digest' => $approval['record_digest']], 'source_model_binding' => ['id' => $modelBindingId, 'digest' => $modelBinding['record_digest']], 'source_access_attestation' => ['id' => $attestationId, 'digest' => $attestation['record_digest']], 'occupancy_generation' => 1, 'status' => 'OPERATIONAL_MANIFESTATION_BOUND_PENDING_DEPLOYMENT_AUTHORIZATION', 'binding_atomic' => true, 'seat_bound' => true, 'sealed' => true]);
        $decisionId = 'citadel-officer-activation-authorization-decision-'.str_repeat('3', 20);
        $decision = $this->record(['schema' => 'imperium.imperator-citadel-officer-activation-authorization-decision/v1', 'decision_id' => $decisionId, 'instance_id' => 'imperium-test', 'case_id' => 'case', 'case_digest' => str_repeat('2', 64), 'source_seat_binding' => ['id' => $bindingId, 'digest' => $binding['record_digest']], 'source_assembly' => ['id' => $assemblyId, 'digest' => $assembly['record_digest']], 'source_qualification' => ['id' => $qualificationId, 'digest' => $qualification['record_digest']], 'source_profile_approval' => ['id' => $approvalId, 'digest' => $approval['record_digest']], 'source_model_binding' => ['id' => $modelBindingId, 'digest' => $modelBinding['record_digest']], 'source_access_attestation' => ['id' => $attestationId, 'digest' => $attestation['record_digest']], 'seat' => 'foundry.artificer', 'manifestation_id' => 'manifestation-artificer', 'occupancy_generation' => 1, 'manifestation' => $manifestation, 'disposition' => $authorized ? 'AUTHORIZED' : 'REFUSED', 'status' => $authorized ? 'MODEL_BOUND_MANIFESTATION_ACTIVATION_AUTHORIZED_PENDING_RUNTIME_ACTIVATION' : 'NON_AUTHORIZING_CITADEL_ACTIVATION_DISPOSITION_RECORDED', 'activation_authorized' => $authorized, 'runtime_activation_authority' => $authorized ? ['authority_id' => 'citadel-runtime-activation-authority-'.str_repeat('4', 20), 'authority_single_use' => true, 'destination' => 'conscription.recruiter', 'purpose' => 'ACTIVATE_EXACT_BOUND_CITADEL_MANIFESTATION', 'consumed' => false] : null, 'runtime_active' => false, 'operational_use_permitted' => false, 'provider_invocation_authority' => false, 'execution_authority' => false, 'sealed' => true]);
        $this->write($root.'/var/imperium/offices/conscription/model-bound-operational-manifestation-assemblies/'.$assemblyId.'.json', $assembly);
        $this->write($root.'/var/imperium/offices/conscription/model-bound-operational-profile-qualifications/'.$qualificationId.'.json', $qualification);
        $this->write($root.'/var/imperium/imperator/model-bound-profile-approval-decisions/'.$approvalId.'.json', $approval);
        $this->write($root.'/var/imperium/offices/conscription/profile-model-bindings/'.$modelBindingId.'.json', $modelBinding);
        $this->write($root.'/var/imperium/offices/clavium/profile-model-access-attestations/'.$attestationId.'.json', $attestation);
        $this->write($root.'/var/imperium/operational/occupancy/'.$bindingId.'.json', $binding);
        $this->write($root.'/var/imperium/imperator/citadel-officer-activation-authorization-decisions/'.$decisionId.'.json', $decision);
        if (!is_dir($root.'/var/imperium')) {
            mkdir($root.'/var/imperium', 0770, true);
        }
        (new StateStore($root))->write(['state' => 'CURIA_READY', 'binding' => ['instance_id' => 'imperium-test'], 'events' => [['transition' => 'T04', 'result' => 'SUCCESS', 'output' => ['successor' => ['manifestation_id' => 'recruiter-1', 'seat' => 'conscription.recruiter', 'occupancy_generation' => 2, 'authority' => 'ordinary-recruiter']]]]]);
        return $decisionId;
    }

    private function record(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        return $record;
    }

    private function write(string $path, array $record): void
    {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0770, true);
        }
        file_put_contents($path, json_encode($record, JSON_THROW_ON_ERROR));
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->remove($child) : unlink($child);
        }
        rmdir($path);
    }
}
