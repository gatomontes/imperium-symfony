# Governed Tool and Provider Separation — Preparation Batch 0 inventory

## Result

`PREPARATION_BATCH_0_COMPLETE`

The deterministic substrate already carries separate operation, tool ID, capability ID,
destination, payload digest and expected-return identities. That is a usable generic foundation.
The completed outbound-email evidence corridor nevertheless binds AgentMail as a constitutional
fact instead of a replaceable implementation. This is not confined to one transport class.

The direct AgentMail name or contract appears in 28 source and test files: ten production PHP files
and eighteen tests. Seven production files belong to the outbound corridor; the inbound controller,
webhook verifier and inbound Lazaretto are inventoried but remain outside this campaign. The
migration must preserve the completed authority, crash,
idempotency, evidence-authenticity and reconstruction proofs rather than generalize them away.

Runtime behavior is unchanged. No external I/O or credential resolution occurred.

## Exact coupling inventory

| Surface | Current coupling | Classification | Consumer posture |
| --- | --- | --- | --- |
| Generic outbound request | `OutboundRequest` separately carries `operation`, `toolIds`, `capabilityIds`, destinations and return contract. | `EXISTS_CANONICALLY` | `GENERIC_IDENTITY_SUBSTRATE` |
| Generic boundary dispatch | `IronGate` propagates allowed tool/capability IDs without selecting a transport. | `EXISTS_CANONICALLY` | `PROVIDER_SELECTION_NOT_OWNED` |
| Generic transport seam | `DeterministicTransport::supports()` and `execute()` accept an operation, destination, payload and opaque authentication. | `EXISTS_FRAGMENTED` | `UNBOUND_IMPLEMENTATION_SEAM` |
| Generic result seam | `TransportResult` returns opaque content, source IDs and observation time. | `EXISTS_FRAGMENTED` | `NO_NORMALIZED_TOOL_RESULT` |
| Generic Lazaretto | `Lazaretto` validates dispatch lineage, tool IDs and capability IDs without knowing AgentMail. | `EXISTS_CANONICALLY` | `GENERIC_LINEAGE_ONLY` |
| Canonical deterministic tool definition | No Armory-owned or equivalent immutable definition binds `email.send` payload, effect and normalized result semantics. A string ID is passed directly. | `ABSENT` | `TOOL_CONTRACT_REQUIRED` |
| Provider implementation binding | No immutable record separately selects adapter, endpoint rules, decoder, assurance profile and credential family for one execution. | `ABSENT` | `PROVIDER_BINDING_REQUIRED` |
| Curia request validation | `OutboundEmailAuthorizationRequestService` requires provider `agentmail` and matches the literal AgentMail URL. | `EXISTS_CANONICALLY` | `FUSED_AUTHORITY_PROVIDER_SCOPE` |
| Authorization schema | `DeterministicOutboundEmailAuthorizationContract` embeds `inbox_id`, provider, endpoint, key and provider-contract reference beside tool scope. | `EXISTS_CANONICALLY` | `VERSIONED_MIGRATION_REQUIRED` |
| Provider request encoding | `AgentMailIdempotencyHeaderAdapter` validates the literal host/path and constructs AgentMail headers. | `EXISTS_CANONICALLY` | `AGENTMAIL_ADAPTER_CANDIDATE` |
| Credential-bound invocation | `DeterministicJournalBoundCredentialBroker` directly depends on the AgentMail adapter and hard-requires `email.send`. | `EXISTS_CANONICALLY` | `BROKER_PROVIDER_COUPLING_REFUSED` |
| Credential secrecy | `CredentialBroker` and `CredentialCapability` keep secret material callback-local and persist only opaque references/digests. | `EXISTS_CANONICALLY` | `PRESERVE_OPAQUE_CAPABILITY` |
| Provider response capture | Callback-bound envelope preserves exact request and response evidence without caller-supplied bytes. | `EXISTS_CANONICALLY` | `PRESERVE_RAW_PROVIDER_EVIDENCE` |
| Provider result decoding | `DeterministicRawProviderResultService` parses AgentMail `message_id` / `thread_id` and emits `agentmail.http-response/v1`. | `EXISTS_CANONICALLY` | `EXTRACT_BOUND_DECODER` |
| Deterministic Lazaretto receipt admission | Admission requires `agentmail.message/v1`, reconstructs the same AgentMail fields and creates an AgentMail-named artifact. | `EXISTS_CANONICALLY` | `FUSED_ADMISSION_DECODER` |
| Normalized `email.send` result | No provider-neutral receipt identifies accepted send, provider evidence reference and provider-assigned message/thread identities as typed optional attributes. | `ABSENT` | `NORMALIZED_RESULT_REQUIRED` |
| Reconstruction | Read-only reconstruction preserves the complete local chain, but its terminal binding is AgentMail-specific. | `EXISTS_FRAGMENTED` | `RECONSTRUCTION_MIGRATION_REQUIRED` |
| Live command authority | `AgentMailEmailSendCommand` creates random commission, request and authorization IDs, asks the broker to issue its credential capability, chooses the provider and constructs the transport. | `EXISTS_CANONICALLY` | `SELF_ASSEMBLED_AUTHORITY_PROHIBITED` |
| Provider replaceability proof | No sterile second adapter proves that the generic corridor can change provider without changing tool authority or Lazaretto policy. | `ABSENT` | `SECOND_ADAPTER_PROOF_REQUIRED` |
| AgentMail inbound webhook lane | Controller, verifier and inbound Lazaretto process a separately authenticated inbound boundary. | `DEFERRED_BOUNDARY` | `INBOUND_NOT_MIGRATED_BY_OUTBOUND_CAMPAIGN` |
| Sortie tools and cognition | Sortie has its own tool registry, manifestation and retirement boundaries. | `DEFERRED_BOUNDARY` | `SORTIE_SEPARATE` |
| Hostile-writer and multi-host guarantees | Existing trusted-writer digests and one-root locks remain the terminal limit. | `DEFERRED_BOUNDARY` | `DO_NOT_LAUNDER_EXISTING_LIMITS` |

