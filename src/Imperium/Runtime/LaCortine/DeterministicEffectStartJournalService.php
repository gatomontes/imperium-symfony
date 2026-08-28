<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DeterministicEffectStartJournalService
{
    public const string JOURNALS = 'var/imperium/la-cortine/deterministic-effect-start-journals';
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;
    private AtomicTransition $atomic;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->validator = new RecordReferenceValidator($root);
        $this->atomic = new AtomicTransition($root);
        $this->records = new ImmutableRecordStore($root, $this->atomic);
    }

    public function start(string $claimId, \DateTimeImmutable $startedAt): array
    {
        if (!preg_match('/^deterministic-execution-claim-[a-f0-9]{20}$/', $claimId)) {
            throw new \InvalidArgumentException('IGJ500_EXECUTION_CLAIM_ID_INVALID');
        }
        $claim = $this->validator->read($this->root.'/'.DeterministicExecutionClaimService::CLAIMS.'/'.$claimId.'.json', 'IGJ501_EXECUTION_CLAIM_ABSENT');
        if (!$this->validator->isIntact($claim)
            || DeterministicExecutionClaimContract::REQUIRED_FIELDS !== array_keys($claim)
            || DeterministicExecutionClaimContract::SCHEMA !== ($claim['schema'] ?? null)
            || $claimId !== ($claim['claim_id'] ?? null)
            || true !== ($claim['authorization_consumption']['consumed'] ?? null)
            || false !== ($claim['authorization_consumption']['continuing_authority'] ?? null)
            || 'CLAIMED_PRE_IO' !== ($claim['effect']['checkpoint'] ?? null)
            || false !== ($claim['effect']['external_io_started'] ?? null)
            || 'NOT_ATTEMPTED' !== ($claim['effect']['outcome'] ?? null)
            || true !== ($claim['execution_identity']['single_use'] ?? null)
            || 1 !== ($claim['credential_capability']['max_uses'] ?? null)
            || new \DateTimeImmutable((string) ($claim['claimed_at'] ?? '1970-01-01')) > $startedAt
            || new \DateTimeImmutable((string) ($claim['expires_at'] ?? '1970-01-01')) <= $startedAt) {
            throw new \RuntimeException('IGJ502_EXECUTION_CLAIM_NOT_STARTABLE');
        }
        $provider = $claim['provider_safety'];
        if ('PROVIDER_IDEMPOTENCY_KEY' !== ($provider['strategy'] ?? null)
            || true === ($provider['automatic_replay_permitted'] ?? null)
            || !is_string($provider['provider_idempotency_key'] ?? null)
            || '' === trim($provider['provider_idempotency_key'])) {
            throw new \RuntimeException('IGJ503_PROVIDER_SAFETY_INVALID');
        }

        $requestFingerprint = hash('sha256', CanonicalJson::encode([$claim['request'], $claim['credential_capability'], $claim['replay_fingerprint']]));
        $journalId = 'deterministic-effect-start-journal-'.substr(hash('sha256', CanonicalJson::encode([$claimId, $claim['record_digest'], $requestFingerprint, $provider['provider_idempotency_key']])), 0, 20);
        $record = [
            'schema' => DeterministicEffectStartJournalContract::SCHEMA,
            'journal_id' => $journalId,
            'instance_id' => $claim['instance_id'],
            'execution_claim' => ['id' => $claimId, 'digest' => $claim['record_digest'], 'replay_fingerprint' => $claim['replay_fingerprint'], 'execution_id' => $claim['execution_identity']['execution_id']],
            'source_authorization' => ['id' => $claim['source_authorization']['id'], 'digest' => $claim['source_authorization']['digest']],
            'credential_use' => ['capability_id' => $claim['credential_capability']['capability_id'], 'credential_reference_digest' => $claim['credential_capability']['credential_reference_digest'], 'consumption_required' => true, 'consumed_by_journal' => false, 'credential_resolved' => false],
            'provider_safety' => ['strategy' => $provider['strategy'], 'provider_idempotency_key' => $provider['provider_idempotency_key'], 'request_fingerprint' => $requestFingerprint, 'provider_contract_reference' => $provider['provider_contract_reference'], 'automatic_replay_permitted' => false],
            'effect' => ['checkpoint' => 'EFFECT_STARTED', 'external_io_may_have_started' => true, 'outcome' => 'UNKNOWN_REPLAY_PROHIBITED', 'provider_invoked_by_transition' => false, 'resolved_at' => null],
            'started_at' => $startedAt->format(DATE_ATOM),
            'expires_at' => $claim['expires_at'],
            'sealed' => true,
        ];

        return $this->atomic->run('iron-gate-effect-start:'.$claimId, function () use ($claimId, $journalId, $record, $requestFingerprint): array {
            foreach (glob($this->root.'/'.self::JOURNALS.'/*.json') ?: [] as $path) {
                $prior = $this->validator->read($path, 'IGJ504_EFFECT_START_CONFLICT');
                if (($prior['execution_claim']['id'] ?? null) !== $claimId) continue;
                if (!$this->validator->isIntact($prior) || ($prior['journal_id'] ?? null) !== $journalId || ($prior['provider_safety']['request_fingerprint'] ?? null) !== $requestFingerprint) {
                    throw new \RuntimeException('IGJ504_EFFECT_START_CONFLICT');
                }
                return $prior;
            }
            return $this->records->put(self::JOURNALS, $journalId, $record);
        });
    }
}
