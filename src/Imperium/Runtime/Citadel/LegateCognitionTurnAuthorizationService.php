<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class LegateCognitionTurnAuthorizationService
{
    private string $dispositions;
    private string $commissions;
    private string $activations;
    private string $occupancy;
    private string $attestations;
    private string $decisions;
    private RecordReferenceValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, ?RecordReferenceValidator $validator = null)
    {
        $this->dispositions = $root.'/var/imperium/operational/citadel-legate-governed-commission-dispositions';
        $this->commissions = $root.'/var/imperium/operational/citadel-legate-governed-commissions';
        $this->activations = $root.'/var/imperium/operational/citadel-legate-runtime-activations';
        $this->occupancy = $root.'/var/imperium/operational/occupancy';
        $this->attestations = $root.'/var/imperium/offices/clavium/profile-model-access-attestations';
        $this->decisions = $root.'/var/imperium/operational/citadel-legate-cognition-turn-authorization-decisions';
        $this->validator = $validator ?? new RecordReferenceValidator($root);
    }

    public function decide(string $dispositionId, string $issuerBindingId, string $decision, string $rationale, \DateTimeImmutable $expiresAt, \DateTimeImmutable $decidedAt): array
    {
        if (!preg_match('/^citadel-legate-governed-commission-disposition-[a-f0-9]{20}$/', $dispositionId)) {
            throw new \InvalidArgumentException('CIT340_COMMISSION_DISPOSITION_ID_INVALID');
        }
        if (!preg_match('/^[a-z0-9][a-z0-9-]{2,127}$/', $issuerBindingId)) {
            throw new \InvalidArgumentException('CIT341_ISSUER_BINDING_ID_INVALID');
        }
        if (!in_array($decision, ['AUTHORIZED', 'REFUSED', 'DEFERRED', 'REVOKED'], true)) {
            throw new \InvalidArgumentException('CIT342_COGNITION_TURN_DECISION_INVALID');
        }
        $rationale = trim($rationale);
        if ('' === $rationale) {
            throw new \InvalidArgumentException('CIT343_COGNITION_TURN_RATIONALE_REQUIRED');
        }
        if ($expiresAt <= $decidedAt || $expiresAt > $decidedAt->modify('+15 minutes')) {
            throw new \InvalidArgumentException('CIT344_COGNITION_TURN_EXPIRY_INVALID');
        }

        $disposition = $this->read($this->dispositions.'/'.$dispositionId.'.json', 'CIT345_COMMISSION_DISPOSITION_ABSENT');
        $commission = $this->source($this->commissions, $disposition['source_commission'] ?? [], 'CIT346_COGNITION_TURN_CHAIN_ABSENT');
        $activation = $this->source($this->activations, $disposition['source_runtime_activation'] ?? [], 'CIT346_COGNITION_TURN_CHAIN_ABSENT');
        $attestation = $this->source($this->attestations, $disposition['source_access_attestation'] ?? [], 'CIT346_COGNITION_TURN_CHAIN_ABSENT');
        $issuer = $this->read($this->occupancy.'/'.$issuerBindingId.'.json', 'CIT347_ISSUER_OCCUPANCY_ABSENT');
        $target = $this->read($this->occupancy.'/'.($disposition['actor']['binding_id'] ?? '').'.json', 'CIT348_TARGET_OCCUPANCY_ABSENT');
        $this->validate($dispositionId, $disposition, $commission, $activation, $attestation, $issuerBindingId, $issuer, $target, $decidedAt);
        $this->assertSoleCurrentTargetOccupancy($target);

        foreach (glob($this->decisions.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'CIT351_COGNITION_TURN_DECISION_CONFLICT');
            if (($prior['source_commission_disposition']['id'] ?? null) !== $dispositionId) {
                continue;
            }
            if (($prior['source_commission_disposition']['digest'] ?? null) !== $disposition['record_digest']
                || ($prior['decision'] ?? null) !== $decision || ($prior['rationale'] ?? null) !== $rationale
                || ($prior['expires_at'] ?? null) !== $expiresAt->format(DATE_ATOM)) {
                throw new \RuntimeException('CIT351_COGNITION_TURN_DECISION_CONFLICT');
            }

            return $prior;
        }

        $authorized = 'AUTHORIZED' === $decision;
        $actor = ['seat' => $issuer['seat'], 'binding_id' => $issuerBindingId, 'binding_digest' => $issuer['record_digest'], 'manifestation_id' => $issuer['manifestation_id'], 'occupancy_generation' => $issuer['occupancy_generation']];
        $decisionId = 'citadel-legate-cognition-turn-authorization-decision-'.substr(hash('sha256', CanonicalJson::encode([$dispositionId, $disposition['record_digest'], $actor, $decision, $rationale, $expiresAt->format(DATE_ATOM)])), 0, 20);
        $turnAuthorityId = $authorized ? 'citadel-legate-cognition-turn-authority-'.substr(hash('sha256', CanonicalJson::encode([$decisionId, $commission['contract'], $disposition['actor']])), 0, 20) : null;
        $invocationActivationAuthorityId = $authorized ? 'citadel-legate-provider-invocation-activation-authority-'.substr(hash('sha256', CanonicalJson::encode([$decisionId, $turnAuthorityId, $activation['source_model_binding'] ?? null, $disposition['source_access_attestation']])), 0, 20) : null;

        return $this->save($decisionId, [
            'schema' => 'imperium.citadel-legate-cognition-turn-authorization-decision/v1',
            'decision_id' => $decisionId,
            'instance_id' => $disposition['instance_id'],
            'case_id' => $disposition['case_id'],
            'case_digest' => $disposition['case_digest'],
            'source_commission_disposition' => ['id' => $dispositionId, 'digest' => $disposition['record_digest']],
            'source_commission' => $disposition['source_commission'],
            'source_runtime_activation' => $disposition['source_runtime_activation'],
            'source_access_attestation' => $disposition['source_access_attestation'],
            'authorizer' => $actor,
            'target' => $disposition['actor'],
            'contract' => $disposition['contract'],
            'decision' => $decision,
            'rationale' => $rationale,
            'decided_at' => $decidedAt->format(DATE_ATOM),
            'expires_at' => $expiresAt->format(DATE_ATOM),
            'status' => $authorized ? 'CITADEL_LEGATE_COGNITION_TURN_AUTHORIZED_PENDING_PROVIDER_INVOCATION_ACTIVATION' : 'CITADEL_LEGATE_COGNITION_TURN_'.$decision.'_NO_AUTHORITY',
            'bounded_cognition_turn_authority' => $authorized ? [
                'authority_id' => $turnAuthorityId,
                'authority_single_use' => true,
                'destination' => $disposition['actor']['seat'],
                'purpose' => 'PERFORM_ONE_EXACT_GOVERNED_COGNITION_TURN',
                'maximum_turns' => 1,
                'expires_at' => $expiresAt->format(DATE_ATOM),
                'consumed' => false,
            ] : null,
            'governed_cognition_authority' => $authorized,
            'commission_exercisable' => false,
            'provider_invocation_activation_required' => $authorized,
            'provider_invocation_activation_authority' => $authorized ? [
                'authority_id' => $invocationActivationAuthorityId,
                'authority_single_use' => true,
                'destination' => 'clavium.locksmith',
                'purpose' => 'ACTIVATE_ONE_EXACT_PROVIDER_INVOCATION',
                'expires_at' => $expiresAt->format(DATE_ATOM),
                'consumed' => false,
            ] : null,
            'provider_invocation_authority' => false,
            'provider_invoked' => false,
            'cognition_performed' => false,
            'operational_use_permitted' => false,
            'autonomous_cognition_authority' => false,
            'tool_use_authority' => false,
            'credential_use_authority' => false,
            'external_action_authority' => false,
            'execution_authority' => false,
            'continuing_turn_authority' => false,
            'sealed' => true,
        ]);
    }

    private function validate(string $dispositionId, array $disposition, array $commission, array $activation, array $attestation, string $issuerBindingId, array $issuer, array $target, \DateTimeImmutable $decidedAt): void
    {
        $expiresAt = $attestation['provider_access_evidence']['expires_at'] ?? null;
        if (!$this->valid($disposition) || 'imperium.citadel-legate-governed-commission-disposition/v1' !== ($disposition['schema'] ?? null)
            || $dispositionId !== ($disposition['disposition_id'] ?? null) || 'ACCEPTED' !== ($disposition['disposition'] ?? null)
            || 'CITADEL_LEGATE_GOVERNED_COMMISSION_ACCEPTED_PENDING_COGNITION_TURN_AUTHORIZATION' !== ($disposition['status'] ?? null)
            || true !== ($disposition['commission_accepted'] ?? null) || true !== ($disposition['commission_bound'] ?? null)
            || true !== ($disposition['cognition_turn_authorization_required'] ?? null) || true === ($disposition['commission_exercisable'] ?? null)
            || true === ($disposition['governed_cognition_authority'] ?? null) || true === ($disposition['provider_invocation_authority'] ?? null)
            || true === ($disposition['execution_authority'] ?? null) || true !== ($disposition['sealed'] ?? null)
            || !$this->valid($commission) || 'imperium.citadel-legate-governed-commission/v1' !== ($commission['schema'] ?? null)
            || 'CITADEL_LEGATE_GOVERNED_COMMISSION_ISSUED_PENDING_LEGATE_ACCEPTANCE' !== ($commission['status'] ?? null)
            || ($disposition['contract'] ?? null) !== ($commission['contract'] ?? null)
            || !$this->valid($activation) || 'imperium.conscription-citadel-legate-runtime-activation/v1' !== ($activation['schema'] ?? null)
            || 'MODEL_BOUND_CITADEL_LEGATE_RUNTIME_ACTIVE_PENDING_GOVERNED_COMMISSION' !== ($activation['status'] ?? null)
            || true !== ($activation['runtime_active'] ?? null) || true !== ($activation['sealed'] ?? null)
            || !$this->valid($attestation) || 'imperium.clavium-profile-model-access-attestation/v1' !== ($attestation['schema'] ?? null)
            || 'ACCESS_AVAILABLE' !== ($attestation['status'] ?? null) || true !== ($attestation['sealed'] ?? null)
            || !is_string($expiresAt) || new \DateTimeImmutable($expiresAt) <= $decidedAt
            || !$this->valid($issuer) || $issuerBindingId !== ($issuer['binding_id'] ?? null) || 'ACTIVE' !== ($issuer['status'] ?? null)
            || true !== ($issuer['binding_atomic'] ?? null) || true !== ($issuer['sealed'] ?? null)
            || ($commission['issuer']['binding_id'] ?? null) !== $issuerBindingId || ($commission['issuer']['binding_digest'] ?? null) !== ($issuer['record_digest'] ?? null)
            || ($commission['issuer']['seat'] ?? null) !== ($issuer['seat'] ?? null) || ($commission['instance_id'] ?? null) !== ($issuer['instance_id'] ?? null)
            || true !== ($issuer['governed_commission_issuance_authority'] ?? null) || !in_array($target['seat'] ?? null, $issuer['commissionable_seats'] ?? [], true)
            || !$this->valid($target) || 'imperium.model-bound-operational-manifestation-seat-binding/v1' !== ($target['schema'] ?? null)
            || true !== ($target['binding_atomic'] ?? null) || true !== ($target['sealed'] ?? null)
            || ($disposition['actor']['binding_id'] ?? null) !== ($target['binding_id'] ?? null)
            || ($disposition['actor']['binding_digest'] ?? null) !== ($target['record_digest'] ?? null)
            || ($disposition['actor']['seat'] ?? null) !== ($target['seat'] ?? null) || ($disposition['actor']['manifestation_id'] ?? null) !== ($target['manifestation_id'] ?? null)
            || ($disposition['actor']['occupancy_generation'] ?? null) !== ($target['occupancy_generation'] ?? null)
            || !in_array($target['status'] ?? null, ['OPERATIONAL_MANIFESTATION_BOUND_PENDING_DEPLOYMENT_AUTHORIZATION', 'ACTIVE'], true)) {
            throw new \RuntimeException('CIT349_COGNITION_TURN_AUTHORIZATION_CHAIN_INVALID');
        }
    }

    private function assertSoleCurrentTargetOccupancy(array $target): void
    {
        foreach (glob($this->occupancy.'/*.json') ?: [] as $path) {
            $other = $this->read($path, 'CIT352_TARGET_OCCUPANCY_CONFLICT');
            if (($other['seat'] ?? null) !== $target['seat'] || ($other['binding_id'] ?? null) === $target['binding_id']) {
                continue;
            }
            if (in_array($other['status'] ?? null, ['OPERATIONAL_MANIFESTATION_BOUND_PENDING_DEPLOYMENT_AUTHORIZATION', 'ACTIVE'], true)) {
                throw new \RuntimeException('CIT352_TARGET_OCCUPANCY_CONFLICT');
            }
        }
    }

    private function source(string $directory, array $reference, string $error): array
    {
        return $this->validator->resolve($directory, $reference, $error, 'CIT349_COGNITION_TURN_AUTHORIZATION_CHAIN_INVALID');
    }

    private function read(string $path, string $error): array
    {
        return $this->validator->read($path, $error);
    }

    private function valid(array $record): bool
    {
        return $this->validator->isIntact($record);
    }

    private function save(string $id, array $record): array
    {
        if (!is_dir($this->decisions) && !mkdir($this->decisions, 0770, true) && !is_dir($this->decisions)) {
            throw new \RuntimeException('CIT350_COGNITION_TURN_DECISION_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->decisions.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'CIT351_COGNITION_TURN_DECISION_CONFLICT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('CIT351_COGNITION_TURN_DECISION_CONFLICT');
            }

            return $existing;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('CIT350_COGNITION_TURN_DECISION_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