## Identity and authority defects

| Defect | Consequence | Required stop condition |
| --- | --- | --- |
| Tool and provider are fused in Curia validation | Authorizing `email.send` silently authorizes one implementation rather than consuming a separately governed binding. | No generalized authority until tool and provider identities are independently versioned. |
| Provider and credential broker are fused | The broker knows the adapter rather than verifying a capability against a provider binding supplied by an authorized consumer. | No new provider may be added by branching inside Clavium. |
| Provider decoder and Lazaretto are fused | Admission policy itself understands AgentMail response fields and artifact names. | Generic Lazaretto may admit only a normalized result produced by the exact bound decoder. |
| Command self-assembles authority-shaped state | Random IDs and broker issuance imitate authorization without consuming the native Curia/Imperator corridor. | Live command remains unmigrated and must not be used as the canonical consumer. |
| Provider assurance is prose-referenced | A provider-specific safety fact can be carried without an immutable provider binding and exact assurance version. | Provider execution assurance remains paused. |

## Required contract boundaries

Batch 1 must define contracts with no implementation:

1. `GovernedToolOperationContract` — tool ID, operation, version, payload contract, effect class and
   normalized return contract. It neither selects a provider nor grants execution authority.
2. `ProviderImplementationBindingContract` — exact provider, adapter, destination policy, credential
   family, assurance-profile reference, request encoder and response decoder bound to one tool
   operation and one authorization target.
3. `ProviderRequestEncoderContract` — deterministic transformation from authorized tool payload to
   provider request bytes/headers, excluding secret material and external I/O.
4. `ProviderEvidenceDecoderContract` — deterministic interpretation of exact sealed provider bytes
   into a normalized tool result, with no authority to alter raw evidence or select another decoder.
5. `NormalizedToolResultContract` — provider-neutral effect outcome, provider evidence reference,
   normalized attributes and refusal semantics.

Exact producer and consumer postures must be named. Contract existence alone authorizes nothing.

## Smallest safe campaign sequence

The bounded sequence is recorded in
`docs/next-campaign-governed-tool-provider-separation.md`: contracts; canonical tool definition;
provider binding; AgentMail extraction; credential enforcement; generic evidence/admission;
reconstruction; live-command correction; adversarial proof and terminal audit.

No step is authorized by this inventory except separately named Batch 1 contract definition.

## Preserved perimeter

No runtime file is changed. Iron Gate, Lazaretto, AgentMail, credential resolution, external I/O,
live command, inbound webhook, sortie, credential-platform, revocation, propagation, telemetry,
reassessment, containment and incident behavior remain closed. Provider Execution Assurance remains
paused after its completed Preparation Batch 0; its evidence is preserved rather than discarded.
