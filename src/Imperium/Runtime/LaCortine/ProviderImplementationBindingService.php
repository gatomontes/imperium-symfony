<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Imperium\Runtime\Armory\CanonicalEmailSendToolDefinitionService;
use App\Imperium\Runtime\Armory\GovernedToolOperationContract;
use App\Imperium\Runtime\Imperator\ProviderBindingAuthorizationContract;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\AuthorityConsumptionStore;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderImplementationBindingService
{
    public const string AUTHORITIES = 'var/imperium/offices/imperator/provider-binding-authorities';
    public const string BINDINGS = 'var/imperium/offices/la-cortine/provider-implementation-bindings';
    private const string CONSUMER = 'la-cortine.provider-implementation-binding';

    private ImmutableRecordStore $records;
    private RecordReferenceValidator $validator;
    private AuthorityConsumptionStore $consumptions;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $atomic = new AtomicTransition($root);
        $this->records = new ImmutableRecordStore($root, $atomic);
        $this->validator = new RecordReferenceValidator($root);
        $this->consumptions = new AuthorityConsumptionStore($this->records, $atomic);
    }

    public function bind(string $authorityId, \DateTimeImmutable $at): array
    {
        if (!preg_match('/^provider-binding-authority-[a-f0-9]{20}$/', $authorityId)) {
            throw new \InvalidArgumentException('GTP300_PROVIDER_BINDING_AUTHORITY_ID_INVALID');
        }

        $authority = $this->validator->read($this->root.'/'.self::AUTHORITIES.'/'.$authorityId.'.json', 'GTP301_PROVIDER_BINDING_AUTHORITY_ABSENT');
        $this->assertAuthority($authorityId, $authority, $at);

        $tool = (new CanonicalEmailSendToolDefinitionService($this->root))->read();
        $toolReference = $authority['tool_operation'];
        if ($toolReference['id'] !== $tool['tool_id'].'.v'.$tool['tool_version']
            || $toolReference['digest'] !== $tool['record_digest']
            || GovernedToolOperationContract::SCHEMA !== $toolReference['schema']
            || 'DEFINED_INACTIVE' !== $tool['status']) {
            throw new \RuntimeException('GTP303_PROVIDER_BINDING_TOOL_TARGET_INVALID');
        }

        $bindingId = 'provider-implementation-binding-'.substr(hash('sha256', $authorityId.'|'.$authority['record_digest']), 0, 20);
        $binding = $this->records->put(self::BINDINGS, $bindingId, [
            'schema' => ProviderImplementationBindingContract::SCHEMA,
            'binding_id' => $bindingId,
            'instance_id' => $authority['instance_id'],
            'source_authority' => ['id' => $authorityId, 'digest' => $authority['record_digest'], 'schema' => ProviderBindingAuthorizationContract::SCHEMA],
            'tool_operation' => $toolReference,
            'provider_implementation' => $authority['provider_implementation'],
            'assurance_profile' => $authority['assurance_profile'],
            'credential_family' => $authority['credential_family'],
            'request_encoder' => $authority['request_encoder'],
            'evidence_decoder' => $authority['evidence_decoder'],
            'destination_policy' => $authority['destination_policy'],
            'scope' => $authority['scope'],
            'validity' => ['effective_at' => $authority['issued_at'], 'expires_at' => $authority['expires_at']],
            'status' => 'BOUND_INACTIVE',
            'bound_at' => $at->format(DATE_ATOM),
            'sealed' => true,
        ]);

        $this->consumptions->consume($authorityId, $authorityId, $authority['record_digest'], self::CONSUMER, $at);

        return $binding;
    }

    private function assertAuthority(string $authorityId, array $authority, \DateTimeImmutable $at): void
    {
        if (!$this->validator->isIntact($authority)
            || ProviderBindingAuthorizationContract::REQUIRED_FIELDS !== array_keys($authority)
            || ProviderBindingAuthorizationContract::SCHEMA !== ($authority['schema'] ?? null)
            || $authorityId !== ($authority['authority_id'] ?? null)
            || !is_array($authority['source'] ?? null)
            || ProviderBindingAuthorizationContract::REQUIRED_SOURCE_FIELDS !== array_keys($authority['source'])
            || !is_string($authority['source']['office']) || '' === trim($authority['source']['office'])
            || !is_string($authority['source']['seat']) || '' === trim($authority['source']['seat'])
            || !is_string($authority['source']['id']) || '' === trim($authority['source']['id'])
            || !is_string($authority['source']['digest']) || !preg_match('/^[a-f0-9]{64}$/', $authority['source']['digest'])
            || !$this->hasExactBindingShape($authority)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || false !== ($authority['continuing_authority'] ?? null)
            || true !== ($authority['sealed'] ?? null)
            || new \DateTimeImmutable((string) $authority['issued_at']) > $at
            || new \DateTimeImmutable((string) $authority['expires_at']) <= $at) {
            throw new \RuntimeException('GTP302_PROVIDER_BINDING_AUTHORITY_INVALID');
        }
    }

    private function hasExactBindingShape(array $authority): bool
    {
        foreach (['tool_operation', 'assurance_profile', 'request_encoder', 'evidence_decoder'] as $field) {
            if (!is_array($authority[$field] ?? null)
                || ProviderImplementationBindingContract::REQUIRED_REFERENCE_FIELDS !== array_keys($authority[$field])
                || !is_string($authority[$field]['id']) || '' === trim($authority[$field]['id'])
                || !is_string($authority[$field]['digest']) || !preg_match('/^[a-f0-9]{64}$/', $authority[$field]['digest'])
                || !is_string($authority[$field]['schema']) || '' === trim($authority[$field]['schema'])) {
                return false;
            }
        }

        return is_array($authority['provider_implementation'] ?? null)
            && ProviderImplementationBindingContract::REQUIRED_PROVIDER_IMPLEMENTATION_FIELDS === array_keys($authority['provider_implementation'])
            && is_string($authority['provider_implementation']['provider_id']) && '' !== trim($authority['provider_implementation']['provider_id'])
            && is_string($authority['provider_implementation']['adapter_id']) && '' !== trim($authority['provider_implementation']['adapter_id'])
            && is_string($authority['provider_implementation']['adapter_version']) && '' !== trim($authority['provider_implementation']['adapter_version'])
            && is_array($authority['credential_family'] ?? null)
            && ProviderImplementationBindingContract::REQUIRED_CREDENTIAL_FAMILY_FIELDS === array_keys($authority['credential_family'])
            && is_string($authority['credential_family']['family_id']) && '' !== trim($authority['credential_family']['family_id'])
            && false === $authority['credential_family']['secret_persistence_permitted']
            && $authority['credential_family']['provider_id'] === $authority['provider_implementation']['provider_id']
            && is_array($authority['destination_policy'] ?? null)
            && ProviderImplementationBindingContract::REQUIRED_DESTINATION_POLICY_FIELDS === array_keys($authority['destination_policy'])
            && is_string($authority['destination_policy']['policy_id']) && '' !== trim($authority['destination_policy']['policy_id'])
            && is_string($authority['destination_policy']['policy_digest']) && 1 === preg_match('/^[a-f0-9]{64}$/', $authority['destination_policy']['policy_digest'])
            && true === $authority['destination_policy']['exact_destination_required']
            && is_array($authority['scope'] ?? null)
            && ProviderImplementationBindingContract::REQUIRED_SCOPE_FIELDS === array_keys($authority['scope'])
            && 'email.send' === $authority['scope']['operation']
            && is_string($authority['scope']['authorization_target_id']) && '' !== trim($authority['scope']['authorization_target_id'])
            && is_string($authority['scope']['authorization_target_digest']) && 1 === preg_match('/^[a-f0-9]{64}$/', $authority['scope']['authorization_target_digest'])
            && false === $authority['scope']['provider_substitution_permitted'];
    }
}
