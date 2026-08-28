# Iron Gate deterministic execution claim and receipt binding contract

## Status

`BATCH_1_CONTRACTS_DEFINED_NO_CONSUMER_MIGRATED`

Batch 1 defines two separately versioned declarative contracts:

- `imperium.la-cortine.deterministic-execution-claim/v1`; and
- `imperium.la-cortine.deterministic-receipt-binding/v1`.

The contracts grant no authority, consume no capability, acquire no lock, resolve no credential,
perform no external I/O, persist no runtime record and admit no payload. They define the evidence a
later separately authorized deterministic consumer migration must produce.

## Deterministic execution claim

One claim must bind the native sealed source authorization and competent decision owner; complete
request scope; exact actor, Office, Seat and runtime principal; complete replay fingerprint; one
single-use execution identity and winner scope; exact credential-capability identity without secret
material; one provider-safety strategy; effect checkpoint; claim time; and transitive expiry.

The complete request scope includes request, commission and authorization identities and digests,
`DETERMINISTIC` mode, exact operation, one destination, payload digest and expected return contract.
Changing any input creates a conflict rather than a second permissible execution.

The contract does not manufacture the missing native authorization currently used by deterministic
CLI commands. A consumer is ineligible until a competent persisted source authorization exists.

## Provider-safety prerequisite

Before a consumer can migrate, one exact operation must prove one of two strategies:

1. `PROVIDER_IDEMPOTENCY_KEY`: the provider contract and stable key make duplicate submission
   semantics explicit; or
2. `NON_REPLAYABLE_UNKNOWN_OUTCOME`: once effect start is recorded, any unresolved result becomes
   `UNKNOWN_REPLAY_PROHIBITED` and automatic replay is permanently refused.

The contract does not select a strategy or prove that a provider honors one. An empty key, an
undocumented provider assumption, or a process-local use counter is insufficient. Network I/O
remains outside internal rollback semantics.

Effect checkpoints are `CLAIMED_PRE_IO`, `EFFECT_STARTED`, `PROVIDER_RESOLVED`,
`RAW_RECEIPT_SEALED` and `RECEIPT_BOUND`. Outcomes are `NOT_ATTEMPTED`, `ACCEPTED`, `REJECTED` and
`UNKNOWN_REPLAY_PROHIBITED`. Unknown never means rejected or accepted.

## Deterministic receipt binding

One binding must preserve the exact execution claim, source authorization, request, provider
outcome, sealed raw response bytes, Lazaretto admission and recovery state. The provider outcome
must distinguish `ACCEPTED`, `REJECTED` and `UNKNOWN_REPLAY_PROHIBITED` without inventing knowledge
that the provider did not return.

The raw receipt reference must bind its schema, content digest, sealed content location and native
observed/received times. The request reference repeats the operation, destination, payload digest,
credential capability ID and expected return contract so reconstruction does not depend on logs.
Credential secret material is categorically excluded.

Lazaretto admission must name the admitted artifact and digest, validate—not merely copy—the exact
expected return contract, preserve the transformation and admission time, and retain no authority.
This requirement does not expand Lazaretto trust, sanitization or inbound policy.

## Recovery and reconstruction

The receipt contract observes `CLAIM_ONLY`, `OUTCOME_UNKNOWN`, `RAW_RECEIPT_SEALED`,
`LAZARETTO_ADMITTED` and `COMPLETE`. Recovery from a sealed raw response completes forward without
credential reconsumption or provider reinvocation. An unknown outcome remains stopped pending a
separately governed resolution; it cannot create a receipt claiming acceptance.

Read-only reconstruction must prove:

1. exact source authorization and decision owner;
2. exact execution winner and replay fingerprint;
3. exact operation, destination, payload and credential-capability identity;
4. truthful provider outcome and provider-safety strategy;
5. exact sealed raw receipt digest;
6. exact Lazaretto admission; and
7. zero credential-secret inclusion and zero provider reinvocation during recovery.

## Preserved boundaries

`DeterministicExecutionClaimContract` and `DeterministicReceiptBindingContract` are declarative.
Batch 1 migrates no issuer or consumer and changes no `OutboundRequest`, `IronGate`,
`BoundaryDispatch`, executor, broker, transport, raw payload, Lazaretto, sortie, Oracle or provider
journal behavior. Revocation, propagation, telemetry, reassessment, containment, incidents,
credential-platform redesign and external I/O remain closed.
