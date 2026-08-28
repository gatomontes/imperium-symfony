# Iron Gate deterministic execution claim

## Status

`BATCH_6_DURABLE_AUTHORIZATION_CONSUMPTION_CLAIMED_PRE_IO`

Batch 6 implements the first deterministic La Cortine consumer. It resolves one intact Batch 5
issuance aggregate, validates its embedded outbound authorization, binds one opaque credential
capability by metadata, and creates one immutable execution claim.

The claim is the single-use authorization-consumption record. It binds the exact authorization ID
and digest, consumption time, holder, request and commission, operation, destination, payload,
return contract, execution winner, replay fingerprint, provider idempotency identity,
credential-reference digest and transitive expiry.

## Winner and replay rule

The lock scope is the source authorization identity. Lock order is `authorization` then
`execution-claim`. One authorization may produce one winner. Exact replay returns the existing
claim even when the observer retries later; a changed credential capability or request fingerprint
is a conflict, not a second execution.

The source authorization is not mutated. Its consumption and the durable claim are one immutable
aggregate, avoiding a partial authority-consumption/claim pair. Source issuance or embedded
authorization tamper fails before persistence.

Batch 6 also repairs the pre-consumer contract gap by binding `commission_id` transitively in the
outbound authorization scope. A claim may not infer or manufacture that identity.

## Pre-I/O stop

Every Batch 6 claim seals `checkpoint=CLAIMED_PRE_IO`, `external_io_started=false`,
`outcome=NOT_ATTEMPTED` and `automatic_replay_permitted=false`.

Credential metadata includes only the opaque capability ID, credential-reference digest, scope,
expiry and maximum uses. The credential is neither resolved nor consumed, and secret material is
never accepted or persisted.

No `OutboundRequest`, `IronGate`, `DeterministicBoundaryExecutor`, `CredentialBroker`, transport,
provider journal, raw payload or Lazaretto behavior changes. Sortie remains separate and closed.

## Smallest safe continuation

Only a separately authorized Batch 7 may bind the durable claim to one pre-I/O credential-use and
effect-start journal transition. It must preserve the provider idempotency key and the rule that an
unresolved started effect is `UNKNOWN_REPLAY_PROHIBITED`. External provider invocation, raw receipt
creation and Lazaretto admission remain separately bounded.

