# Handoff — reconciliation authority provenance remediation Batch 1 complete

Marker: `BATCH_1_COMPLETE_CANONICAL_ISSUANCE_AND_CUSTODY_CONTRACTS_DEFINED`

Batch 1 defines the v2 authority, immutable issuance, trusted resolution, typed
custody, authority consumption, v2 claim, claim consumption and read-only
reconstruction contracts. It expressly rejects issuer prose, a caller-computed
digest and storage location as substitutes for authenticated issuance.

No production behavior, configuration or service wiring changed. No issuer or
capability was implemented; no authority, issuance record, consumption, claim or
receipt was created. No provider, callback, credential, network or external I/O
was reached.

The next authorized stage is Batch 2: implement Root-provenanced issuance,
immutable issuance evidence, trusted source resolution, process-local typed
custody and atomic authority-to-claim derivation, without provider reachability.
Batch 3 admission replacement is not part of this handoff.

Focused gate:

```powershell
php vendor/bin/phpunit tests/Imperium/Runtime/CanonicalNativeEffectReconciliationAuthorityProvenanceRemediationBatch1Test.php
```
