<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class CitadelGovernedCommissionDispositionService
{
    private string $commissions;
    private string $activations;
    private string $occupancy;
    private string $attestations;
    private string $dispositions;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->commissions = $root.'/var/imperium/operational/citadel-governed-commissions';
        $this->activations = $root.'/var/imperium/operational/citadel-runtime-activations';
        $this->occupancy = $root.'/var/imperium/operational/occupancy';
        $this->attestations = $root.'/var/imperium/offices/clavium/profile-model-access-attestations';
        $this->dispositions = $root.'/var/imperium/operational/citadel-governed-commission-dispositions';
    }

    public function decide(string $commissionId, string $targetBindingId, string $disposition, string $rationale, \DateTimeImmutable $decidedAt): array
    {
        if (!preg_match('/^citadel-governed-commission-[a-f0-9]{20}$/', $commissionId)) {
            throw new \InvalidArgumentException('CIT320_GOVERNED_COMMISSION_ID_INVALID');
        }
        if (!preg_match('/^[a-z0-9][a-z0-9-]{2,127}$/', $targetBindingId)) {
            throw new \InvalidArgumentException('CIT321_TARGET_BINDING_ID_INVALID');
        }
        if (!in_array($disposition, ['ACCEPTED', 'REFUSED'], true)) {
            throw new \InvalidArgumentException('CIT322_COMMISSION_DISPOSITION_INVALID');
        }
        $rationale = trim($rationale);
        if ('' === $rationale) {
            throw new \InvalidArgumentException('CIT323_COMMISSION_RATIONALE_REQUIRED');
        }

        $commission = $this->read($this->commissions.'/'.$commissionId.'.json', 'CIT324_GOVERNED_COMMISSION_ABSENT');
        $activation = $this->source($this->activations, $commission['source_runtime_activation'] ?? [], 'CIT325_COMMISSION_ACCEPTANCE_CHAIN_ABSENT');
        $targetBinding = $this->read($this->occupancy.'/'.$targetBindingId.'.json', 'CIT326_TARGET_OCCUPANCY_ABSENT');
        $attestation = $this->source($this->attestations, $commission['source_access_attestation'] ?? [], 'CIT325_COMMISSION_ACCEPTANCE_CHAIN_ABSENT');
        $this->validate($commissionId, $commission, $activation, $targetBindingId, $targetBinding, $attestation, $decidedAt);
        $this->assertSoleCurrentTargetOccupancy($targetBinding);

        foreach (glob($this->dispositions.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'CIT329_COMMISSION_DISPOSITION_CONFLICT');
            if (($prior['source_commission']['id'] ?? null) !== $commissionId) {
                continue;
            }
            if (($prior['source_commission']['digest'] ?? null) !== $commission['record_digest']
                || ($prior['disposition'] ?? null) !== $disposition
                || ($prior['rationale'] ?? null) !== $rationale
                || ($prior['actor']['binding_digest'] ?? null) !== $targetBinding['record_digest']) {
                throw new \RuntimeException('CIT329_COMMISSION_DISPOSITION_CONFLICT');
            }

            return $prior;
        }

        $accepted = 'ACCEPTED' === $disposition;
        $actor = [
            'seat' => $targetBinding['seat'],
            'binding_id' => $targetBindingId,
            'binding_digest' => $targetBinding['record_digest'],
            'manifestation_id' => $targetBinding['manifestation_id'],
            'occupancy_generation' => $targetBinding['occupancy_generation'],
        ];
        $dispositionId = 'citadel-governed-commission-disposition-'.substr(hash('sha256', CanonicalJson::encode([$commissionId, $commission['record_digest'], $actor, $disposition, $rationale])), 0, 20);

        return $this->save($dispositionId, [
            'schema' => 'imperium.citadel-governed-commission-disposition/v1',
            'disposition_id' => $dispositionId,
            'instance_id' => $commission['instance_id'],
            'case_id' => $commission['case_id'],
            'case_digest' => $commission['case_digest'],
            'source_commission' => ['id' => $commissionId, 'digest' => $commission['record_digest']],
            'source_runtime_activation' => $commission['source_runtime_activation'],
            'source_access_attestation' => $commission['source_access_attestation'],
            'issuer' => $commission['issuer'],
            'target' => $commission['target'],
            'actor' => $actor,
            'contract' => $commission['contract'],
            'disposition' => $disposition,
            'rationale' => $rationale,
            'decided_at' => $decidedAt->format(DATE_ATOM),
            'commission_acceptance_authority' => [
                'id' => $commission['commission_acceptance_authority']['authority_id'],
                'consumed' => true,
                'continuing_authority' => false,
            ],
            'status' => $accepted
                ? 'CITADEL_OFFICER_GOVERNED_COMMISSION_ACCEPTED_PENDING_COGNITION_TURN_AUTHORIZATION'
                : 'CITADEL_OFFICER_GOVERNED_COMMISSION_REFUSED_NO_AUTHORITY',
            'commission_accepted' => $accepted,
            'commission_bound' => $accepted,
            'commission_exercisable' => false,
            'cognition_turn_authorization_required' => $accepted,
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

    private function validate(string $commissionId, array $commission, array $activation, string $targetBindingId, array $targetBinding, array $attestation, \DateTimeImmutable $decidedAt): void
    {
        $authority = $commission['commission_acceptance_authority'] ?? [];
        $expiresAt = $attestation['provider_access_evidence']['expires_at'] ?? null;
        if (!$this->valid($commission) || 'imperium.citadel-governed-commission/v1' !== ($commission['schema'] ?? null)
            || $commissionId !== ($commission['commission_id'] ?? null)
            || 'CITADEL_OFFICER_GOVERNED_COMMISSION_ISSUED_PENDING_OFFICER_ACCEPTANCE' !== ($commission['status'] ?? null)
            || true !== ($commission['commission_issued'] ?? null) || true !== ($commission['commission_intake_available'] ?? null)
            || true !== ($authority['authority_single_use'] ?? null) || false !== ($authority['consumed'] ?? null)
            || 'ACCEPT_ONE_EXACT_GOVERNED_COMMISSION' !== ($authority['purpose'] ?? null)
            || ($commission['target']['seat'] ?? null) !== ($authority['destination'] ?? null)
            || true === ($commission['commission_accepted'] ?? null) || true === ($commission['commission_exercisable'] ?? null)
            || true === ($commission['governed_cognition_authority'] ?? null) || true === ($commission['provider_invocation_authority'] ?? null)
            || true === ($commission['execution_authority'] ?? null) || true !== ($commission['sealed'] ?? null)
            || !$this->valid($activation) || 'imperium.conscription-citadel-officer-runtime-activation/v1' !== ($activation['schema'] ?? null)
            || 'MODEL_BOUND_CITADEL_MANIFESTATION_RUNTIME_ACTIVE_PENDING_GOVERNED_COMMISSION' !== ($activation['status'] ?? null)
            || true !== ($activation['runtime_active'] ?? null) || true !== ($activation['commission_intake_available'] ?? null) || true !== ($activation['sealed'] ?? null)
            || ($commission['instance_id'] ?? null) !== ($activation['instance_id'] ?? null)
            || ($commission['target']['runtime_activation_id'] ?? null) !== ($activation['activation_id'] ?? null)
            || ($commission['target']['runtime_activation_digest'] ?? null) !== ($activation['record_digest'] ?? null)
            || ($commission['source_access_attestation'] ?? null) !== ($activation['source_access_attestation'] ?? null)
            || ($commission['target']['seat'] ?? null) !== ($activation['seat'] ?? null)
            || ($commission['target']['manifestation_id'] ?? null) !== ($activation['manifestation_id'] ?? null)
            || ($commission['target']['occupancy_generation'] ?? null) !== ($activation['occupancy_generation'] ?? null)
            || !$this->valid($targetBinding) || 'imperium.model-bound-operational-manifestation-seat-binding/v1' !== ($targetBinding['schema'] ?? null)
            || $targetBindingId !== ($targetBinding['binding_id'] ?? null) || true !== ($targetBinding['binding_atomic'] ?? null) || true !== ($targetBinding['sealed'] ?? null)
            || ($commission['instance_id'] ?? null) !== ($targetBinding['instance_id'] ?? null)
            || ($commission['target']['seat'] ?? null) !== ($targetBinding['seat'] ?? null)
            || ($commission['target']['manifestation_id'] ?? null) !== ($targetBinding['manifestation_id'] ?? null)
            || ($commission['target']['occupancy_generation'] ?? null) !== ($targetBinding['occupancy_generation'] ?? null)
            || ($commission['target']['binding_digest'] ?? null) !== ($targetBinding['record_digest'] ?? null)
            || !in_array($targetBinding['status'] ?? null, ['OPERATIONAL_MANIFESTATION_BOUND_PENDING_DEPLOYMENT_AUTHORIZATION', 'ACTIVE'], true)
            || !$this->valid($attestation) || 'imperium.clavium-profile-model-access-attestation/v1' !== ($attestation['schema'] ?? null)
            || 'ACCESS_AVAILABLE' !== ($attestation['status'] ?? null) || true !== ($attestation['sealed'] ?? null)
            || !is_string($expiresAt) || new \DateTimeImmutable($expiresAt) <= $decidedAt) {
            throw new \RuntimeException('CIT327_GOVERNED_COMMISSION_DISPOSITION_CHAIN_INVALID');
        }
    }

    private function assertSoleCurrentTargetOccupancy(array $targetBinding): void
    {
        foreach (glob($this->occupancy.'/*.json') ?: [] as $path) {
            $other = $this->read($path, 'CIT330_TARGET_OCCUPANCY_CONFLICT');
            if (($other['seat'] ?? null) !== $targetBinding['seat'] || ($other['binding_id'] ?? null) === $targetBinding['binding_id']) {
                continue;
            }
            if (in_array($other['status'] ?? null, ['OPERATIONAL_MANIFESTATION_BOUND_PENDING_DEPLOYMENT_AUTHORIZATION', 'ACTIVE'], true)) {
                throw new \RuntimeException('CIT330_TARGET_OCCUPANCY_CONFLICT');
            }
        }
    }

    private function source(string $directory, array $reference, string $error): array
    {
        $record = $this->read($directory.'/'.($reference['id'] ?? '').'.json', $error);
        if (!$this->valid($record) || ($reference['digest'] ?? null) !== ($record['record_digest'] ?? null)) {
            throw new \RuntimeException('CIT327_GOVERNED_COMMISSION_DISPOSITION_CHAIN_INVALID');
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
        if (!is_dir($this->dispositions) && !mkdir($this->dispositions, 0770, true) && !is_dir($this->dispositions)) {
            throw new \RuntimeException('CIT328_COMMISSION_DISPOSITION_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->dispositions.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'CIT329_COMMISSION_DISPOSITION_CONFLICT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('CIT329_COMMISSION_DISPOSITION_CONFLICT');
            }

            return $existing;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('CIT328_COMMISSION_DISPOSITION_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
