<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\CitadelGovernedCommissionIssuanceService;
use PHPUnit\Framework\TestCase;

final class CitadelGovernedCommissionIssuanceServiceTest extends TestCase
{
    public function testAuthorizedOccupiedCallerIssuesExactPendingCommission(): void
    {
        $root = sys_get_temp_dir().'/imperium-citadel-commission-'.bin2hex(random_bytes(5));
        try {
            [$activationId, $issuerBindingId] = $this->fixtures($root, true, '2026-08-24T20:00:00+00:00');
            $service = new CitadelGovernedCommissionIssuanceService($root);
            $at = new \DateTimeImmutable('2026-08-23T20:00:00+00:00');
            $commission = $service->issue($activationId, $issuerBindingId, $this->contract(), $at);

            self::assertSame('CITADEL_OFFICER_GOVERNED_COMMISSION_ISSUED_PENDING_OFFICER_ACCEPTANCE', $commission['status']);
            self::assertSame('curia.seneschal', $commission['issuer']['seat']);
            self::assertSame('foundry.artificer', $commission['target']['seat']);
            self::assertSame($this->contract(), $commission['contract']);
            self::assertTrue($commission['commission_issued']);
            self::assertTrue($commission['commission_acceptance_authority']['authority_single_use']);
            self::assertFalse($commission['commission_acceptance_authority']['consumed']);
            self::assertFalse($commission['commission_accepted']);
            self::assertFalse($commission['commission_exercisable']);
            foreach (['operational_use_permitted', 'autonomous_cognition_authority', 'governed_cognition_authority', 'tool_use_authority', 'credential_use_authority', 'provider_invocation_authority', 'external_action_authority', 'execution_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($commission[$field]);
            }
            self::assertSame($commission, $service->issue($activationId, $issuerBindingId, $this->contract(), $at->modify('+1 hour')));
        } finally {
            $this->remove($root);
        }
    }

    public function testCallerWithoutExplicitTargetAuthorityCannotIssue(): void
    {
        $root = sys_get_temp_dir().'/imperium-citadel-commission-denied-'.bin2hex(random_bytes(5));
        try {
            [$activationId, $issuerBindingId] = $this->fixtures($root, false, '2026-08-24T20:00:00+00:00');
            $this->expectExceptionMessage('CIT306_GOVERNED_COMMISSION_AUTHORITY_INVALID');
            (new CitadelGovernedCommissionIssuanceService($root))->issue($activationId, $issuerBindingId, $this->contract(), new \DateTimeImmutable('2026-08-23T20:00:00+00:00'));
        } finally {
            $this->remove($root);
        }
    }

    public function testExpiredModelAccessCannotReceiveCommission(): void
    {
        $root = sys_get_temp_dir().'/imperium-citadel-commission-expired-'.bin2hex(random_bytes(5));
        try {
            [$activationId, $issuerBindingId] = $this->fixtures($root, true, '2026-08-23T20:00:00+00:00');
            $this->expectExceptionMessage('CIT306_GOVERNED_COMMISSION_AUTHORITY_INVALID');
            (new CitadelGovernedCommissionIssuanceService($root))->issue($activationId, $issuerBindingId, $this->contract(), new \DateTimeImmutable('2026-08-23T20:00:00+00:00'));
        } finally {
            $this->remove($root);
        }
    }

    public function testIncompleteContractCannotBeIssued(): void
    {
        $root = sys_get_temp_dir().'/imperium-citadel-commission-contract-'.bin2hex(random_bytes(5));
        try {
            [$activationId, $issuerBindingId] = $this->fixtures($root, true, '2026-08-24T20:00:00+00:00');
            $this->expectExceptionMessage('CIT307_GOVERNED_COMMISSION_CONTRACT_INVALID');
            (new CitadelGovernedCommissionIssuanceService($root))->issue($activationId, $issuerBindingId, ['task' => 'Do work.'], new \DateTimeImmutable('2026-08-23T20:00:00+00:00'));
        } finally {
            $this->remove($root);
        }
    }

    private function contract(): array
    {
        return [
            'task' => 'Prepare one evidence-bound construction recommendation.',
            'purpose' => 'Answer the exact commissioned design question.',
            'inputs' => ['sealed-requirement-digest'],
            'evidence_requirements' => ['Reference every supplied input by digest.'],
            'constraints' => ['No tools or external action.'],
            'output_contract' => ['Return one sealed recommendation.'],
            'stop_conditions' => ['Required evidence is absent.'],
        ];
    }

    private function fixtures(string $root, bool $authorized, string $expiresAt): array
    {
        $targetBindingId = 'model-bound-operational-seat-binding-'.str_repeat('a', 20);
        $targetBinding = $this->record([
            'schema' => 'imperium.model-bound-operational-manifestation-seat-binding/v1',
            'binding_id' => $targetBindingId,
            'instance_id' => 'imperium-test',
            'seat' => 'foundry.artificer',
            'manifestation_id' => 'manifestation-artificer',
            'occupancy_generation' => 1,
            'status' => 'OPERATIONAL_MANIFESTATION_BOUND_PENDING_DEPLOYMENT_AUTHORIZATION',
            'binding_atomic' => true,
            'sealed' => true,
        ]);
        $issuerBindingId = 'operational-seat-binding-'.str_repeat('b', 20);
        $issuerBinding = $this->record([
            'schema' => 'imperium.operational-seat-binding/v1',
            'binding_id' => $issuerBindingId,
            'instance_id' => 'imperium-test',
            'seat' => 'curia.seneschal',
            'manifestation_id' => 'manifestation-seneschal',
            'occupancy_generation' => 1,
            'status' => 'ACTIVE',
            'binding_atomic' => true,
            'governed_commission_issuance_authority' => true,
            'commissionable_seats' => $authorized ? ['foundry.artificer'] : ['guildhall.guildmaster'],
            'sealed' => true,
        ]);
        $attestationId = 'profile-model-access-attestation-'.str_repeat('c', 20);
        $attestation = $this->record([
            'schema' => 'imperium.clavium-profile-model-access-attestation/v1',
            'attestation_id' => $attestationId,
            'status' => 'ACCESS_AVAILABLE',
            'provider_access_evidence' => ['expires_at' => $expiresAt],
            'sealed' => true,
        ]);
        $activationId = 'citadel-officer-runtime-activation-'.str_repeat('d', 20);
        $activation = $this->record([
            'schema' => 'imperium.conscription-citadel-officer-runtime-activation/v1',
            'activation_id' => $activationId,
            'instance_id' => 'imperium-test',
            'case_id' => 'case-artificer',
            'case_digest' => str_repeat('e', 64),
            'source_seat_binding' => ['id' => $targetBindingId, 'digest' => $targetBinding['record_digest']],
            'source_access_attestation' => ['id' => $attestationId, 'digest' => $attestation['record_digest']],
            'seat' => 'foundry.artificer',
            'manifestation_id' => 'manifestation-artificer',
            'occupancy_generation' => 1,
            'status' => 'MODEL_BOUND_CITADEL_MANIFESTATION_RUNTIME_ACTIVE_PENDING_GOVERNED_COMMISSION',
            'runtime_active' => true,
            'commission_intake_available' => true,
            'governed_cognition_authority' => false,
            'provider_invocation_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ]);
        $this->write($root.'/var/imperium/operational/occupancy/'.$targetBindingId.'.json', $targetBinding);
        $this->write($root.'/var/imperium/operational/occupancy/'.$issuerBindingId.'.json', $issuerBinding);
        $this->write($root.'/var/imperium/offices/clavium/profile-model-access-attestations/'.$attestationId.'.json', $attestation);
        $this->write($root.'/var/imperium/operational/citadel-runtime-activations/'.$activationId.'.json', $activation);

        return [$activationId, $issuerBindingId];
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
