<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Armory;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class CanonicalEmailSendToolDefinitionService
{
    public const string DEFINITIONS = 'var/imperium/offices/armory/governed-tool-definitions';
    public const string TOOL_ID = 'email.send';
    public const string TOOL_VERSION = '1';
    public const string PAYLOAD_SCHEMA = 'imperium.armory.email-send-payload/v1';
    public const string NORMALIZED_RESULT_SCHEMA = 'imperium.armory.email-send-normalized-result/v1';
    public const string EFFECT_CLASS = 'IRREVERSIBLE_EXTERNAL_COMMUNICATION';

    public const array PAYLOAD_SEMANTICS = [
        'required_fields' => ['to', 'subject'],
        'content_fields' => ['text', 'html'],
        'at_least_one_content_field_required' => true,
        'recipient_set_must_be_non_empty' => true,
        'attachments_optional' => true,
        'attachment_fields' => ['content', 'filename', 'content_type'],
        'exact_serialized_bytes_authorized' => true,
        'credential_material_permitted' => false,
        'provider_fields_permitted' => false,
    ];

    public const array NORMALIZED_RESULT_SEMANTICS = [
        'required_fields' => ['status', 'provider_evidence_reference'],
        'statuses' => ['ACCEPTED', 'REJECTED', 'UNKNOWN_REPLAY_PROHIBITED'],
        'provider_assigned_attributes_typed_optional' => true,
        'raw_provider_evidence_required' => true,
        'provider_reinvocation_permitted' => false,
        'automatic_replay_permitted' => false,
    ];

    private ImmutableRecordStore $records;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->records = new ImmutableRecordStore($root, new AtomicTransition($root));
    }

    public function define(\DateTimeImmutable $sealedAt): array
    {
        $record = [
            'schema' => GovernedToolOperationContract::SCHEMA,
            'tool_id' => self::TOOL_ID,
            'tool_version' => self::TOOL_VERSION,
            'owner' => ['office' => 'armory', 'seat' => 'armory.armorer'],
            'operation' => self::TOOL_ID,
            'payload_contract' => [
                'schema' => self::PAYLOAD_SCHEMA,
                'digest_algorithm' => 'sha256',
                'exact_bytes_required' => true,
            ],
            'effect_class' => self::EFFECT_CLASS,
            'normalized_return_contract' => self::NORMALIZED_RESULT_SCHEMA,
            'secret_policy' => [
                'payload_may_contain_credentials' => false,
                'provider_adapter_may_receive_opaque_authentication' => true,
            ],
            'provider_policy' => [
                'provider_neutral' => true,
                'provider_binding_required' => true,
                'provider_substitution_permitted' => false,
            ],
            'status' => 'DEFINED_INACTIVE',
            'sealed_at' => $sealedAt->format(DATE_ATOM),
            'sealed' => true,
        ];

        $definition = $this->records->put(self::DEFINITIONS, 'email.send.v1', $record);
        $this->assertCanonical($definition);

        return $definition;
    }

    public function read(): array
    {
        $definition = $this->records->read(self::DEFINITIONS, 'email.send.v1');
        $this->assertCanonical($definition);

        return $definition;
    }

    private function assertCanonical(array $definition): void
    {
        if (GovernedToolOperationContract::REQUIRED_FIELDS !== array_keys($definition)
            || GovernedToolOperationContract::SCHEMA !== ($definition['schema'] ?? null)
            || self::TOOL_ID !== ($definition['tool_id'] ?? null)
            || self::TOOL_VERSION !== ($definition['tool_version'] ?? null)
            || ['office' => 'armory', 'seat' => 'armory.armorer'] !== ($definition['owner'] ?? null)
            || self::TOOL_ID !== ($definition['operation'] ?? null)
            || ['schema' => self::PAYLOAD_SCHEMA, 'digest_algorithm' => 'sha256', 'exact_bytes_required' => true] !== ($definition['payload_contract'] ?? null)
            || self::EFFECT_CLASS !== ($definition['effect_class'] ?? null)
            || self::NORMALIZED_RESULT_SCHEMA !== ($definition['normalized_return_contract'] ?? null)
            || ['payload_may_contain_credentials' => false, 'provider_adapter_may_receive_opaque_authentication' => true] !== ($definition['secret_policy'] ?? null)
            || ['provider_neutral' => true, 'provider_binding_required' => true, 'provider_substitution_permitted' => false] !== ($definition['provider_policy'] ?? null)
            || 'DEFINED_INACTIVE' !== ($definition['status'] ?? null)
            || true !== ($definition['sealed'] ?? null)) {
            throw new \RuntimeException('GTP200_CANONICAL_EMAIL_SEND_TOOL_DEFINITION_INVALID');
        }
    }
}
