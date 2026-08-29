<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class AgentMailProviderProfile
{
    public const string SCHEMA = 'imperium.la-cortine.agentmail-provider-profile/v1';
    public const string PROVIDER_ID = 'agentmail';
    public const string ADAPTER_ID = 'agentmail.email-send';
    public const string ADAPTER_VERSION = '1';
    public const string ASSURANCE_PROFILE_ID = 'agentmail.email-send-assurance.v1';
    public const string CREDENTIAL_FAMILY_ID = 'agentmail.api-key.v1';
    public const string CREDENTIAL_REFERENCE_SYNTAX = 'env:AGENTMAIL_API_KEY';
    public const string REQUEST_ENCODER_ID = 'agentmail.email-send-request-encoder.v1';
    public const string EVIDENCE_DECODER_ID = 'agentmail.email-send-evidence-decoder.v1';
    public const string ENDPOINT_PATTERN = '#^https://api\.agentmail\.to/v0/inboxes/[^/]+/messages/send$#';
    public const string AUTHORIZATION_SCHEME = 'Bearer';
    public const array RECEIPT_FIELDS = ['message_id', 'thread_id'];

    public const array BOUNDARY = [
        'resolves_credentials' => false,
        'starts_external_io' => false,
        'admits_evidence' => false,
        'changes_live_consumer' => false,
    ];

    private function __construct()
    {
    }
}
