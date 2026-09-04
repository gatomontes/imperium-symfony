# Canonical Native Effect Reconciliation Issuance Authority and Revocation-at-Use Remediation — Batch 1 local ready

`BATCH_1_LOCAL_ENTRYPOINT_READY`
`BATCH_1_CONTRACTS_ONLY_AUTHORIZED`
`BATCH_2_NOT_AUTHORIZED`
`NO_RUNTIME_AUTHORITY_OR_PROVIDER_EFFECT`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

## Local synchronization and current guards

```powershell
git checkout main
git pull --ff-only origin main
git status --short
php vendor/bin/phpunit tests/Imperium/Runtime/CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationCampaignReadyTest.php
php vendor/bin/phpunit tests/Imperium/Runtime/CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationPreparationBatch0Test.php
```

The worktree must be clean before Batch 1 begins. If either guard fails, stop.

## New-chat prompt

```text
Continue Imperium from clean synchronized `main` after the merge preparing
Canonical Native Effect Reconciliation Issuance Authority and Revocation-at-Use
Remediation Batch 1.

Read completely:

- `docs/next-campaign-canonical-native-effect-reconciliation-issuance-authority-revocation-remediation.md`;
- `docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-campaign-ready.md`;
- `docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-preparation-batch-0-complete.md`;
- the complete Preparation Batch 0 inventory, derivation-authority/currentness
  call graph, issuance-custody-consumption matrix, revocation-race matrix,
  adversarial proof matrix and reading/evidence ledger;
- `docs/canonical-native-effect-reconciliation-authority-provenance-post-merge-blackquill-review-v1.md`;
- the complete prior reconciliation-authority provenance campaign contracts,
  implementation documents, tests, evidence ledgers and handoffs;
- `docs/delegate-mission-flow.md`, `docs/handoffs/README.md` and
  `todo/blackquill-todos.md`; and
- the complete current sources/tests for
  `NativeEffectReconciliationAuthorityIssuanceService`,
  `NativeEffectReconciliationAuthorityIssuanceContract`,
  `NativeEffectReconciliationAuthoritySourceResolver`,
  `NativeEffectReconciliationAuthorityResolver`,
  `NativeEffectReconciliationAuthorityCapability`,
  `NativeEffectReconciliationAuthorityClaimDerivationService`,
  `NativeEffectForwardRecoveryClaimAdmissionService`,
  `NativeEffectReconciliationAuthorityReconstructionService`,
  `CanonicalNativeEffectCorridor`, `NativeAuthority`, `NativePrincipal`,
  `NativeRootActs`, `AuthorityConsumptionStore`, `AtomicTransition`, and
  every reusable Operator Root/Imperator decision, caller-authority, typed
  custody, consumption and reconstruction contract identified by the
  preparation inventory.

Begin Batch 1 — Issuance Authority and At-Use Currentness Contracts only.

Define canonical, versioned and authority-empty contracts for:

1. the exact reconciliation-authority issuance decision and its separately
   provenanced competent issuer;
2. the single-purpose, single-use issuance authority and its exact target,
   holder, admission/lineage references, issuer, validity window and replay
   identity;
3. non-serializable process-local typed custody, without creating or delivering
   a real capability;
4. atomic consumption/publication semantics, lock identity/order,
   interruption cuts, exact retry convergence and changed-input conflict;
5. present-tense Root/native/source currentness revalidation at both the issuer
   cut and the claim-use cut;
6. explicit non-authorization by source provenance, service possession,
   historical approval, deterministic output or the already-consumed native
   transition authority carrying `continuing_authority: false`;
7. exact refusal/result vocabulary for missing, counterfeit, expired, replayed,
   substituted, consumed, stale, revoked, suspended, superseded, retired,
   migration-required and conflicted inputs; and
8. read-only reconstruction with no continuing power, separating current
   untimestamped Operator Root revocation from timestamped native/source
   lifecycle history.

Preserve the corrected Preparation Batch 0 distinctions:

- RR02, RR05 and RR11 are transitively bounded expiry preservation cases, not
  at-use stale-capability races;
- independently mutable Root revocation, native-principal revocation, source
  generation and lifecycle changes require present-tense revalidation;
- RR07-RR10 require distinct `SUSPEND`, `SUPERSEDE`, `REVOKE`, `EXPIRE`,
  `RETIRE` and v3 migration/currentness refusal outcomes;
- CUR08A is fragmented because current untimestamped Root revocation blocks
  historical reconstruction;
- CUR08B may preserve timestamped lifecycle history only while current Root
  eligibility remains satisfied.

Implement only contract/schema/value definitions that cannot issue, resolve,
consume, publish or reconstruct operational evidence, plus authority-empty
contract tests and versioned documentation. Do not modify any existing
production issuer, resolver, claim, recovery, corridor, container or command
behavior. Do not wire a new service.

Do not create or consume a real issuance decision, authority, capability,
reconciliation authority, claim, receipt or runtime record. Do not mutate
runtime state, access a credential, invoke AgentMail/provider, perform
network/external I/O, execute a mission/live trial/email, open Iron
Gate/Lazaretto, claim terminal closure, repair the current Root-history
limitation or restore Batch 7.

Produce:

- a versioned Batch 1 contract specification;
- the smallest authority-empty contract/value definitions;
- focused contract and documentary tests;
- an updated campaign status/countdown; and
- a Batch 1 completion handoff reporting exact files, invariants, exclusions,
  focused/full PHPUnit commands and the four remaining stages.

Run the focused Batch 1 tests and the complete PHPUnit suite locally. Do not
claim GitHub CI until the exact SHA has actually passed it.

Batch 2 is not authorized. Stop at
`BATCH_1_COMPLETE_RECONCILIATION_ISSUANCE_AUTHORITY_CURRENTNESS_CONTRACTS_DEFINED`.

No shorthand continuation language, green test, prior merge, “clear,”
“forward,” or Latin motto extends this authority.
```

## Completion boundary

A successful Batch 1 defines the law but exercises no power. It must leave four
stages and stop before any Rooted issuance decision, typed capability delivery,
atomic consumption, issuer enforcement or provider-adjacent behavior.
