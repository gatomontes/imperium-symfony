<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Oracle;

use App\Bootstrap\CanonicalJson;

final readonly class ModelIntelligenceLedgerService
{
    private const ACCESS_STATES = ['ACCESSIBLE', 'UNVERIFIED', 'UNAVAILABLE'];
    private const ADMISSIBILITY_STATES = ['ADMISSIBLE', 'RESTRICTED', 'INADMISSIBLE', 'UNEVALUATED'];

    private string $directory;

    public function __construct(string $projectDir)
    {
        $this->directory = $projectDir.'/var/imperium/offices/oracle/model-intelligence-snapshots';
    }

    public function sealSnapshot(string $instanceId, array $records, array $augurBinding, ?string $priorSnapshotId = null): array
    {
        $this->assertAugurAuthority($instanceId, $augurBinding);
        if ([] === $records) {
            throw new \InvalidArgumentException('OR01_MODEL_INTELLIGENCE_EMPTY');
        }

        $models = [];
        foreach ($records as $record) {
            $normalized = $this->validateRecord($record);
            $ref = $normalized['model_ref'];
            if (isset($models[$ref])) {
                throw new \RuntimeException('OR02_MODEL_INTELLIGENCE_DUPLICATE');
            }
            $models[$ref] = $normalized;
        }
        ksort($models, SORT_STRING);

        $prior = null;
        $generation = 1;
        if (null !== $priorSnapshotId) {
            $priorRecord = $this->read($this->directory.'/'.$priorSnapshotId.'.json', 'OR16_PRIOR_SNAPSHOT_ABSENT');
            if (!$this->digestMatches($priorRecord)
                || 'imperium.oracle-model-intelligence-snapshot/v1' !== ($priorRecord['schema'] ?? null)
                || $instanceId !== ($priorRecord['instance_id'] ?? null)
                || $priorSnapshotId !== ($priorRecord['snapshot_id'] ?? null)
            ) throw new \RuntimeException('OR17_PRIOR_SNAPSHOT_INVALID');
            $generation = ((int) ($priorRecord['snapshot_generation'] ?? 0)) + 1;
            $prior = ['id' => $priorSnapshotId, 'digest' => $priorRecord['record_digest']];
        }

        $actor = [
            'office' => 'oracle',
            'seat' => 'oracle.augur',
            'binding_id' => $augurBinding['binding_id'],
            'manifestation_id' => $augurBinding['manifestation_id'],
            'occupancy_generation' => $augurBinding['occupancy_generation'],
        ];
        $snapshotId = 'oracle-model-intelligence-'.substr(hash('sha256', CanonicalJson::encode([$instanceId, $generation, $prior, $actor, $models])), 0, 20);

        return $this->persist($snapshotId, [
            'schema' => 'imperium.oracle-model-intelligence-snapshot/v1',
            'snapshot_id' => $snapshotId,
            'snapshot_generation' => $generation,
            'prior_snapshot' => $prior,
            'instance_id' => $instanceId,
            'steward' => 'oracle',
            'actor' => $actor,
            'models' => $models,
            'classification_dimensions' => ['knowledge', 'accessibility', 'admissibility'],
            'status' => 'ORACLE_MODEL_INTELLIGENCE_SNAPSHOT_SEALED_NO_SELECTION_AUTHORITY',
            'requirement_commission_authority' => false,
            'eligibility_authority' => false,
            'recommendation_authority' => false,
            'selection_authority' => false,
            'model_assignment_authority' => false,
            'profile_mutation_authority' => false,
            'credential_disclosure_authority' => false,
            'provider_invocation_authority' => false,
            'deployment_authority' => false,
        ]);
    }

    private function validateRecord(mixed $record): array
    {
        $keys = ['provider', 'model_id', 'model_version', 'knowledge_sources', 'claims', 'accessibility', 'admissibility'];
        if (!is_array($record) || array_keys($record) !== $keys
            || !$this->identifier($record['provider']) || !$this->identifier($record['model_id']) || !$this->identifier($record['model_version'])
        ) {
            throw new \InvalidArgumentException('OR03_MODEL_IDENTITY_INVALID');
        }

        $sources = [];
        foreach ($record['knowledge_sources'] as $source) {
            if (!is_array($source) || array_keys($source) !== ['source_id', 'source_type', 'locator', 'observed_at', 'content_digest']
                || !$this->identifier($source['source_id']) || !$this->identifier($source['source_type'])
                || !is_string($source['locator']) || '' === trim($source['locator'])
                || !is_string($source['observed_at']) || false === \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $source['observed_at'])
                || !preg_match('/^sha256:[a-f0-9]{64}$/', (string) $source['content_digest'])
            ) {
                throw new \InvalidArgumentException('OR04_KNOWLEDGE_SOURCE_INVALID');
            }
            if (isset($sources[$source['source_id']])) throw new \RuntimeException('OR05_KNOWLEDGE_SOURCE_DUPLICATE');
            $sources[$source['source_id']] = $source;
        }
        if ([] === $sources) throw new \RuntimeException('OR06_KNOWN_MODEL_REQUIRES_SOURCE');
        ksort($sources, SORT_STRING);

        $claims = [];
        foreach ($record['claims'] as $claim) {
            if (!is_array($claim) || array_keys($claim) !== ['claim_id', 'subject', 'value', 'evidence_source_ids']
                || !$this->identifier($claim['claim_id']) || !is_string($claim['subject']) || '' === trim($claim['subject'])
                || !(is_scalar($claim['value']) || is_array($claim['value'])) || !$this->stringList($claim['evidence_source_ids'])
                || [] !== array_diff($claim['evidence_source_ids'], array_keys($sources))
            ) throw new \InvalidArgumentException('OR07_MODEL_CLAIM_INVALID');
            if (isset($claims[$claim['claim_id']])) throw new \RuntimeException('OR08_MODEL_CLAIM_DUPLICATE');
            $claims[$claim['claim_id']] = $claim;
        }
        ksort($claims, SORT_STRING);

        $accessibility = $this->validateAccessibility($record['accessibility']);
        if ('ACCESSIBLE' === $accessibility['status'] && $record['provider'] !== $accessibility['clavium_assertion']['provider']) {
            throw new \RuntimeException('OR11_CLAVIUM_ASSERTION_INVALID');
        }
        $admissibility = $this->validateAdmissibility($record['admissibility'], array_keys($sources));

        return [
            'model_ref' => $record['provider'].'/'.$record['model_id'].'@'.$record['model_version'],
            'provider' => $record['provider'], 'model_id' => $record['model_id'], 'model_version' => $record['model_version'],
            'knowledge' => ['status' => 'KNOWN', 'sources' => $sources, 'claims' => $claims],
            'accessibility' => $accessibility, 'admissibility' => $admissibility,
        ];
    }

    private function validateAccessibility(mixed $value): array
    {
        if (!is_array($value) || array_keys($value) !== ['status', 'clavium_assertion'] || !in_array($value['status'], self::ACCESS_STATES, true)) {
            throw new \InvalidArgumentException('OR09_ACCESS_CLASSIFICATION_INVALID');
        }
        $assertion = $value['clavium_assertion'];
        if ('ACCESSIBLE' !== $value['status']) {
            if (null !== $assertion) throw new \RuntimeException('OR10_NONACCESSIBLE_MODEL_HAS_ACCESS_ASSERTION');
            return $value;
        }
        if (!is_array($assertion) || 'imperium.clavium-provider-access-assertion/v1' !== ($assertion['schema'] ?? null)
            || !$this->identifier($assertion['assertion_id'] ?? null) || 'clavium' !== ($assertion['issuer']['office'] ?? null)
            || 'locksmith' !== ($assertion['issuer']['officer'] ?? null) || !$this->identifier($assertion['provider'])
            || !str_starts_with((string) $assertion['credential_ref'], 'clavium://') || !$this->stringList($assertion['scope'])
            || 'ACCESS_AVAILABLE' !== ($assertion['status'] ?? null)
            || 'CLAVIUM_PROVIDER_ACCESS_ASSERTION_SEALED_NO_USE_AUTHORITY' !== ($assertion['checkpoint'] ?? null)
            || false === \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, (string) ($assertion['observation']['observed_at'] ?? ''))
            || false === \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, (string) ($assertion['revalidation']['expires_at'] ?? ''))
            || true === ($assertion['credential_use_authority'] ?? null) || true === ($assertion['credential_disclosure_authority'] ?? null)
            || true === ($assertion['provider_invocation_authority'] ?? null) || true === ($assertion['model_selection_authority'] ?? null)
            || !preg_match('/^sha256:[a-f0-9]{64}$/', (string) $assertion['record_digest'])
            || isset($assertion['secret']) || isset($assertion['token']) || isset($assertion['api_key'])
        ) throw new \RuntimeException('OR11_CLAVIUM_ASSERTION_INVALID');
        if (new \DateTimeImmutable($assertion['revalidation']['expires_at']) <= new \DateTimeImmutable($assertion['observation']['observed_at'])) {
            throw new \RuntimeException('OR11_CLAVIUM_ASSERTION_INVALID');
        }
        $assertionDigest = $assertion['record_digest'];
        unset($assertion['record_digest']);
        if (!hash_equals($assertionDigest, 'sha256:'.hash('sha256', CanonicalJson::encode($assertion)))) {
            throw new \RuntimeException('OR11_CLAVIUM_ASSERTION_INVALID');
        }
        return $value;
    }

    private function validateAdmissibility(mixed $value, array $sourceIds): array
    {
        if (!is_array($value) || array_keys($value) !== ['status', 'policy_refs', 'evidence_source_ids', 'reasons']
            || !in_array($value['status'] ?? null, self::ADMISSIBILITY_STATES, true)
            || !$this->stringList($value['policy_refs'], 'UNEVALUATED' === $value['status'])
            || !$this->stringList($value['evidence_source_ids'], 'UNEVALUATED' === $value['status'])
            || [] !== array_diff($value['evidence_source_ids'], $sourceIds)
            || !$this->stringList($value['reasons'], 'ADMISSIBLE' === $value['status'] || 'UNEVALUATED' === $value['status'])
        ) throw new \InvalidArgumentException('OR12_ADMISSIBILITY_CLASSIFICATION_INVALID');
        return $value;
    }

    private function assertAugurAuthority(string $instanceId, array $binding): void
    {
        if ('oracle.augur' !== ($binding['seat'] ?? null) || 'oracle' !== ($binding['office'] ?? null)
            || !in_array($binding['status'] ?? null, ['ACTIVE', 'ORACLE_AUGUR_BOUND_ACTIVE_NO_MODEL_SELECTION_AUTHORITY'], true)
            || true !== ($binding['model_intelligence_stewardship_authority'] ?? null)
            || true === ($binding['model_selection_authority'] ?? null) || $instanceId !== ($binding['instance_id'] ?? null)
        ) throw new \RuntimeException('OR13_AUGUR_AUTHORITY_INVALID');
    }

    private function identifier(mixed $value): bool { return is_string($value) && 1 === preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._:@\/-]*$/', $value); }
    private function stringList(mixed $value, bool $emptyAllowed = false): bool { return is_array($value) && ($emptyAllowed || [] !== $value) && [] === array_filter($value, static fn ($item) => !is_string($item) || '' === trim($item)); }
    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function digestMatches(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }

    private function persist(string $id, array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        if (!is_dir($this->directory) && !mkdir($this->directory, 0770, true) && !is_dir($this->directory)) throw new \RuntimeException('OR14_LEDGER_PERSISTENCE_FAILED');
        $path = $this->directory.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) throw new \RuntimeException('OR15_LEDGER_REPLAY_CONFLICT');
            return $existing;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) throw new \RuntimeException('OR14_LEDGER_PERSISTENCE_FAILED');
        return $record;
    }
}
