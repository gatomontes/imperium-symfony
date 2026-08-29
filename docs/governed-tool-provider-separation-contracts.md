# Governed Tool and Provider Separation contracts

## Status

`BATCH_1_CONTRACTS_DEFINED_NO_PRODUCER_OR_CONSUMER_IMPLEMENTED`

Batch 1 defines five provider-neutral, separately versioned contracts. They establish identity,
shape, producer posture, consumer posture, substitution rules and explicit non-authorities. They do
not create records, select a provider, resolve credentials, invoke a tool, perform external I/O,
decode evidence or admit a result.

No contract names AgentMail. Provider-specific facts may enter the corridor only through a later
exact `ProviderImplementationBindingContract` record and its bound encoder, decoder, credential
family and assurance profile.

## Contract map

| Contract | Owner | Sole producer posture | Consumers | Purpose |
| --- | --- | --- | --- | --- |
| `GovernedToolOperationContract` | Armory | `armory.armorer-governed-tool-definition` | Curia authority request, provider binding, Iron Gate execution, Lazaretto normalized admission | Defines one provider-neutral tool operation, payload contract, effect class and normalized return contract. |
| `ProviderImplementationBindingContract` | La Cortine | `la-cortine.exact-provider-binding-transition` | Curia, Clavium, encoder, decoder, reconstruction | Binds one provider implementation, adapter version, assurance profile, credential family, destination policy, encoder and decoder to one exact authorization target. |
| `ProviderRequestEncoderContract` | La Cortine | `la-cortine.bound-provider-adapter` | Journal-bound invocation and reconstruction | Deterministically constructs provider request identity from already-authorized inputs while authentication remains callback-local. |
| `ProviderEvidenceDecoderContract` | La Cortine | `la-cortine.bound-provider-evidence-decoder` | Lazaretto normalized admission and reconstruction | Interprets exact sealed provider bytes using only the decoder named by the provider binding. |
| `NormalizedToolResultContract` | La Cortine | `la-cortine.exact-bound-provider-evidence-decoder` | Lazaretto normalized admission and reconstruction | Carries provider-neutral effect outcome plus exact raw-evidence, provider-binding and decoder lineage. |

## Separation rules

### Tool definition

The Armory contract identifies what the tool does. It cannot grant use of the tool, choose a
provider, issue a credential, encode a request, execute an effect, decode evidence or admit a
result. `provider_neutral=true`, `provider_binding_required=true` and
`provider_substitution_permitted=false` are required record facts when a canonical tool definition
is later produced.

### Provider binding

The provider binding is a separately governed selection record, not a field smuggled into tool
identity. It binds exact references for tool operation, source authority, provider implementation,
assurance profile, credential family, encoder, decoder and destination policy. Every substitution
dimension is forbidden. The contract cannot select or produce itself.

### Request encoding

The encoder receives an exact tool operation, provider binding, source authorization, execution
claim, effect-start journal, destination, payload digest, exact payload bytes and opaque
authentication. It may construct request identity only. It cannot resolve or consume credentials,
start I/O or observe a response. Secret persistence is categorically forbidden.

### Evidence decoding

The decoder receives exact sealed response evidence and all authoritative references. Caller-
supplied provider truth, raw-evidence mutation, provider substitution, decoder substitution,
credential resolution, provider reinvocation and external I/O are forbidden. Decoding does not
admit the result.

### Normalized result

The result preserves source authorization, execution claim, provider binding, raw provider evidence
and decoder identity. Provider-specific attributes must remain typed data beneath the generic
result; they cannot redefine the generic effect outcome. The result cannot decode or admit itself,
and it never authorizes automatic replay or provider reinvocation.

## Producer and consumer posture

All producer and consumer identifiers are contract postures only. No service is declared compliant
by naming it here. A future batch must separately implement, authorize and prove each producer and
consumer before it may create or consume these records.

Existing `AgentMailIdempotencyHeaderAdapter`, `DeterministicJournalBoundCredentialBroker`,
`DeterministicRawProviderResultService`, `DeterministicLazarettoReceiptAdmissionService` and
`AgentMailEmailSendCommand` are not migrated and are not consumers of these contracts.

## Closed boundary

No producer or consumer is implemented. No existing runtime behavior changes. AgentMail is not
selected or called. No credential is issued or resolved. Iron Gate, Lazaretto, inbound webhook,
sortie, credential-platform, revocation, propagation, telemetry, reassessment, containment and
incident behavior remain closed.
