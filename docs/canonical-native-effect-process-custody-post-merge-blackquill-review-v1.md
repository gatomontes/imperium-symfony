# Canonical Native Effect Process Custody — post-merge Blackquill review v1

`PROCESS_CUSTODY_CORRECTION_ACCEPTED`
`FORMAL_CLOSURE_REFUSED_RECONCILIATION_AUTHORITY_PROVENANCE_ABSENT`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

## Reviewed baseline

The review examined merged `main` at
`b188a0b849f27ebec4d3e14f98c471eead15b484`, including the six-stage
process-custody/formal-closure remediation, its final GitHub Actions run and the
runtime recovery path.

## Retained corrections

Process custody is materially corrected. PID plus an issuer-private nonce and
exact object identity reject fresh-process, clone, serialization and supported
fork inheritance. First callback execution, read-only reconstruction and forward
completion are separate APIs. The final GitHub run `33827147088` passed
`2371 tests / 50106 assertions` for the exact merged baseline.

## Material refusal

`NativeEffectForwardRecoveryClaimAdmissionService::admit()` accepts a
caller-supplied authority array. The asserted issuer, holder and act are constant
strings, while `NativeState::seal()` is a public deterministic digest. That
proves byte consistency, not issuance, provenance, custody or authorization.

The Batch 3 and Batch 4 tests construct the alleged reconciliation authority
directly, apply `NativeState::seal()`, and successfully admit it. No canonical
Imperator/Operator Root issuer, trusted issuance record, typed custody capability,
revocation source or atomic authority-consumption evidence participates. The
corridor exposes this admission service, so an absent upstream producer is not
a harmless deferred component: the receiving boundary presently converts
self-authored prose into durable authority.

## Verdict

The process-custody substrate remains accepted. The formal-closure marker and
`ZERO_CAMPAIGN_STAGES_REMAIN` are not supported for governed recovery.
A corrective campaign must establish Root-provenanced reconciliation issuance,
trusted resolution, typed custody/consumption, counterfeit refusal, canonical
corridor integration and separately sequenced proof. No live effect follows.
