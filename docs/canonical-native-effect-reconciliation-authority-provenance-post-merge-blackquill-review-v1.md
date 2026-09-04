# Canonical Native Effect Reconciliation Authority Provenance — post-merge Blackquill review v1

`SELF_SEALED_AUTHORITY_INGRESS_CORRECTED`
`ROOT_PROVENANCE_JOIN_ACCEPTED`
`FORMAL_CLOSURE_REFUSED_RECONCILIATION_DERIVATION_AUTHORITY_ABSENT`
`REVOCATION_AT_CONSUMPTION_UNPROVED`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

## Reviewed baseline

The review examined merged `main` at
`23034731492cb8661a5580470dbc0c34139e17fc`, including the completed
reconciliation-authority provenance campaign and exact final GitHub Actions run
`33875195432`, which passed `2484 tests / 51091 assertions`.

## Retained corrections

Caller-created self-sealed arrays no longer cross claim admission. Durable
authority and issuance evidence are resolved against the native transition,
active native Imperator principal and signed Operator Root act. Admission
requires exact typed process custody. Claim/receipt consumption, contention,
interruption recovery and read-only receipt-to-Root reconstruction are material
corrections and remain accepted substrate.

## Material refusal: authority to derive is absent

`NativeEffectReconciliationAuthorityIssuanceService::issue()` accepts only an
admission identifier and timestamps. It requires no exact issuance decision,
caller/issuance authority or typed consumable capability.

The source native authority is evidence for one
`AUTHORIZED_EXACT_TRANSITION`, is single-use and explicitly carries
`continuing_authority: false`. Resolving its provenance cannot silently grant
a new act: issuing reconciliation authority after the transition. The public
corridor exposes the issuer service, so construction access is presently being
mistaken for competence. Provenance answers where the source came from; it does
not answer who authorized derivation.

## Material refusal: revocation is stale after resolution

The resolver checks Root/principal currentness while resolving custody, but
`consume()` does not re-resolve that source chain. A capability resolved before
revocation can remain acceptable after revocation until its own expiry. Existing
tests revoke before fresh resolution; they do not prove
resolve -> revoke -> consume refusal.

## Verdict

The typed provenance substrate remains accepted. Formal closure and zero stages
are refused until an exact separately sourced issuance authority is delivered
and atomically consumed, currentness is revalidated at use, both paths are
adversarially proved, and a separate terminal audit accepts the merged evidence.
No live effect follows and Batch 7 remains suspended.
