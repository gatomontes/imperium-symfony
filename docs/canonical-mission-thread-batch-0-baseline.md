# Canonical Mission Thread campaign — Batch 0 baseline

## Frozen start

| Datum | Exact value |
|---|---|
| Repository | `E:\htdocs\imperium` |
| Baseline commit | `2527b33925bf3ef47d029786e60a6aefe752737b` |
| Starting branch | `main` tracking `origin/main`, `+0/-0` |
| Starting worktree | clean (`git status --porcelain=v2 --branch`) |
| Campaign branch | `codex/canonical-mission-thread-authority-provenance` |
| PHP | `8.4.14` |
| Symfony | `8.1.4` |
| PHPUnit | `13.3.0` |

No network, credential, provider, remote mutation, live trial, push, pull-request operation, or
remote review occurred.

## Reproducible focused baseline

The command below passed at the frozen baseline with **124 tests / 1,078 assertions**:

```text
php vendor/bin/phpunit tests/Imperium/Runtime/CanonicalNativeEffectReconciliationAuthorityProvenanceRemediationCampaignReadyTest.php tests/Imperium/Runtime/CanonicalNativeEffectReconciliationAuthorityProvenanceRemediationPreparationBatch0Test.php tests/Imperium/Runtime/CanonicalNativeEffectReconciliationAuthorityProvenanceRemediationBatch1Test.php tests/Imperium/Runtime/CanonicalNativeEffectReconciliationAuthorityProvenanceRemediationBatch2Test.php tests/Imperium/Runtime/CanonicalNativeEffectReconciliationAuthorityProvenanceRemediationBatch3Test.php tests/Imperium/Runtime/CanonicalNativeEffectReconciliationAuthorityProvenanceRemediationBatch4Test.php tests/Imperium/Runtime/CanonicalNativeEffectReconciliationAuthorityProvenanceRemediationBatch5Test.php tests/Imperium/Runtime/CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationBatch1Test.php
```

## Reconciliation issuance authority inventory

| Role | Entry point | Authority behavior at baseline |
|---|---|---|
| Source reader | `NativeEffectReconciliationAuthoritySourceResolver` | Reconstructs admission, callback, response, native authority, principal, transition, and root-act lineage. Evidence only. |
| Target builder | `NativeEffectReconciliationAuthorityFactory` | Public pure factory constructs the future reconciliation authority from lineage. Construction corridor is not authenticated. |
| Decision + issuance-authority producer | `NativeEffectReconciliationIssuanceAuthorizationService::authorize` | Defect: infers a competent issuer from native-principal lineage and creates both decision and issuance authority without separately originated authorization custody. |
| Issuance-custody producer | `NativeEffectReconciliationIssuanceAuthorityResolver::resolve` | Converts durable issuance evidence into process-local custody. It proves possession/currentness, not Operator authorization. |
| Issuance consumer | `NativeEffectReconciliationAuthorityIssuanceService::issue` | Consumes issuance custody and publishes the target reconciliation authority and issuance receipt. |
| Reconciliation-custody producer | `NativeEffectReconciliationAuthorityResolver::resolve` | Converts the issued authority into process-local custody after currentness checks. |
| Reconciliation consumer | `NativeEffectReconciliationAuthorityClaimDerivationService::derive` | Consumes reconciliation custody and publishes the deterministic recovery claim. |

Downstream `NativeEffectForwardRecoveryClaimAdmissionService` consumes the resulting claim in the
native-effect recovery corridor. Existing atomic-transition, immutable-record, revocation, process
custody, and shared-before-target exclusion controls remain relevant but do not supply mission
authority.

## Baseline conclusion

The exact defect is architectural, not a missing lineage check: the authorization service is an
authority consumer and authority producer in the same call, with lineage-derived competence as
its root. Batch 2 must require separately originated, mission-bound Operator custody before that
service can publish either record. The public authority factory must be constrained so ordinary
domain callers cannot use it as an authority-issuance corridor.

