# Canonical governed `email.send` tool definition

## Status

`BATCH_2_CANONICAL_EMAIL_SEND_TOOL_DEFINED_INACTIVE`

Armory now owns one immutable, provider-neutral version-1 definition for `email.send`. The record is
produced by `CanonicalEmailSendToolDefinitionService` under the Batch 1
`GovernedToolOperationContract` and stored at the fixed identity `email.send.v1`.

The definition is `DEFINED_INACTIVE`. Definition is not availability, assignment, authorization,
provider binding, credential access or execution authority.

## Exact identity

| Field | Canonical value |
| --- | --- |
| Tool ID | `email.send` |
| Version | `1` |
| Owner | `armory.armorer` |
| Operation | `email.send` |
| Payload schema | `imperium.armory.email-send-payload/v1` |
| Payload digest | `sha256` over exact serialized bytes |
| Effect class | `IRREVERSIBLE_EXTERNAL_COMMUNICATION` |
| Normalized result | `imperium.armory.email-send-normalized-result/v1` |
| Status | `DEFINED_INACTIVE` |

## Payload semantics

The provider-neutral payload requires a non-empty recipient set and subject, at least one of `text`
or `html`, and permits typed attachments with exact content, filename and content type. The exact
serialized payload bytes are what later authority must bind. Credential material and provider fields
are forbidden in the tool payload.

These semantics describe the external effect. They do not specify a provider endpoint, transport
headers, provider idempotency key or provider receipt shape.

## Normalized result semantics

The provider-neutral result requires a truthful status and exact raw-provider-evidence reference.
Allowed outcomes are `ACCEPTED`, `REJECTED` and `UNKNOWN_REPLAY_PROHIBITED`. Provider-assigned
attributes may be preserved only as typed optional attributes. Automatic replay and provider
reinvocation are forbidden.

## Provider and secret policy

No provider identity is present in the definition. A separately governed provider binding is
mandatory and provider substitution is forbidden. Tool payloads cannot contain credentials. A
future exact bound adapter may receive opaque authentication only inside the already governed
credential callback.

## Persistence and replay

The fixed record is sealed through `ImmutableRecordStore`. An exact replay with the same definition
and sealing time returns the immutable record. A changed definition or sealing time conflicts with
`PST111_IMMUTABLE_RECORD_CONFLICT`; no replacement or silent version drift occurs.

## Closed boundary

The canonical tool is inactive. No provider is selected or bound; no credential is issued or
resolved; no request is encoded; no email is sent; no external I/O occurs; no provider evidence is
decoded or admitted; and no existing AgentMail command, Iron Gate, Lazaretto, inbound webhook or
sortie consumer is migrated.
