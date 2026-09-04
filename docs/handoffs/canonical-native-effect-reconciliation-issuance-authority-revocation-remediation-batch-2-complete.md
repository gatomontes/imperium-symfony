# Canonical Native Effect Reconciliation Issuance Authority and Revocation-at-Use Remediation — Batch 2 complete

`BATCH_2_COMPLETE_ROOTED_DECISION_CUSTODY_AND_ATOMIC_PUBLICATION`
`BATCH_3_NEXT_UNDER_EXISTING_SEQUENTIAL_LOCAL_AUTHORITY`
`NO_REMOTE_PUBLICATION_AUTHORIZED`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

Batch 2 implements the finite Root/native-provenanced decision, separate exact
issuance authority, process-local typed custody and semantic-target-scoped
consumption/publication cut described in the Batch 1 contracts. Exact retries
complete or return the same deterministic authority and issuance evidence;
changed target inputs conflict before a second semantic winner.

The legacy public issuer signature remains unchanged by design. Batch 3 must
require typed issuance custody at that boundary, migrate every repository caller
and add present-tense revalidation at issuer and claim-use cuts.

## Exact implementation files

- `src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationIssuanceAuthorizationService.php`
- `src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationIssuanceAuthorityResolver.php`
- `src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationIssuanceCapability.php`
- `src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationIssuancePublicationService.php`
- `src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationAuthorityIssuanceService.php`
- `tests/Imperium/Runtime/CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationBatch2Test.php`
- `docs/frozen-runtime-coverage-tripwire-restoration-inventory-v1.tsv`
- `docs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-batch-2-rooted-decision-custody-publication-v1.md`
- `docs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-evidence-ledger-v1.json`
- this handoff.

## Local evidence

- Batch 2 focused: `27 tests / 143 assertions`, passed.
- First complete suite: `2541 tests / 49504 assertions`, three mechanical
  frozen-inventory failures and no behavioral failure.
- Batch 2 plus corrected frozen tripwires: `36 tests / 4046 assertions`, passed.
- Corrected complete suite: `2542 tests / 51695 assertions`, passed in
  `00:06:02.247` on PHP 8.4.14 / PHPUnit 13.3.0.

No GitHub CI, remote review, merge, provider effect, credential access or
external I/O is claimed.
