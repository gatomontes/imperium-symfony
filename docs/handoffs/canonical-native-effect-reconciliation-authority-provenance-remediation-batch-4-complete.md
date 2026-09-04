# Handoff — reconciliation authority provenance remediation Batch 4 complete

Marker: `BATCH_4_COMPLETE_ADVERSARIAL_APPLICATION_PROCESS_PROOF`

Counterfeit labels/digests, revoked or stale Root/native lineage, substituted
records, copied custody, competing processes, all three publication cuts, exact
replay, real container construction and read-only receipt-to-Root reconstruction
are now covered. The tests use disposable local state and a provider double only.

Batch 5 may begin only after this batch is committed and merged to a clean local
`main`. It must independently audit the chain, run focused and full PHPUnit,
retain exact Git/CI provenance, update canonical status consumers and decide
closure. No live provider or Batch 7 behavior is authorized.

Focused gate:

```powershell
php vendor/bin/phpunit tests/Imperium/Runtime/CanonicalNativeEffectReconciliationAuthorityProvenanceRemediationBatch4Test.php
```

