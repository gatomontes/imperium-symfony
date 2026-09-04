# Canonical Native Effect Reconciliation Issuance Authority and Revocation-at-Use Remediation — Batch 3

`BATCH_3_COMPLETE_TYPED_ISSUER_AND_AT_USE_CURRENTNESS`
`BATCH_4_NOT_YET_ADVERSARIALLY_PROVED`
`NO_PROVIDER_NO_CREDENTIAL_NO_EXTERNAL_IO`

The public reconciliation-authority issuer now accepts only a process-local
`NativeEffectReconciliationIssuanceAuthorityCapability`. The corridor exposes
the decision boundary and requires the same issuance-authority resolver when it
constructs the issuer; the former admission-string and caller-selected expiry
route no longer exists.

Issuance revalidates Operator Root, native principal and source currentness
inside the native-state exclusion before consuming issuance authority and
publishing the deterministic reconciliation authority. Claim derivation now
repeats reconciliation-authority inspection inside its governed exclusion
immediately before exact authority consumption and claim publication. A Root
revocation, native-principal revocation or source-generation advance between
capability resolution and use is therefore refused without publication.

Repository callers and the fresh-process worker use the rooted decision,
shared issuance resolver and typed public issuer. The frozen runtime inventory
continues to cover the decision and resolver ingress and the authorized
consumption service.

Focused Batch 3 proof passed `28 tests / 128 assertions`. The combined Batch
2–3, provenance, continuation, process-custody and frozen-perimeter regression
gate passed `251 tests / 5533 assertions`.

No provider callback, credential access, callback reinvocation, transport,
network or external I/O edge was added.
