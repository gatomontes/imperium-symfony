# Canonical Native Effect Reconciliation Issuance Authority and Revocation-at-Use Remediation — Batch 3 complete

`BATCH_3_COMPLETE_TYPED_ISSUER_AND_AT_USE_CURRENTNESS`
`BATCH_4_NEXT_UNDER_EXISTING_SEQUENTIAL_LOCAL_AUTHORITY`
`NO_REMOTE_PUBLICATION_AUTHORIZED`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

Batch 3 removes the public unguarded reconciliation-authority write signature.
The issuer requires exact process-local issuance custody, the corridor shares
the delivering issuance resolver, and every repository call site has migrated.
Issuer publication and claim publication now each revalidate independently
mutable Root, native-principal, source-generation and source-lifecycle state in
the governed exclusion immediately before consumption and publication.

Exact lifecycle refusals remain distinct, v3 lifecycle drift requires migration,
and RR02/RR05/RR11 expiry preservation remains intact.

Batch 4 owns the bounded adversarial, application, concurrency, interruption,
fresh-process and reconstruction proof.

## Changed surfaces

- `src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationAuthorityIssuanceService.php`
- `src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationIssuanceAuthorizationService.php`
- `src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationIssuanceAuthorityResolver.php`
- `src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationIssuancePublicationService.php`
- `src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationAuthoritySourceResolver.php`
- `src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationAuthorityResolver.php`
- `src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationAuthorityClaimDerivationService.php`
- `src/Imperium/Runtime/NativeEffect/CanonicalNativeEffectCorridor.php`
- the canonical native test fixture helper, prior reconciliation callers and
  `tests/Imperium/Runtime/Support/reconciliation_authority_worker.php`
- `tests/Imperium/Runtime/CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationBatch3Test.php`
- the Batch 3 specification, evidence ledger and this handoff.

## Local evidence

- Batch 3 focused class: `35 tests / 147 assertions`, passed.
- Migrated caller/currentness focus: `260 tests / 1557 assertions`, passed.
- Preparation compatibility plus Batch 3: `45 tests / 361 assertions`, passed.
- Complete PHPUnit: `2577 tests / 51843 assertions` in `00:06:26.398`, passed.

No GitHub CI, remote review, merge, provider effect, credential access or
external I/O is claimed.
