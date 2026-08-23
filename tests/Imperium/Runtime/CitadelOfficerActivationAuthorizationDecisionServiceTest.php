<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\CitadelOfficerActivationAuthorizationDecisionService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CitadelOfficerActivationAuthorizationDecisionServiceTest extends TestCase
{
    public function testAuthorizationIsExactReplaySafeAndGrantsOnlyRuntimeActivationAuthority(): void
    {
        $root = sys_get_temp_dir().'/imperium-citadel-activation-'.bin2hex(random_bytes(5));
        try {
            $bindingId = $this->fixtures($root, '2026-08-24T20:00:00+00:00');
            $service = new CitadelOfficerActivationAuthorizationDecisionService($root);
            $at = new \DateTimeImmutable('2026-08-23T20:00:00+00:00');
            $decision = $service->decide($bindingId, 'AUTHORIZED', 'Authorize exact Citadel Officer activation.', 'Activation only.', $at);
            self::assertSame('MODEL_BOUND_MANIFESTATION_ACTIVATION_AUTHORIZED_PENDING_RUNTIME_ACTIVATION', $decision['status']);
            self::assertTrue($decision['activation_authorized']);
            self::assertSame('conscription.recruiter', $decision['runtime_activation_authority']['destination']);
            self::assertTrue($decision['runtime_activation_authority']['authority_single_use']);
            self::assertFalse($decision['runtime_activation_authority']['consumed']);
            self::assertSame('foundry.artificer', $decision['seat']);
            foreach (['runtime_active', 'operational_use_permitted', 'tool_use_authority', 'credential_use_authority', 'provider_invocation_authority', 'external_action_authority', 'execution_authority', 'continuing_cognition_authority'] as $field) {
                self::assertFalse($decision[$field]);
            }
            self::assertSame($decision, $service->decide($bindingId, 'AUTHORIZED', 'Authorize exact Citadel Officer activation.', 'Activation only.', $at));
            $this->expectExceptionMessage('I245_CITADEL_ACTIVATION_DECISION_CONFLICT');
            $service->decide($bindingId, 'DEFERRED', 'Defer.', 'No authority.', $at);
        } finally {
            $this->remove($root);
        }
    }

    #[DataProvider('nonAuthorizingDispositions')]
    public function testNonAuthorizingBranchesRemainSealedAndInert(string $disposition): void
    {
        $root = sys_get_temp_dir().'/imperium-citadel-nonauthorization-'.bin2hex(random_bytes(5));
        try {
            $bindingId = $this->fixtures($root, '2026-08-24T20:00:00+00:00');
            $decision = (new CitadelOfficerActivationAuthorizationDecisionService($root))->decide($bindingId, $disposition, 'Record non-authorization.', 'No activation authority.', new \DateTimeImmutable('2026-08-23T20:00:00+00:00'));
            self::assertSame('NON_AUTHORIZING_CITADEL_ACTIVATION_DISPOSITION_RECORDED', $decision['status']);
            self::assertFalse($decision['activation_authorized']);
            self::assertNull($decision['runtime_activation_authority']);
            self::assertFalse($decision['runtime_active']);
            self::assertFalse($decision['execution_authority']);
            self::assertTrue($decision['sealed']);
        } finally {
            $this->remove($root);
        }
    }

    public static function nonAuthorizingDispositions(): array
    {
        return [['REFUSED'], ['RETURNED_FOR_REVISION'], ['ALTERNATIVE_PROPOSED'], ['CLARIFICATION_REQUIRED'], ['DEFERRED']];
    }

    public function testExpiredAccessAttestationPreventsAuthorization(): void
    {
        $root = sys_get_temp_dir().'/imperium-citadel-expired-'.bin2hex(random_bytes(5));
        try {
            $bindingId = $this->fixtures($root, '2026-08-23T19:59:59+00:00');
            $this->expectExceptionMessage('I244_CITADEL_ACTIVATION_CHAIN_INVALID');
            (new CitadelOfficerActivationAuthorizationDecisionService($root))->decide($bindingId, 'AUTHORIZED', 'Attempt.', 'Activation only.', new \DateTimeImmutable('2026-08-23T20:00:00+00:00'));
        } finally {
            $this->remove($root);
        }
    }

    private function fixtures(string $root, string $expiresAt): string
    {
        $profile = ['profile_id' => 'profile-foundry-artificer', 'profile_version' => '1.1.0', 'content_digest' => 'sha256:'.str_repeat('a', 64), 'target' => ['kind' => 'seat', 'id' => 'foundry.artificer']];
        $assemblyId = 'model-bound-operational-manifestation-assembly-'.str_repeat('b', 20);
        $assembly = $this->record(['schema' => 'imperium.conscription-model-bound-operational-manifestation-assembly/v1', 'assembly_id' => $assemblyId, 'status' => 'OPERATIONAL_MANIFESTATION_ASSEMBLED_PENDING_SEAT_BINDING', 'sealed' => true]);
        $qualificationId = 'model-bound-operational-profile-qualification-'.str_repeat('c', 20);
        $qualification = $this->record(['schema' => 'imperium.conscription-model-bound-operational-profile-qualification/v1', 'qualification_id' => $qualificationId, 'status' => 'PROFILE_OPERATIONALLY_QUALIFIED_PENDING_MANIFESTATION_ASSEMBLY', 'sealed' => true]);
        $approvalId = 'model-bound-profile-approval-decision-'.str_repeat('d', 20);
        $approval = $this->record(['schema' => 'imperium.imperator-model-bound-profile-approval-decision/v1', 'decision_id' => $approvalId, 'disposition' => 'APPROVED', 'profile_approved' => true, 'sealed' => true]);
        $modelBindingId = 'profile-model-binding-'.str_repeat('e', 20);
        $modelBinding = $this->record(['schema' => 'imperium.conscription-profile-model-binding/v1', 'binding_id' => $modelBindingId, 'sealed_profile' => $profile, 'sealed' => true]);
        $attestationId = 'profile-model-access-attestation-'.str_repeat('f', 20);
        $attestation = $this->record(['schema' => 'imperium.clavium-profile-model-access-attestation/v1', 'attestation_id' => $attestationId, 'status' => 'ACCESS_AVAILABLE', 'provider_access_evidence' => ['expires_at' => $expiresAt], 'sealed' => true]);
        $bindingId = 'model-bound-operational-seat-binding-'.str_repeat('1', 20);
        $manifestation = ['manifestation_id' => 'manifestation-artificer', 'profile' => $profile, 'intended_seat' => $profile['target'], 'status' => 'ASSEMBLED_UNBOUND'];
        $binding = $this->record(['schema' => 'imperium.model-bound-operational-manifestation-seat-binding/v1', 'binding_id' => $bindingId, 'instance_id' => 'imperium-test', 'case_id' => 'case', 'case_digest' => str_repeat('2', 64), 'seat' => 'foundry.artificer', 'manifestation_id' => 'manifestation-artificer', 'manifestation' => $manifestation, 'source_assembly' => ['id' => $assemblyId, 'digest' => $assembly['record_digest']], 'source_qualification' => ['id' => $qualificationId, 'digest' => $qualification['record_digest']], 'source_imperator_approval' => ['id' => $approvalId, 'digest' => $approval['record_digest']], 'source_model_binding' => ['id' => $modelBindingId, 'digest' => $modelBinding['record_digest']], 'source_access_attestation' => ['id' => $attestationId, 'digest' => $attestation['record_digest']], 'prior_occupancy_generation' => 0, 'occupancy_generation' => 1, 'supersedes' => null, 'status' => 'OPERATIONAL_MANIFESTATION_BOUND_PENDING_DEPLOYMENT_AUTHORIZATION', 'binding_atomic' => true, 'seat_bound' => true, 'supersession_authority' => false, 'operational_use_permitted' => false, 'deployment_authority' => false, 'custody_transfer_authority' => false, 'tool_use_authority' => false, 'credential_use_authority' => false, 'external_action_authority' => false, 'execution_authority' => false, 'sealed' => true]);
        $this->write($root.'/var/imperium/offices/conscription/model-bound-operational-manifestation-assemblies/'.$assemblyId.'.json', $assembly);
        $this->write($root.'/var/imperium/offices/conscription/model-bound-operational-profile-qualifications/'.$qualificationId.'.json', $qualification);
        $this->write($root.'/var/imperium/imperator/model-bound-profile-approval-decisions/'.$approvalId.'.json', $approval);
        $this->write($root.'/var/imperium/offices/conscription/profile-model-bindings/'.$modelBindingId.'.json', $modelBinding);
        $this->write($root.'/var/imperium/offices/clavium/profile-model-access-attestations/'.$attestationId.'.json', $attestation);
        $this->write($root.'/var/imperium/operational/occupancy/'.$bindingId.'.json', $binding);
        return $bindingId;
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
