<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class LegateGovernedCommissionIssuanceService
{
    private string $activations;
    private string $occupancy;
    private string $attestations;
    private string $commissions;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->activations = $root.'/var/imperium/operational/citadel-legate-runtime-activations';
        $this->occupancy = $root.'/var/imperium/operational/occupancy';
        $this->attestations = $root.'/var/imperium/offices/clavium/profile-model-access-attestations';
        $this->commissions = $root.'/var/imperium/operational/citadel-legate-governed-commissions';
    }

    public function issue(string $activationId, string $issuerBindingId, array $contract, \DateTimeImmutable $issuedAt): array
    {
        if (!preg_match('/^citadel-legate-runtime-activation-[a-f0-9]{20}$/', $activationId)) {
            throw new \InvalidArgumentException('CIT301_RUNTIME_ACTIVATION_ID_INVALID');
        }
        if (!preg_match('/^[a-z0-9][a-z0-9-]{2,127}$/', $issuerBindingId)) {
            throw new \InvalidArgumentException('CIT302_ISSUER_BINDING_ID_INVALID');
        }

        $activation = $this->read($this->activations.'/'.$activationId.'.json', 'CIT303_RUNTIME_ACTIVATION_ABSENT');
        $targetBinding = $this->source($this->occupancy, $activation['source_seat_binding'] ?? [], 'CIT304_COMMISSION_CHAIN_ABSENT');
        $attestation = $this->source($this->attestations, $activation['source_access_attestation'] ?? [], 'CIT304_COMMISSION_CHAIN_ABSENT');
        $issuerBinding = $this->read($this->occupancy.'/'.$issuerBindingId.'.json', 'CIT305_ISSUER_OCCUPANCY_ABSENT');
        $normalized = $this->contract($contract);
        $this->validate($activationId, $activation, $targetBinding, $attestation, $issuerBindingId, $issuerBinding, $issuedAt);
        $this->assertSoleCurrentTargetOccupancy($targetBinding);

        $issuer = [
            'seat' => $issuerBinding['seat'],
            'binding_id' => $issuerBindingId,
            'binding_digest' => $issuerBinding['record_digest'],
            'manifestation_id' => $issuerBinding['manifestation_id'],
            'occupancy_generation' => $issuerBinding['occupancy_generation'],
        ];
        $target = [
            'seat' => $activation['seat'],
            'binding_id' => $targetBinding['binding_id'],
            'binding_digest' => $targetBinding['record_digest'],
            'manifestation_id' => $activation['manifestation_id'],
            'occupancy_generation' => $activation['occupancy_generation'],
            'runtime_activation_id' => $activationId,
            'runtime_activation_digest' => $activation['record_digest'],
        ];
        $commissionId = 'citadel-legate-governed-commission-'.substr(hash('sha256', CanonicalJson::encode([$activationId, $activation['record_digest'], $issuer, $target, $normalized])), 0, 20);
        $path = $this->commissions.'/'.$commissionId.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'CIT309_GOVERNED_COMMISSION_CONFLICT');
            if (($prior['source_runtime_activation']['digest'] ?? null) !== $activation['record_digest']
                || ($prior['issuer']['binding_digest'] ?? null) !== $issuerBinding['record_digest']
                || CanonicalJson::encode($prior['contract'] ?? null) !== CanonicalJson::encode($normalized)) {
                throw new \RuntimeException('CIT309_GOVERNED_COMMISSION_CONFLICT');
            }
            return $prior;
        }

        $acceptanceAuthorityId = 'citadel-legate-governed-commission-acceptance-authority-'.substr(hash('sha256', CanonicalJson::encode([$commissionId, $target])), 0, 20);

        return $this->save($commissionId, [
            'schema' => 'imperium.citadel-legate-governed-commission/v1',
            'commission_id' => $commissionId,
            'instance_id' => $activation['instance_id'],
            'case_id' => $activation['case_id'],
            'case_digest' => $activation['case_digest'],
            'issuer' => $issuer,
            'target' => $target,
            'source_runtime_activation' => ['id' => $activationId, 'digest' => $activation['record_digest']],
            'source_access_attestation' => $activation['source_access_attestation'],
            'contract' => $normalized,
            'issued_at' => $issuedAt->format(DATE_ATOM),
            'status' => 'CITADEL_LEGATE_GOVERNED_COMMISSION_ISSUED_PENDING_LEGATE_ACCEPTANCE',
            'commission_issued' => true,
            'commission_intake_available' => true,
            'commission_acceptance_authority' => [
                'authority_id' => $acceptanceAuthorityId,
                'authority_single_use' => true,
                'destination' => $activation['seat'],
                'purpose' => 'ACCEPT_ONE_EXACT_GOVERNED_COMMISSION',
                'consumed' => false,
            ],
            'commission_accepted' => false,
            'commission_exercisable' => false,
            'operational_use_permitted' => false,
            'autonomous_cognition_authority' => false,
            'governed_cognition_authority' => false,
            'tool_use_authority' => false,
            'credential_use_authority' => false,
            'provider_invocation_authority' => false,
            'external_action_authority' => false,
            'execution_authority' => false,
            'continuing_turn_authority' => false,
            'sealed' => true,
        ]);
    }

    private function validate(string $activationId, array $activation, array $targetBinding, array $attestation, string $issuerBindingId, array $issuerBinding, \DateTimeImmutable $issuedAt): void
    {
        $expiresAt = $attestation['provider_access_evidence']['expires_at'] ?? null;
        if (!$this->valid($activation) || 'imperium.conscription-citadel-legate-runtime-activation/v1' !== ($activation['schema'] ?? null)
            || $activationId !== ($activation['activation_id'] ?? null) || 'MODEL_BOUND_CITADEL_LEGATE_RUNTIME_ACTIVE_PENDING_GOVERNED_COMMISSION' !== ($activation['status'] ?? null)
            || true !== ($activation['runtime_active'] ?? null) || true !== ($activation['commission_intake_available'] ?? null)
            || true === ($activation['governed_cognition_authority'] ?? null) || true === ($activation['provider_invocation_authority'] ?? null)
            || true === ($activation['execution_authority'] ?? null) || true !== ($activation['sealed'] ?? null)
            || !$this->valid($targetBinding) || 'imperium.model-bound-operational-manifestation-seat-binding/v1' !== ($targetBinding['schema'] ?? null)
            || true !== ($targetBinding['binding_atomic'] ?? null) || true !== ($targetBinding['sealed'] ?? null)
            || ($activation['seat'] ?? null) !== ($targetBinding['seat'] ?? null)
            || ($activation['manifestation_id'] ?? null) !== ($targetBinding['manifestation_id'] ?? null)
            || ($activation['occupancy_generation'] ?? null) !== ($targetBinding['occupancy_generation'] ?? null)
            || !in_array($targetBinding['status'] ?? null, ['OPERATIONAL_MANIFESTATION_BOUND_PENDING_DEPLOYMENT_AUTHORIZATION', 'ACTIVE'], true)
            || !$this->valid($attestation) || 'imperium.clavium-profile-model-access-attestation/v1' !== ($attestation['schema'] ?? null)
            || 'ACCESS_AVAILABLE' !== ($attestation['status'] ?? null) || true !== ($attestation['sealed'] ?? null)
            || !is_string($expiresAt) || new \DateTimeImmutable($expiresAt) <= $issuedAt
            || !$this->valid($issuerBinding) || $issuerBindingId !== ($issuerBinding['binding_id'] ?? null)
            || ($activation['instance_id'] ?? null) !== ($issuerBinding['instance_id'] ?? null)
            || 'ACTIVE' !== ($issuerBinding['status'] ?? null) || true !== ($issuerBinding['binding_atomic'] ?? null) || true !== ($issuerBinding['sealed'] ?? null)
            || !is_string($issuerBinding['seat'] ?? null) || ($issuerBinding['seat'] ?? null) === ($activation['seat'] ?? null)
            || true !== ($issuerBinding['governed_commission_issuance_authority'] ?? null)
            || !in_array($activation['seat'] ?? null, $issuerBinding['commissionable_seats'] ?? [], true)) {
            throw new \RuntimeException('CIT306_GOVERNED_COMMISSION_AUTHORITY_INVALID');
        }
    }

    private function assertSoleCurrentTargetOccupancy(array $targetBinding): void
    {
        foreach (glob($this->occupancy.'/*.json') ?: [] as $path) {
            $other = $this->read($path, 'CIT311_TARGET_OCCUPANCY_CONFLICT');
            if (($other['seat'] ?? null) !== $targetBinding['seat'] || ($other['binding_id'] ?? null) === $targetBinding['binding_id']) {
                continue;
            }
            if (in_array($other['status'] ?? null, ['OPERATIONAL_MANIFESTATION_BOUND_PENDING_DEPLOYMENT_AUTHORIZATION', 'ACTIVE'], true)) {
                throw new \RuntimeException('CIT311_TARGET_OCCUPANCY_CONFLICT');
            }
        }
    }

    private function contract(array $contract): array
    {
        $requiredStrings = ['task', 'purpose'];
        $requiredLists = ['inputs', 'evidence_requirements', 'constraints', 'output_contract', 'stop_conditions'];
        $expected = [...$requiredStrings, ...$requiredLists];
        $actual = array_keys($contract);
        sort($expected);
        sort($actual);
        if ($actual !== $expected) {
            throw new \InvalidArgumentException('CIT307_GOVERNED_COMMISSION_CONTRACT_INVALID');
        }
        foreach ($requiredStrings as $field) {
            if (!is_string($contract[$field]) || '' === trim($contract[$field])) {
                throw new \InvalidArgumentException('CIT307_GOVERNED_COMMISSION_CONTRACT_INVALID');
            }
            $contract[$field] = trim($contract[$field]);
        }
        foreach ($requiredLists as $field) {
            if (!is_array($contract[$field]) || [] === $contract[$field] || array_is_list($contract[$field]) === false) {
                throw new \InvalidArgumentException('CIT307_GOVERNED_COMMISSION_CONTRACT_INVALID');
            }
            foreach ($contract[$field] as $value) {
                if (!is_string($value) || '' === trim($value)) {
                    throw new \InvalidArgumentException('CIT307_GOVERNED_COMMISSION_CONTRACT_INVALID');
                }
            }
            $contract[$field] = array_map('trim', $contract[$field]);
        }

        return $contract;
    }

    private function source(string $directory, array $reference, string $error): array
    {
        $record = $this->read($directory.'/'.($reference['id'] ?? '').'.json', $error);
        if (!$this->valid($record) || ($reference['digest'] ?? null) !== ($record['record_digest'] ?? null)) {
            throw new \RuntimeException('CIT306_GOVERNED_COMMISSION_AUTHORITY_INVALID');
        }
        return $record;
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException($error);
        }

        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function valid(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);

        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function save(string $id, array $record): array
    {
        if (!is_dir($this->commissions) && !mkdir($this->commissions, 0770, true) && !is_dir($this->commissions)) {
            throw new \RuntimeException('CIT308_GOVERNED_COMMISSION_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->commissions.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'CIT309_GOVERNED_COMMISSION_CONFLICT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('CIT309_GOVERNED_COMMISSION_CONFLICT');
            }
            return $existing;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('CIT308_GOVERNED_COMMISSION_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
