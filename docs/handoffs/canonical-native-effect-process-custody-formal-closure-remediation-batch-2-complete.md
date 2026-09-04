# Canonical Native Effect Process Custody and Formal Closure Remediation — Batch 2 complete

`BATCH_2_PROCESS_BOUND_CUSTODY_AND_TRANSFER_REFUSAL_COMPLETE`
`BATCH_3_EXECUTION_RECOVERY_SEPARATION_NEXT`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

The current issuer now requires actual PID, issuer nonce, exact registry and
exact object identity. Serialization, unserialization and cloning fail closed;
fork inheritance refuses when supported because the child PID differs.

Three stages remain: Batch 3 execution/recovery separation, Batch 4 complete
adversarial/application proof, and separately sequenced Batch 5 terminal audit.

```powershell
php vendor/bin/phpunit tests/Imperium/Runtime/CanonicalNativeEffectProcessCustodyFormalClosureRemediationBatch2Test.php
```

Focused local result: PHPUnit 13.3.0 / PHP 8.4.14,
`OK (7 tests, 33 assertions)`. The inherited continuation Batches 2–4
regression set passed at `OK (81 tests, 622 assertions)`. No CI result is
claimed yet.

No recovery authority/claim was admitted, no provider double or credential was
used, and no network, mission or live effect occurred. Batch 7 remains
suspended.
