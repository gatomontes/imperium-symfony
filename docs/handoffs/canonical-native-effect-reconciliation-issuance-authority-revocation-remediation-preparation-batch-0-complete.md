# Canonical Native Effect Reconciliation Issuance Authority and Revocation-at-Use Remediation — Preparation Batch 0 complete

`PREPARATION_BATCH_0_COMPLETE_RECONCILIATION_ISSUANCE_AUTHORITY_AND_REVOCATION_GAPS_CLASSIFIED`
`DOCUMENTARY_ONLY_NO_RUNTIME_CHANGE`
`FORMAL_CLOSURE_REFUSED_RECONCILIATION_DERIVATION_AUTHORITY_ABSENT`
`REVOCATION_AT_CONSUMPTION_UNPROVED`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`
`POST_RECEIPT_RECONSTRUCTION_REVOCATION_SOURCE_SPLIT_REQUIRED`

Preparation Batch 0 is complete against clean synchronized cached `main` at
`3dceba3057497c6c80f019bd78835335cf69c774`. `HEAD`, `main`,
`origin/main` and `origin/HEAD` agreed at entry. No fetch, network request,
external CI lookup, provider operation, credential access or authority action
was performed.

The issuer counterexample is exact. Public construction access plus an admission
ID and caller-chosen valid times reaches
`issue(string $admissionId, int $at, int $expiresAt)`. The service correctly
resolves Root/native/effect provenance and publishes deterministic authority and
issuance evidence, but it accepts and consumes no decision, caller authority,
issuance authority or typed issuance capability. Issuer-service identity is not
issuer competence, and deterministic convergence is not authorization.

The source native decision authorizes `AUTHORIZED_EXACT_TRANSITION` for its
exact target/consumer/root. Its single-use consumption is embedded at
`transition.records.authority_consumption` in the committed native transition.
Decision, custody, authority and consumption all carry
`continuing_authority: false`; none can authorize a later reconciliation-
issuance act.

The revocation race is also exact. `resolve()` verifies Root currentness, native
principal activation/revocation/expiry, source-principal lifecycle and source
generation before issuing process-bound custody. `consume()` rechecks only the
resolver-owned exact object, capability expiry, PID/incarnation binding and
authority/issuance digests. A Root revocation, native-principal revocation,
source-generation advance or source lifecycle disposition inserted between
those calls is not observed before deterministic claim publication. Later
forward-completion inspection may refuse; it cannot retroactively authorize the
already-derived claim.

The smallest later correction adds a separately sourced exact issuance decision
and single-use typed issuance authority, atomically consumes it with authority
and issuance-evidence publication, and revalidates Root/native/source
currentness in the governed issuer and claim-use cuts. It preserves existing
typed recovery custody, deterministic local retry, no-provider claim/receipt
consumption, process-loss recovery and read-only reconstruction conditional on current Operator Root eligibility.

Artifacts:

- `docs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-preparation-inventory-v1.md`;
- `docs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-derivation-authority-currentness-call-graph-v1.md`;
- `docs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-issuance-custody-consumption-matrix-v1.md`;
- `docs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-revocation-race-matrix-v1.md`;
- `docs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-adversarial-proof-matrix-v1.md`; and
- `docs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-reading-evidence-ledger-v1.json`.

## Exact remaining stages

1. Batch 1 — issuance-authority and at-use-currentness contracts only.
2. Batch 2 — Rooted issuance decision, typed custody and atomic consumption with
   deterministic reconciliation-authority/issuance publication.
3. Batch 3 — issuer enforcement and present-tense Root/native/source
   revalidation at the governed issuer and claim-use cuts.
4. Batch 4 — missing/counterfeit/replayed/substituted issuance authority,
   consumed-source refusal, resolve-revoke-consume, expiry, competing issuers and
   claimants, interruption, fresh-process, container/worker, Windows/Linux and
   source-specific post-receipt reconstruction proof without provider or
   credential access.
5. Batch 5 — separately authorized terminal audit from clean synchronized
   merged Batch 4 `main`, with independent reconstruction, focused/full local
   proof and retained exact-SHA GitHub CI evidence.

The original corridor Batch 7 is not one of these five stages and remains
suspended. Ordinary Root-anchor and native-principal expiry require preservation proof,
not duplicate at-use remediation. Post-receipt reconstruction must distinguish
current untimestamped Operator Root revocation, which presently refuses
historical reconstruction, from timestamped native/source lifecycle changes.
Batch 1 is not authorized by this handoff.

## Focused local PHPUnit command

```powershell
php vendor/bin/phpunit tests/Imperium/Runtime/CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationPreparationBatch0Test.php
```

Original pre-amendment focused documentary guard result: PHPUnit 13.3.0 / PHP 8.4.14,
`OK (9 tests, 192 assertions)`. The review amendment adds source-specific guards;
its exact-SHA GitHub CI result is retained on the amendment PR. Two earlier
source-read-only runs found only
documentary expectation/case and test-string interpolation defects
(`9 / 177 / 3 failures / 1 warning`, then `9 / 186 / 1 failure`); both are
retained in the reading/evidence ledger. No authority-producing prior campaign
test was executed.

No production runtime behavior, configuration or service wiring changed. No
Batch 1 contract/test, issuance decision, issuance authority, capability,
reconciliation authority, claim, receipt or completion handoff was created or
consumed. No credential, AgentMail/provider, network/external I/O, mission, live
trial or email was invoked. Iron Gate and Lazaretto remained closed. No terminal
closure, fabricated evidence or Batch 7 restoration is claimed.

Stop here at
`PREPARATION_BATCH_0_COMPLETE_RECONCILIATION_ISSUANCE_AUTHORITY_AND_REVOCATION_GAPS_CLASSIFIED`.
