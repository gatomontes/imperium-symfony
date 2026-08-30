<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\AuthorityConsumptionStore;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class CorridorDispositionPrincipalAuthorityRemediationProducer
{
    public const string SCOPE_SUCCESSORS = 'var/imperium/runtime/corridor-disposition-scope-successors';
    public const string CALLER_AUTHORITIES = 'var/imperium/runtime/corridor-disposition-caller-authorities';
    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;
    private AuthorityConsumptionStore $consumptions;
    private CorridorDispositionPrincipalAuthorityRemediationContractValidator $contracts;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->atomic = new AtomicTransition($root); $this->records = new ImmutableRecordStore($root, $this->atomic); $this->consumptions = new AuthorityConsumptionStore($this->records, $this->atomic); $this->contracts = new CorridorDispositionPrincipalAuthorityRemediationContractValidator();
    }

    public function commitSuccessor(array $grant, array $transition, array $sourcePrincipal, array $pendingSuccessor, \DateTimeImmutable $at): array
    {
        $winner = [$grant['instance_id'] ?? null, $grant['source_principal']['id'] ?? null, $grant['successor_principal']['generation'] ?? null];
        return $this->atomic->run('corridor-scope-successor:'.hash('sha256', CanonicalJson::encode($winner)), function () use ($grant, $transition, $sourcePrincipal, $pendingSuccessor, $at): array {
            try { $canonicalSource = $this->records->read(FutureInstanceImperatorPrincipalConstitutionService::PRINCIPAL_VERSIONS, (string) ($grant['source_principal']['id'] ?? '')); } catch (\Throwable) { throw new \RuntimeException('CPA501_CANONICAL_SOURCE_PRINCIPAL_ABSENT'); }
            if (($canonicalSource['record_digest'] ?? null) !== ($sourcePrincipal['record_digest'] ?? null)) throw new \RuntimeException('CPA502_CANONICAL_SOURCE_PRINCIPAL_CONFLICT');
            $this->contracts->assertScopeGrant($grant, $sourcePrincipal, $at); $this->contracts->assertScopeSuccessor($transition, $grant);
            $this->assertPendingSuccessor($grant, $sourcePrincipal, $pendingSuccessor, $at);
            $this->assertGenerationWinner($pendingSuccessor);
            $this->consumptions->consume($grant['grant_id'], $grant['grant_id'], $grant['record_digest'], 'imperator.corridor-scope-successor-committer', $at);
            $storedPrincipal = $this->records->put(FutureInstanceImperatorPrincipalConstitutionService::PRINCIPAL_VERSIONS, $pendingSuccessor['principal_version_id'], $pendingSuccessor);
            $storedTransition = $this->records->put(self::SCOPE_SUCCESSORS, $transition['successor_transition_id'], $transition);
            return ['principal_version' => $storedPrincipal, 'scope_successor' => $storedTransition, 'grant_consumed' => true, 'principal_activated' => false, 'binding_activated' => false, 'external_action_performed' => false];
        });
    }

    public function activateSuccessor(array $activationDisposition, \DateTimeImmutable $at): array
    {
        $id = (string) ($activationDisposition['disposition_id'] ?? 'invalid');
        return $this->atomic->run('corridor-scope-successor-activation:'.hash('sha256', (string) ($activationDisposition['source_principal_version']['id'] ?? $id)), function () use ($activationDisposition, $at, $id): array {
            (new ImperatorPrincipalProvenanceFixtureStore($this->root))->assertLifecycleDisposition($activationDisposition);
            if ('ACTIVATE' !== $activationDisposition['disposition'] || 'PENDING_ACTIVATION' !== $activationDisposition['source_status'] || new \DateTimeImmutable($activationDisposition['effective_at']) > $at) throw new \RuntimeException('CPA510_SEPARATE_ACTIVATION_INVALID');
            $source = $activationDisposition['source_principal_version'];
            $principal = $this->records->read(FutureInstanceImperatorPrincipalConstitutionService::PRINCIPAL_VERSIONS, $source['id']);
            if ($source !== $this->reference($principal, 'principal_version_id') || 'PENDING_ACTIVATION' !== $principal['status'] || true !== $principal['authority_scope']['corridor_disposition_authority'] || new \DateTimeImmutable($principal['lifecycle']['expires_at']) <= $at) throw new \RuntimeException('CPA511_ACTIVATION_PRINCIPAL_INVALID');
            $this->consumptions->consume($id, $id, $activationDisposition['record_digest'], 'operator-root.corridor-scope-successor-activator', $at);
            $stored = $this->records->put(ImperatorPrincipalProvenanceFixtureStore::LIFECYCLE_DISPOSITIONS, $id, $activationDisposition);
            return ['activation_disposition' => $stored, 'effective_status' => 'ACTIVE', 'principal_record_mutated' => false, 'binding_activated' => false, 'external_action_performed' => false];
        });
    }

    public function issueCallerAuthority(array $authorization, array $successor, array $activationDisposition, array $target, array $dossier, array $eligibility, \DateTimeImmutable $at): array
    {
        $id = (string) ($authorization['issuance_authorization_id'] ?? 'invalid');
        return $this->atomic->run('corridor-caller-authority-issuance:'.hash('sha256', (string) ($authorization['result_authority_id'] ?? $id)), function () use ($authorization, $successor, $activationDisposition, $target, $dossier, $eligibility, $at, $id): array {
            $principalId = (string) ($authorization['issuer_principal']['id'] ?? '');
            $reconstruction = (new ImperatorPrincipalLifecycleReconstructionService($this->root))->reconstruct($principalId, $at); $principal = $reconstruction['principal_version'];
            if ('ACTIVE' !== $reconstruction['effective_status'] || ($reconstruction['effective_disposition']['record_digest'] ?? null) !== ($activationDisposition['record_digest'] ?? null)) throw new \RuntimeException('CPA520_SEPARATE_ACTIVATION_REQUIRED');
            $this->contracts->assertIssuanceAuthorization($authorization, $principal, $successor, $activationDisposition, $target, $dossier, $eligibility, $at);
            $record = ['schema' => ActivationCorridorDispositionCallerAuthorityContract::SCHEMA, 'authority_id' => $authorization['result_authority_id'], 'instance_id' => $authorization['instance_id'], 'principal' => $this->reference($principal, 'principal_version_id'), 'target' => $authorization['target'], 'evidence_dossier' => $authorization['evidence_dossier'], 'eligibility' => $authorization['eligibility'], 'permitted_transition' => ActivationCorridorDispositionCallerAuthorityContract::PERMITTED_TRANSITION, 'proposed_disposition' => $authorization['proposed_disposition'], 'authority_single_use' => true, 'authority_exercisable' => true, 'issued_at' => $authorization['issued_at'], 'expires_at' => $authorization['expires_at'], 'consumed' => false, 'continuing_authority' => false, 'issuance_winner_required' => true, 'consumption_winner_required' => true, 'sealed' => true];
            $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
            (new ActivationCorridorDispositionContractValidator())->assertCallerAuthority($record);
            $this->consumptions->consume($id, $id, $authorization['record_digest'], 'imperator.corridor-caller-authority-issuer', $at);
            $stored = $this->records->put(self::CALLER_AUTHORITIES, $record['authority_id'], $record);
            return ['caller_authority' => $stored, 'issuance_authorization_consumed' => true, 'disposition_selected' => false, 'disposition_sealed' => false, 'binding_activated' => false, 'external_action_performed' => false];
        });
    }

    private function assertPendingSuccessor(array $grant, array $source, array $pending, \DateTimeImmutable $at): void
    {
        $plain = $pending; $digest = $plain['record_digest'] ?? null; unset($plain['record_digest']);
        if (ImperatorRuntimePrincipalVersionContract::REQUIRED_FIELDS !== array_keys($pending) || !is_string($digest) || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain))) || $this->reference($pending, 'principal_version_id') + ['generation' => $pending['principal_generation']] !== $grant['successor_principal'] || $pending['instance_id'] !== $grant['instance_id'] || $pending['principal_id'] !== $source['principal_id'] || $pending['binding_id'] !== $source['binding_id'] || $pending['identity'] !== $source['identity'] || $pending['principal_generation'] !== $source['principal_generation'] + 1 || 'PENDING_ACTIVATION' !== $pending['status'] || true !== $pending['authority_scope']['corridor_disposition_authority'] || array_diff_assoc($grant['preserved_scope'], $pending['authority_scope']) || false !== $pending['credential_reference_persisted'] || false !== $pending['credential_secret_persisted'] || false !== $pending['serialized_capability_persisted'] || new \DateTimeImmutable($pending['lifecycle']['effective_at']) > $at || new \DateTimeImmutable($pending['lifecycle']['expires_at']) <= $at || $pending['lifecycle']['prior_version'] !== $this->reference($source, 'principal_version_id')) throw new \RuntimeException('CPA500_PENDING_SUCCESSOR_INVALID');
    }
    private function assertGenerationWinner(array $pending): void
    {
        $directory = $this->root.'/'.FutureInstanceImperatorPrincipalConstitutionService::PRINCIPAL_VERSIONS;
        foreach (glob($directory.'/*.json') ?: [] as $path) {
            $record = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (($record['instance_id'] ?? null) === $pending['instance_id'] && ($record['principal_id'] ?? null) === $pending['principal_id'] && ($record['principal_generation'] ?? null) === $pending['principal_generation'] && ($record['principal_version_id'] ?? null) !== $pending['principal_version_id']) throw new \RuntimeException('CPA503_SUCCESSOR_GENERATION_CONTENTION');
        }
    }
    private function reference(array $record, string $id): array { return ['id' => $record[$id], 'digest' => $record['record_digest'], 'schema' => $record['schema']]; }
}
