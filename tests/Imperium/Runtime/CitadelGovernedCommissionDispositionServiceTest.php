<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\CitadelGovernedCommissionDispositionService;
use PHPUnit\Framework\TestCase;

final class CitadelGovernedCommissionDispositionServiceTest extends TestCase
{
    public function testExactTargetOfficerAcceptsWithoutReceivingCognitionAuthority(): void
    {
        $root = sys_get_temp_dir().'/imperium-citadel-accept-'.bin2hex(random_bytes(5));
        try {
            [$commissionId, $bindingId] = $this->fixtures($root, '2026-08-24T20:00:00+00:00');
            $service = new CitadelGovernedCommissionDispositionService($root);
            $at = new \DateTimeImmutable('2026-08-23T20:00:00+00:00');
            $decision = $service->decide($commissionId, $bindingId, 'ACCEPTED', 'The exact contract is understood and bounded.', $at);

            self::assertSame('CITADEL_OFFICER_GOVERNED_COMMISSION_ACCEPTED_PENDING_COGNITION_TURN_AUTHORIZATION', $decision['status']);
            self::assertSame('foundry.artificer', $decision['actor']['seat']);
            self::assertTrue($decision['commission_acceptance_authority']['consumed']);
            self::assertFalse($decision['commission_acceptance_authority']['continuing_authority']);
            self::assertTrue($decision['commission_accepted']);
            self::assertTrue($decision['commission_bound']);
            self::assertTrue($decision['cognition_turn_authorization_required']);
            self::assertFalse($decision['commission_exercisable']);
            foreach (['operational_use_permitted', 'autonomous_cognition_authority', 'governed_cognition_authority', 'tool_use_authority', 'credential_use_authority', 'provider_invocation_authority', 'external_action_authority', 'execution_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($decision[$field]);
            }
            self::assertSame($decision, $service->decide($commissionId, $bindingId, 'ACCEPTED', 'The exact contract is understood and bounded.', $at->modify('+1 hour')));
        } finally {
            $this->remove($root);
        }
    }

    public function testRefusalIsSealedAndCreatesNoAuthority(): void
    {
        $root = sys_get_temp_dir().'/imperium-citadel-refuse-'.bin2hex(random_bytes(5));
        try {
            [$commissionId, $bindingId] = $this->fixtures($root, '2026-08-24T20:00:00+00:00');
            $decision = (new CitadelGovernedCommissionDispositionService($root))->decide($commissionId, $bindingId, 'REFUSED', 'Required evidence is absent.', new \DateTimeImmutable('2026-08-23T20:00:00+00:00'));

            self::assertSame('CITADEL_OFFICER_GOVERNED_COMMISSION_REFUSED_NO_AUTHORITY', $decision['status']);
            self::assertFalse($decision['commission_accepted']);
            self::assertFalse($decision['commission_bound']);
            self::assertFalse($decision['commission_exercisable']);
            self::assertFalse($decision['cognition_turn_authorization_required']);
            self::assertFalse($decision['governed_cognition_authority']);
            self::assertFalse($decision['provider_invocation_authority']);
            self::assertFalse($decision['execution_authority']);
        } finally {
            $this->remove($root);
        }
    }

    public function testDifferentTargetOccupancyCannotDecideCommission(): void
    {
        $root = sys_get_temp_dir().'/imperium-citadel-wrong-target-'.bin2hex(random_bytes(5));
        try {
            [$commissionId] = $this->fixtures($root, '2026-08-24T20:00:00+00:00');
            $otherId = 'model-bound-operational-seat-binding-'.str_repeat('f', 20);
            $this->write($root.'/var/imperium/operational/occupancy/'.$otherId.'.json', $this->record([
                'schema' => 'imperium.model-bound-operational-manifestation-seat-binding/v1',
                'binding_id' => $otherId,
                'instance_id' => 'imperium-test',
                'seat' => 'oracle.augur',
                'manifestation_id' => 'manifestation-augur',
                'occupancy_generation' => 1,
                'status' => 'ACTIVE',
                'binding_atomic' => true,
                'sealed' => true,
            ]));
            $this->expectExceptionMessage('CIT327_GOVERNED_COMMISSION_DISPOSITION_CHAIN_INVALID');
            (new CitadelGovernedCommissionDispositionService($root))->decide($commissionId, $otherId, 'ACCEPTED', 'Accept.', new \DateTimeImmutable('2026-08-23T20:00:00+00:00'));
        } finally {
            $this->remove($root);
        }
    }

    public function testExpiredModelAccessPreventsAcceptance(): void
    {
        $root = sys_get_temp_dir().'/imperium-citadel-accept-expired-'.bin2hex(random_bytes(5));
        try {
            [$commissionId, $bindingId] = $this->fixtures($root, '2026-08-23T20:00:00+00:00');
            $this->expectExceptionMessage('CIT327_GOVERNED_COMMISSION_DISPOSITION_CHAIN_INVALID');
            (new CitadelGovernedCommissionDispositionService($root))->decide($commissionId, $bindingId, 'ACCEPTED', 'Accept.', new \DateTimeImmutable('2026-08-23T20:00:00+00:00'));
        } finally {
            $this->remove($root);
        }
    }

    private function fixtures(string $root, string $expiresAt): array
    {
        $bindingId = 'model-bound-operational-seat-binding-'.str_repeat('a', 20);
        $binding = $this->record([
            'schema' => 'imperium.model-bound-operational-manifestation-seat-binding/v1',
            'binding_id' => $bindingId,
            'instance_id' => 'imperium-test',
            'seat' => 'foundry.artificer',
            'manifestation_id' => 'manifestation-artificer',
            'occupancy_generation' => 1,
            'status' => 'OPERATIONAL_MANIFESTATION_BOUND_PENDING_DEPLOYMENT_AUTHORIZATION',
            'binding_atomic' => true,
            'sealed' => true,
        ]);
        $attestationId = 'profile-model-access-attestation-'.str_repeat('b', 20);
        $attestation = $this->record([
            'schema' => 'imperium.clavium-profile-model-access-attestation/v1',
            'attestation_id' => $attestationId,
            'status' => 'ACCESS_AVAILABLE',
            'provider_access_evidence' => ['expires_at' => $expiresAt],
            'sealed' => true,
        ]);
        $activationId = 'citadel-officer-runtime-activation-'.str_repeat('c', 20);
        $activation = $this->record([
            'schema' => 'imperium.conscription-citadel-officer-runtime-activation/v1',
            'activation_id' => $activationId,
            'instance_id' => 'imperium-test',
            'source_access_attestation' => ['id' => $attestationId, 'digest' => $attestation['record_digest']],
            'seat' => 'foundry.artificer',
            'manifestation_id' => 'manifestation-artificer',
            'occupancy_generation' => 1,
            'status' => 'MODEL_BOUND_CITADEL_MANIFESTATION_RUNTIME_ACTIVE_PENDING_GOVERNED_COMMISSION',
            'runtime_active' => true,
            'commission_intake_available' => true,
            'sealed' => true,
        ]);
        $target = [
            'seat' => 'foundry.artificer',
            'binding_id' => $bindingId,
            'binding_digest' => $binding['record_digest'],
            'manifestation_id' => 'manifestation-artificer',
            'occupancy_generation' => 1,
            'runtime_activation_id' => $activationId,
            'runtime_activation_digest' => $activation['record_digest'],
        ];
        $commissionId = 'citadel-governed-commission-'.str_repeat('d', 20);
        $commission = $this->record([
            'schema' => 'imperium.citadel-governed-commission/v1',
            'commission_id' => $commissionId,
            'instance_id' => 'imperium-test',
            'case_id' => 'case-artificer',
            'case_digest' => str_repeat('e', 64),
            'issuer' => ['seat' => 'curia.seneschal', 'binding_id' => 'issuer-binding', 'binding_digest' => str_repeat('1', 64), 'manifestation_id' => 'manifestation-seneschal', 'occupancy_generation' => 1],
            'target' => $target,
            'source_runtime_activation' => ['id' => $activationId, 'digest' => $activation['record_digest']],
            'source_access_attestation' => ['id' => $attestationId, 'digest' => $attestation['record_digest']],
            'contract' => ['task' => 'Recommend.', 'purpose' => 'Answer.', 'inputs' => ['input'], 'evidence_requirements' => ['cite'], 'constraints' => ['no tools'], 'output_contract' => ['one answer'], 'stop_conditions' => ['evidence absent']],
            'status' => 'CITADEL_OFFICER_GOVERNED_COMMISSION_ISSUED_PENDING_OFFICER_ACCEPTANCE',
            'commission_issued' => true,
            'commission_intake_available' => true,
            'commission_acceptance_authority' => ['authority_id' => 'citadel-governed-commission-acceptance-authority-'.str_repeat('2', 20), 'authority_single_use' => true, 'destination' => 'foundry.artificer', 'purpose' => 'ACCEPT_ONE_EXACT_GOVERNED_COMMISSION', 'consumed' => false],
            'commission_accepted' => false,
            'commission_exercisable' => false,
            'governed_cognition_authority' => false,
            'provider_invocation_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ]);
        $this->write($root.'/var/imperium/operational/occupancy/'.$bindingId.'.json', $binding);
        $this->write($root.'/var/imperium/offices/clavium/profile-model-access-attestations/'.$attestationId.'.json', $attestation);
        $this->write($root.'/var/imperium/operational/citadel-runtime-activations/'.$activationId.'.json', $activation);
        $this->write($root.'/var/imperium/operational/citadel-governed-commissions/'.$commissionId.'.json', $commission);

        return [$commissionId, $bindingId];
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
