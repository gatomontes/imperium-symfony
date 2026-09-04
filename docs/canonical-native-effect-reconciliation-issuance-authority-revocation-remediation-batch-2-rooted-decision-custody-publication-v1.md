# Canonical Native Effect Reconciliation Issuance Authority and Revocation-at-Use Remediation — Batch 2

`BATCH_2_COMPLETE_ROOTED_DECISION_CUSTODY_AND_ATOMIC_PUBLICATION`
`LEGACY_PUBLIC_ISSUER_SIGNATURE_RETAINED_FOR_BATCH_3`
`NO_PROVIDER_NO_CREDENTIAL_NO_EXTERNAL_IO`

## Implemented chain

`NativeEffectReconciliationIssuanceAuthorizationService` resolves the canonical
effect admission through the committed native transition, active native
principal, source Imperator principal and verified Operator Root act. It
publishes one deterministic exact issuance decision and a distinct single-use
issuance authority. The decision names the exact issuer competence; no unrelated
Imperator scope flag, source provenance label, consumed native-transition
authority or service possession is treated as that competence.

`NativeEffectReconciliationIssuanceAuthorityResolver` re-resolves the durable
decision, issuance authority and complete current source lineage before
delivering an exact-object, resolver-private, PID/incarnation-bound,
non-cloneable and non-serializable capability. Durable records and copied fields
are not custody.

`NativeEffectReconciliationIssuancePublicationService` takes the one semantic
target exclusion before consuming custody. Its durable consumption identity is
derived from the deterministic reconciliation-authority target, while its
source binds the exact issuance-authority ID and digest. This allows only one
decision/authority winner for the semantic target. It then publishes the exact
precomputed reconciliation authority and existing immutable issuance evidence.

## Retry and interruption law

- Before consumption, no reconciliation output exists.
- After durable consumption and before publication, only a freshly resolved
  capability for the same decision, issuance authority, target, lineage and
  validity window can finish the deterministic output.
- Exact retry returns `EXACT_RETRY_CONVERGED` and the established records.
- A changed window produces a different decision/issuance authority but meets
  the same semantic-target consumption root and refuses `REFUSED_CONFLICTED`.
- Publication never grants continuing authority.

The legacy `issue(string $admissionId, int $at, int $expiresAt)` signature is
intentionally unchanged in this batch. Its enforcement and repository-wide
caller migration belong exclusively to Batch 3.

## Boundary

The implementation performs local immutable-file reads, writes and cooperative
single-host `flock` exclusion only. It contains no credential, provider,
transport, environment-secret, HTTP/network, command, mission, Iron Gate or
Lazaretto edge. Multi-host exclusion and hostile direct writers remain outside
the claim.

