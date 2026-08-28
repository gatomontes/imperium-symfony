<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\OutboundEmailAuthorizationIssuanceContract;
use App\Imperium\Runtime\Imperator\OutboundEmailAuthorizationIssuanceService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DeterministicExecutionClaimService
{
    public const string CLAIMS = 'var/imperium/la-cortine/deterministic-execution-claims';
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;
    private AtomicTransition $atomic;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->validator = new RecordReferenceValidator($root);
        $this->atomic = new AtomicTransition($root);
        $this->records = new ImmutableRecordStore($root, $this->atomic);
    }

    public function claim(string $issuanceId, CredentialCapability $credential, \DateTimeImmutable $claimedAt): array
    {
        if (!preg_match('/^outbound-email-authorization-issuance-[a-f0-9]{20}$/', $issuanceId)) {
            throw new \InvalidArgumentException('IGC400_OUTBOUND_EMAIL_ISSUANCE_ID_INVALID');
        }
        $issuance = $this->validator->read($this->root.'/'.OutboundEmailAuthorizationIssuanceService::ISSUANCES.'/'.$issuanceId.'.json', 'IGC401_OUTBOUND_EMAIL_ISSUANCE_ABSENT');
        $authorization = $issuance['issued_authorization'] ?? null;
        if (!$this->validator->isIntact($issuance)
            || OutboundEmailAuthorizationIssuanceContract::REQUIRED_ISSUANCE_FIELDS !== array_keys($issuance)
            || OutboundEmailAuthorizationIssuanceContract::ISSUANCE_SCHEMA !== ($issuance['schema'] ?? null)
            || $issuanceId !== ($issuance['issuance_id'] ?? null)
            || true !== ($issuance['authority_issued'] ?? null)
            || !is_array($authorization)
            || DeterministicOutboundEmailAuthorizationContract::REQUIRED_FIELDS !== array_keys($authorization)
            || !$this->validator->isIntact($authorization)
            || !is_array($authorization['source_decision'] ?? null)
            || !is_array($authorization['issuer'] ?? null)
            || !is_array($authorization['holder'] ?? null)
            || !is_array($authorization['scope'] ?? null)
            || !is_array($authorization['provider_safety'] ?? null)
            || DeterministicOutboundEmailAuthorizationContract::REQUIRED_SOURCE_DECISION_FIELDS !== array_keys($authorization['source_decision'] ?? [])
            || DeterministicOutboundEmailAuthorizationContract::REQUIRED_ACTOR_FIELDS !== array_keys($authorization['issuer'] ?? [])
            || DeterministicOutboundEmailAuthorizationContract::REQUIRED_ACTOR_FIELDS !== array_keys($authorization['holder'] ?? [])
            || DeterministicOutboundEmailAuthorizationContract::REQUIRED_SCOPE_FIELDS !== array_keys($authorization['scope'] ?? [])
            || DeterministicOutboundEmailAuthorizationContract::REQUIRED_PROVIDER_SAFETY_FIELDS !== array_keys($authorization['provider_safety'] ?? [])
            || 'AUTHORIZED' !== ($authorization['source_decision']['decision'] ?? null)
            || true !== ($authorization['single_use'] ?? null)
            || true !== ($authorization['exercisable'] ?? null)
            || false !== ($authorization['consumed'] ?? null)
            || true === ($authorization['continuing_authority'] ?? null)
            || new \DateTimeImmutable((string) ($authorization['issued_at'] ?? '1970-01-01')) > $claimedAt
            || new \DateTimeImmutable((string) ($authorization['expires_at'] ?? '1970-01-01')) <= $claimedAt) {
            throw new \RuntimeException('IGC402_OUTBOUND_EMAIL_AUTHORIZATION_INVALID');
        }

        $scope = $authorization['scope'];
        $provider = $authorization['provider_safety'];
        if ($credential->commissionId !== ($scope['commission_id'] ?? null)
            || $credential->operation !== ($scope['operation'] ?? null)
            || !hash_equals((string) ($scope['credential_reference_digest'] ?? ''), hash('sha256', $credential->credentialRef))
            || 1 !== $credential->maxUses
            || $credential->expiresAt <= $claimedAt
            || $credential->expiresAt > new \DateTimeImmutable($authorization['expires_at'])) {
            throw new \RuntimeException('IGC403_CREDENTIAL_CAPABILITY_SCOPE_INVALID');
        }

        $request = [
            'id' => $issuance['source_request']['id'],
            'commission_id' => $scope['commission_id'],
            'authorization_id' => $authorization['authorization_id'],
            'authorization_digest' => $authorization['record_digest'],
            'mode' => 'DETERMINISTIC',
            'operation' => $scope['operation'],
            'destination' => $scope['destination'],
            'payload_digest' => $scope['payload_digest'],
            'expected_return_contract' => $scope['expected_return_contract'],
        ];
        $credentialMetadata = [
            'capability_id' => $credential->capabilityId,
            'credential_reference_digest' => hash('sha256', $credential->credentialRef),
            'commission_id' => $credential->commissionId,
            'operation' => $credential->operation,
            'expires_at' => $credential->expiresAt->format(DATE_ATOM),
            'max_uses' => $credential->maxUses,
        ];
        $replayFingerprint = hash('sha256', CanonicalJson::encode([$authorization['record_digest'], $request, $credentialMetadata, $provider['request_fingerprint'], $provider['idempotency_key_digest']]));
        $claimId = 'deterministic-execution-claim-'.substr(hash('sha256', CanonicalJson::encode([$authorization['authorization_id'], $replayFingerprint])), 0, 20);
        $executionId = 'deterministic-execution-'.substr(hash('sha256', $claimId), 0, 20);
        $holder = [
            'actor_id' => $authorization['holder']['actor_id'],
            'office' => $authorization['holder']['office'],
            'seat' => $authorization['holder']['seat'],
            'runtime_principal_id' => $authorization['holder']['runtime_principal_id'],
            'competent_service' => 'la-cortine.deterministic-boundary-executor',
        ];
        $record = [
            'schema' => DeterministicExecutionClaimContract::SCHEMA,
            'claim_id' => $claimId,
            'instance_id' => $authorization['instance_id'],
            'source_authorization' => ['id' => $authorization['authorization_id'], 'digest' => $authorization['record_digest'], 'schema' => $authorization['schema'], 'issuer' => $authorization['issuer'], 'decision_owner' => $authorization['source_decision']['decision_owner']],
            'authorization_consumption' => ['authority_id' => $authorization['authorization_id'], 'source_digest' => $authorization['record_digest'], 'consumed_at' => $claimedAt->format(DATE_ATOM), 'consumed' => true, 'continuing_authority' => false],
            'request' => $request,
            'holder' => $holder,
            'replay_fingerprint' => $replayFingerprint,
            'execution_identity' => ['execution_id' => $executionId, 'single_use' => true, 'winner_scope' => 'authorization:'.$authorization['authorization_id'], 'lock_order' => ['authorization', 'execution-claim']],
            'credential_capability' => $credentialMetadata,
            'provider_safety' => ['strategy' => $provider['strategy'], 'provider_idempotency_key' => $provider['idempotency_key'], 'provider_contract_reference' => $provider['provider_contract_reference'], 'automatic_replay_permitted' => false, 'unknown_outcome_status' => 'NOT_STARTED'],
            'effect' => ['checkpoint' => 'CLAIMED_PRE_IO', 'external_io_started' => false, 'outcome' => 'NOT_ATTEMPTED', 'effect_started_at' => null, 'resolved_at' => null],
            'claimed_at' => $claimedAt->format(DATE_ATOM),
            'expires_at' => $credential->expiresAt->format(DATE_ATOM),
            'sealed' => true,
        ];

        return $this->atomic->run('iron-gate-email-authorization:'.$authorization['authorization_id'], function () use ($authorization, $claimId, $record, $replayFingerprint): array {
            foreach (glob($this->root.'/'.self::CLAIMS.'/*.json') ?: [] as $path) {
                $prior = $this->validator->read($path, 'IGC404_EXECUTION_CLAIM_CONFLICT');
                if (($prior['source_authorization']['id'] ?? null) !== $authorization['authorization_id']) continue;
                if (!$this->validator->isIntact($prior) || ($prior['claim_id'] ?? null) !== $claimId || ($prior['replay_fingerprint'] ?? null) !== $replayFingerprint) {
                    throw new \RuntimeException('IGC404_EXECUTION_CLAIM_CONFLICT');
                }
                return $prior;
            }
            return $this->records->put(self::CLAIMS, $claimId, $record);
        });
    }
}
