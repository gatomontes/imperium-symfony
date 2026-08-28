# Iron Gate callback-bound provider response envelope

## Status

`CONTRACT_DEFINED_NOT_IMPLEMENTED`

`DeterministicProviderResponseEnvelopeContract` defines the first authoritative evidence object that
may represent bytes observed from the admitted provider callback. It grants no authority, captures
nothing and has no runtime producer in Batch 1.

## Sole producer

The only permitted producer posture is `la-cortine.journal-bound-provider-invocation`. The producer
must operate inside the same admitted invocation boundary that calls the provider adapter. A command,
transport caller, raw-result sealer, Lazaretto consumer or reconstruction service may not supply,
replace or reinterpret HTTP status, headers or response bytes.

The envelope must bind exact immutable references to:

- provider invocation admission;
- effect-start journal;
- execution claim;
- source authorization;
- operation, destination and payload digest;
- provider idempotency key and canonical request fingerprint; and
- callback-start, response-observation and receipt times.

The observation contains HTTP status, canonical headers digest, exact content digest and an opaque
sealed-content reference. Credential material and authentication headers are forbidden.

## Permitted consumers

Only these consumer postures are declared:

1. `la-cortine.deterministic-raw-provider-result-sealer` may consume one intact envelope and derive
   truthful accepted/rejected raw evidence without accepting caller-nominated status or bytes.
2. `la-cortine.deterministic-receipt-reconstructor` may resolve the envelope read only as one link in
   the complete chain.

Neither posture exists operationally merely because it is named by the contract.

## Unknown outcome

Absence of an envelope after provider-invocation admission means
`UNKNOWN_REPLAY_PROHIBITED`. It never means rejected, failed or not invoked. An envelope may exist
only after a response was actually observed. A thrown callback, timeout, process death or credential
failure may not manufacture one.

The producer must be single-winner per invocation admission. Automatic provider replay is always
false. No envelope can authorize another callback.

## Batch 1 boundary

Batch 1 adds the schema and documentation assertions only. It does not change
`DeterministicJournalBoundCredentialBroker`, `DeterministicRawProviderResultService`, Lazaretto,
reconstruction, AgentMail transport, Iron Gate or any live consumer. No external I/O occurs.
