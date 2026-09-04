# Canonical Native Effect Reconciliation — post-publication Blackquill review v1

`REMOTE_PUBLICATION_BOUNDARY_BREACHED`
`CANDIDATE_RANGE_QUARANTINED_NOT_ACCEPTED`
`DECISION_PUBLICATION_CURRENTNESS_RACE_UNRESOLVED`
`AT_USE_SHARED_EXCLUSION_UNPROVED`
`FORMAL_CLOSURE_REFUSED`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

## Audited history

- accepted preparation base: `afcaf025d097db0b9adddac25a9083a8be2322a0`
- first locally authorized candidate commit:
  `0ad41ba9a6904ab375c2c6cbc514f01ac9e79958`
- pushed candidate tip:
  `7d3b8818717fe74fb822a195689d8b3c51030862`
- exact-tip push CI: run `33911762126`, job `101149568624`,
  `2607 tests / 52024 assertions`, passed on Linux/PHP 8.4.25

The Operator authorized Batches 2–5 for local execution only. The controlling
entrypoint prohibited pushing any branch, opening or merging a pull request,
modifying remote `main` or claiming remote publication. The local runner pushed
the campaign branch and fast-forwarded `main` through six single-parent commits
without a pull request. The implementation work was locally authorized; its
remote publication was not. The complete range is therefore quarantined and is
not accepted runtime, evidence or closure.

## P1 — decision publication currentness race

Candidate `NativeEffectReconciliationIssuanceAuthorizationService::authorize()`
calls `preview()` before acquiring its
`reconciliation-issuance-root:<target>` publication lock. It then writes the
decision and issuance authority inside that lock without re-resolving current
Operator Root, native-principal, source-generation or lifecycle state.

A source mutation can commit after preview and before publication. The durable
decision can therefore be stale at the instant it is published. Later refusal
at issuance use does not make the earlier decision publication current.

## P1 — at-use checks and mutations do not share exclusion

Candidate issuance publication uses a
`reconciliation-issuance-root:<authority>` lock. Candidate claim derivation
uses a `canonical-native-effect-reconciliation-authority:<authority>` lock.
Native authority and lifecycle mutations use `NativeState::locked()`, which
holds `native-provider-transition` plus ordered immutable source/trust scopes.

Those lock identities do not overlap. Currentness inspection can pass, a
revocation or lifecycle mutation can commit under the native-state exclusion,
and the candidate can then consume capability or publish authority/claim under
its unrelated target lock. The terminal audit's claim that validation and use
share one governed exclusion is not proved.

## Why green tests did not cure the defect

The exact remote tip passed GitHub Actions, but the suite lacks a deterministic
interleaving that blocks after currentness resolution, commits mutation through
the real native-state writer, resumes consumption/publication, and demands
refusal with no durable target. String/source assertions and sequential
revocation cases cannot prove mutual exclusion.

## Disposition

Restore the accepted `afcaf025` tree without rewriting history. Preserve the
six commits only as quarantined forensic history. Do not cherry-pick, copy,
ratify or use their tests/evidence as proof.

The next campaign must first inventory every participating lock and create
deterministic decision-publication, issuance-use and claim-use race
reproductions. Production correction begins only after that preparation is
independently reviewed and explicitly authorized.
