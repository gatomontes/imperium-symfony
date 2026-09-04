# Handoff — reconciliation authority provenance remediation Batch 2 complete

Marker: `BATCH_2_COMPLETE_ROOT_PROVENANCED_ISSUANCE_AND_ATOMIC_CUSTODY`

Root-resolved issuance, immutable issuance evidence, source revalidation,
process-local typed custody and atomic authority-to-claim derivation now exist.
The authority and claim contain no provider, credential, retry or continuing
power. Tests use only the pre-existing disposable provider double to establish a
sealed-response fixture; no provider or credential boundary is reached.

Batch 3 must replace `admit(array $authority, int $at)`, route the canonical
corridor through issuer/resolver/typed custody, consume claims for exact receipt
completion, and remove all accepted self-sealed fixture bypasses.

Focused gate:

```powershell
php vendor/bin/phpunit tests/Imperium/Runtime/CanonicalNativeEffectReconciliationAuthorityProvenanceRemediationBatch2Test.php
```

