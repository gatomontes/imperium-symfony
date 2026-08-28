<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\OutboundEmailAuthorizationIssuanceContract;
use App\Imperium\Runtime\LaCortine\DeterministicOutboundEmailAuthorizationContract;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OutboundEmailAuthorizationRequestService
{
    public const string REQUESTS = 'var/imperium/offices/curia/deterministic-outbound-email-requests';
    private const string OCCUPANCY = 'var/imperium/offices/curia/occupancy';
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;
    private AtomicTransition $atomic;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->validator = new RecordReferenceValidator($root);
        $this->atomic = new AtomicTransition($root);
        $this->records = new ImmutableRecordStore($root, $this->atomic);
    }

    public function request(string $bindingId, array $holder, string $purpose, array $scope, array $providerSafety, \DateTimeImmutable $expiresAt, \DateTimeImmutable $requestedAt): array
    {
        $purpose = trim($purpose);
        if (!preg_match('/^curia-seneschal-binding-[a-f0-9]{20}$/', $bindingId) || '' === $purpose || $expiresAt <= $requestedAt || $expiresAt > $requestedAt->modify('+15 minutes')) {
            throw new \InvalidArgumentException('IGR100_OUTBOUND_EMAIL_REQUEST_INVALID');
        }
        $occupancy = $this->validator->read($this->root.'/'.self::OCCUPANCY.'/'.$bindingId.'.json', 'IGR101_SENESCHAL_OCCUPANCY_ABSENT');
        if (!$this->validator->isIntact($occupancy) || 'imperium.curia-seneschal-occupancy/v1' !== ($occupancy['schema'] ?? null) || $bindingId !== ($occupancy['binding_id'] ?? null) || 'curia.seneschal' !== ($occupancy['seat'] ?? null) || 'ACTIVE' !== ($occupancy['status'] ?? null) || true !== ($occupancy['outbound_email_request_authority'] ?? null) || true === ($occupancy['execution_authority'] ?? null)) {
            throw new \RuntimeException('IGR102_SENESCHAL_REQUEST_AUTHORITY_INVALID');
        }
        $this->exactActor($holder);
        $this->exact($scope, DeterministicOutboundEmailAuthorizationContract::REQUIRED_SCOPE_FIELDS, 'IGR103_OUTBOUND_EMAIL_SCOPE_INVALID');
        $this->exact($providerSafety, DeterministicOutboundEmailAuthorizationContract::REQUIRED_PROVIDER_SAFETY_FIELDS, 'IGR104_PROVIDER_SAFETY_INVALID');
        foreach ($scope as $value) if (!is_string($value) || '' === trim($value)) throw new \InvalidArgumentException('IGR103_OUTBOUND_EMAIL_SCOPE_INVALID');
        foreach ($providerSafety as $value) if (!is_string($value) || '' === trim($value)) throw new \InvalidArgumentException('IGR104_PROVIDER_SAFETY_INVALID');
        foreach (['recipient_set_digest', 'subject_digest', 'body_digest', 'attachment_manifest_digest', 'payload_digest', 'credential_reference_digest'] as $digest) {
            if (!preg_match('/^[a-f0-9]{64}$/', $scope[$digest])) throw new \InvalidArgumentException('IGR103_OUTBOUND_EMAIL_SCOPE_INVALID');
        }
        if ('email.send' !== $scope['operation'] || 'PROVIDER_IDEMPOTENCY_KEY' !== $providerSafety['strategy'] || 'agentmail' !== $providerSafety['provider'] || $scope['destination'] !== $providerSafety['endpoint'] || !hash_equals(hash('sha256', $providerSafety['idempotency_key']), $providerSafety['idempotency_key_digest']) || !preg_match('/^[a-f0-9]{64}$/', $providerSafety['request_fingerprint']) || new \DateTimeImmutable($providerSafety['provider_key_expires_at']) < $expiresAt) {
            throw new \InvalidArgumentException('IGR105_OUTBOUND_EMAIL_REQUEST_SCOPE_MISMATCH');
        }
        $requester = ['actor_id' => $occupancy['manifestation_id'], 'office' => 'curia', 'seat' => 'curia.seneschal', 'binding_id' => $bindingId, 'runtime_principal_id' => $bindingId];
        $fingerprint = [$occupancy['instance_id'], $requester, $holder, $purpose, $scope, $providerSafety, $requestedAt->format(DATE_ATOM), $expiresAt->format(DATE_ATOM)];
        $requestId = 'outbound-email-request-'.substr(hash('sha256', CanonicalJson::encode($fingerprint)), 0, 20);
        $record = ['schema' => OutboundEmailAuthorizationIssuanceContract::REQUEST_SCHEMA, 'request_id' => $requestId, 'instance_id' => $occupancy['instance_id'], 'requester' => $requester, 'holder' => $holder, 'purpose' => $purpose, 'scope' => $scope, 'provider_safety' => $providerSafety, 'requested_at' => $requestedAt->format(DATE_ATOM), 'expires_at' => $expiresAt->format(DATE_ATOM), 'authority_requested' => true, 'authority_granted' => false, 'sealed' => true];

        return $this->atomic->run('iron-gate-email-request:'.$requestId, function () use ($requestId, $record): array {
            return $this->records->put(self::REQUESTS, $requestId, $record);
        });
    }

    private function exactActor(array $actor): void
    {
        $this->exact($actor, DeterministicOutboundEmailAuthorizationContract::REQUIRED_ACTOR_FIELDS, 'IGR107_HOLDER_INVALID');
        foreach ($actor as $value) if (!is_string($value) || '' === trim($value)) throw new \InvalidArgumentException('IGR107_HOLDER_INVALID');
    }

    private function exact(array $value, array $keys, string $error): void
    {
        if (array_keys($value) !== $keys) throw new \InvalidArgumentException($error);
    }
}
