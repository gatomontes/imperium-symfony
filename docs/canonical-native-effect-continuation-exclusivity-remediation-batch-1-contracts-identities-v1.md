# Canonical Native Effect Continuation and Exclusivity Remediation — Batch 1 contracts and identities v1

`BATCH_1_CORRECTED_CONTRACTS_AND_IDENTITIES_COMPLETE_NO_RUNTIME_WIRING`
`BATCH_2_NOT_AUTHORIZED`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

Baseline: Preparation Batch 0 artifacts on synchronized `main` at
`77d26f4c7f5655dcce67b5c3765714b5c0ede85e`.

## Corrected identity boundary

`NativeEffectSemanticIdentity` is a pure, unwired derivation. It first requires
the exact v1 authority shape and recomputes its canonical seal. It then derives
the semantic effect tuple exclusively from immutable native, operation,
destination, payload, request, provider, credential-family, return-contract and
idempotency commitments.

The tuple excludes authority id/digest, issuer, holder, effective/expiry time,
revocation/cancellation state and consumption flags. Therefore distinct valid
authorities for the same effect converge on one tuple id. A separate authority-
consumption id digests the tuple id with the candidate authority id/digest, so
the winning authority remains exact. Future admission ids use the complete
64-hex tuple id rather than the current 20-hex authority-specific replay prefix.

The current `NativeEffectAdmissionValidator::replayIdentity()` and all admission
runtime behavior remain unchanged. Batch 2 must explicitly adopt the corrected
identity under the approved lock order.

## New declarative contracts

| Contract | Defined invariant | Runtime status |
| --- | --- | --- |
| `NativeEffectContinuationCapabilityContract` | Exact admission/tuple/authority-consumption binding; issuer-registry object identity; newly published winner only; one use; no persistence, transfer, replay mint or reconstruction | No object, registry, issuer or consumer implemented |
| `NativeEffectReceiptInputContract` | Immutable admitted native/authority/request/provider/credential/return/idempotency provenance; later caller authority/provider/destination/return substitution prohibited | No record written and no callback/receipt consumer changed |
| `NativeEffectTupleDispositionContract` | Winner consumes exact authority; loser is `TUPLE_ALREADY_WON_AUTHORITY_UNCONSUMED`; neither disposition grants retry by itself | No disposition service or publication implemented |

## Batch boundary

This batch adds no lock acquisition, atomic publication, continuation object,
capability issuance/recognition/consumption, admission record, callback,
credential resolver, provider double, transport, command, container wiring or
runtime state. It does not modify current admission, callback or receipt
behavior. The current Blackquill counterexamples remain executable until
Batches 2–4 close them.

Batch 2 is separately bounded to adopting the semantic tuple, publishing one
tuple winner with exact authority consumption, recording loser disposition and
creating process-local continuation custody only for the newly published
winner. Batch 2 is not authorized by this document.
