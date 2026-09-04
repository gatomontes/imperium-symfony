> Historical entrypoint: Preparation Batch 0 completed and its review amendment
> merged at `009bf9bb3a1473ac65ace5b12bdd6711ec40133c`. The current authorized
> entrypoint is
> `docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-batch-1-local-ready.md`.
> This file grants no current execution authority.

# Canonical Native Effect Reconciliation Issuance Authority and Revocation-at-Use Remediation — Preparation Batch 0 local ready

`PREPARATION_BATCH_0_LOCAL_ENTRYPOINT_READY`
`PREPARATION_BATCH_0_ONLY_HARD_STOP`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

## Local synchronization and focused guard

```powershell
git checkout main
git pull --ff-only origin main
git status --short
php vendor/bin/phpunit tests/Imperium/Runtime/CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationCampaignReadyTest.php
```

## New-chat prompt

```text
Continue Imperium from clean synchronized `main` after the merge selecting
Canonical Native Effect Reconciliation Issuance Authority and Revocation-at-Use
Remediation.

Read
`docs/canonical-native-effect-reconciliation-authority-provenance-post-merge-blackquill-review-v1.md`,
`docs/next-campaign-canonical-native-effect-reconciliation-issuance-authority-revocation-remediation.md`,
`docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-campaign-ready.md`,
the complete prior reconciliation-authority provenance Preparation Batch 0 and
Batches 1–5 documents, tests, evidence ledgers and handoffs;
`docs/delegate-mission-flow.md`, `docs/handoffs/README.md`,
`todo/blackquill-todos.md`; and the complete current sources/tests for
`NativeEffectReconciliationAuthorityIssuanceService`,
`NativeEffectReconciliationAuthorityIssuanceContract`,
`NativeEffectReconciliationAuthoritySourceResolver`,
`NativeEffectReconciliationAuthorityResolver`,
`NativeEffectReconciliationAuthorityCapability`,
`NativeEffectReconciliationAuthorityClaimDerivationService`,
`NativeEffectForwardRecoveryClaimAdmissionService`,
`NativeEffectForwardRecoveryService`,
`NativeEffectReconciliationAuthorityReconstructionService`,
`CanonicalNativeEffectCorridor`, `NativeAuthority`, `NativePrincipal`,
`NativeRootActs`, `AuthorityConsumptionStore`, `AtomicTransition`,
existing Operator Root/Imperator issuance-authority services, container
definitions, workers and every call site.

Begin Canonical Native Effect Reconciliation Issuance Authority and
Revocation-at-Use Remediation Preparation Batch 0 only. This is a hard stop.

Inventory every route that can authorize or invoke reconciliation-authority
issuance. Demonstrate the current unguarded
`issue(admissionId, at, expiresAt)` counterexample. Prove exactly what the
source native transition decision authorizes, where its single-use consumption
is recorded, and why `continuing_authority: false` cannot authorize a derived
act. Distinguish source provenance from derivation authorization, issuer service
identity from issuer competence, construction access from authority, and
deterministic output from authorized issuance.

Demonstrate the exact resolve -> revoke -> consume race for Operator Root,
native principal, source generation and any relevant lifecycle disposition.
Identify which checks occur at resolution and which are absent at consumption.
Trace reusable canonical decision, scoped caller/issuance authority, typed
capability, present-tense validation, atomic consumption, deterministic retry,
lock order, process-loss recovery and read-only reconstruction patterns.

Inventory direct instantiation, corridor/container exposure, every issuer,
resolver and claim consumer, all authority and lifecycle stores, competing
issuers/claimants, expiry, substitution, replay, interruption cuts, fresh
processes, Windows/Linux differences, Git commit/merge/CI provenance and every
stale closure consumer.

Classify every surface as EXISTS_CANONICALLY, EXISTS_FRAGMENTED, ABSENT or
DEFERRED_BOUNDARY. Define the smallest acyclic design and staged proof sequence
that requires a separately sourced exact issuance authority, consumes it with
authority publication, revalidates currentness at the governed use cut, and
preserves the accepted typed recovery, process custody and no-provider
boundaries.

Do not modify production runtime behavior, configuration or service wiring.
Do not create Batch 1 contracts/tests, an issuance decision, authority,
capability, reconciliation authority, claim, receipt or completion handoff. Do
not derive or consume authority, access a credential, invoke AgentMail/provider,
perform network/external I/O, execute a mission/live trial/email, open Iron
Gate/Lazaretto, claim terminal closure, fabricate evidence or restore Batch 7.

The campaign-selection merge, prior implementation, green tests, “continue,”
“proceed,” “forward,” “clear,” or a Latin motto do not authorize Batch 1.
Stop even if the correction appears obvious.

Produce only a versioned preparation inventory, derivation-authority/currentness
call graph, issuance-custody-consumption matrix, revocation-race matrix,
adversarial proof matrix, reading/evidence ledger, Preparation Batch 0
documentary guard and completion handoff. Report the exact remaining stages and
focused local PHPUnit command. Stop at
`PREPARATION_BATCH_0_COMPLETE_RECONCILIATION_ISSUANCE_AUTHORITY_AND_REVOCATION_GAPS_CLASSIFIED`.

No later batch and no provider effect is authorized by this entrypoint.
```
