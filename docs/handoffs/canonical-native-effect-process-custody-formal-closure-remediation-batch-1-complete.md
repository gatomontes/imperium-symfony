# Canonical Native Effect Process Custody and Formal Closure Remediation — Batch 1 complete

`BATCH_1_PROCESS_INCARNATION_AND_RECOVERY_CONTRACTS_COMPLETE_NO_RUNTIME_WIRING`
`BATCH_2_PROCESS_BOUND_CUSTODY_NEXT`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

Batch 1 defines the PID-plus-nonce incarnation contract, continuation
non-transferability invariants, exact reconciliation authority/claim schemas,
three disjoint acts and the acyclic recovery lock order. Runtime issuers,
execution/recovery behavior, configuration and service wiring are unchanged.

Four stages remain: Batch 2 process-bound custody; Batch 3 execution/recovery
separation; Batch 4 adversarial/application proof; Batch 5 clean-main
independent terminal audit with source-attributed local and GitHub CI evidence.

Focused command:

```powershell
php vendor/bin/phpunit tests/Imperium/Runtime/CanonicalNativeEffectProcessCustodyFormalClosureRemediationBatch1Test.php
```

Focused local result: PHPUnit 13.3.0 / PHP 8.4.14,
`OK (6 tests, 53 assertions)`. The prior continuation contract regression gate
also passed at `OK (7 tests, 65 assertions)`. No CI result is claimed yet.

No authority/capability/claim was issued or consumed, no durable runtime state
was published and no provider, credential, network, mission or live effect was
reached. Batch 7 remains suspended.
