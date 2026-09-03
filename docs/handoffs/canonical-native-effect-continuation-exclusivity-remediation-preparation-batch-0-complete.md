# Canonical Native Effect Continuation and Exclusivity Remediation — Preparation Batch 0 complete

`PREPARATION_BATCH_0_COMPLETE_CONTINUATION_EXCLUSIVITY_GAPS_CLASSIFIED`
`DOCUMENTARY_ONLY_NO_RUNTIME_CHANGE`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

Preparation Batch 0 is complete against clean synchronized `main` baseline
`77d26f4c7f5655dcce67b5c3765714b5c0ede85e`.

The exact counterexamples are now pinned. A fresh process can start the first
provider-double callback from durable admission because continuation requires
no process-local object. Distinct authorities carrying the same semantic effect
tuple have different replay ids/locks and can both publish. A caller can change
`expected_return_contract`, retain the old authority digest, pass the reference
comparison and substitute receipt meaning. Historical Batch 6 local and CI
assertion totals are retained separately.

The smallest correction is fixed as an authority-independent tuple winner plus
exact authority consumption, followed by a non-durable, single-use continuation
object created only for the newly published winner. First callback requires the
exact uninterrupted object/registry. Every callback/response/receipt meaning
derives from immutable admission facts; fresh-process recovery is read-only or
forward-only after a sealed response. Losing authorities remain unconsumed and
are explicitly refused.

Artifacts:

- `docs/canonical-native-effect-continuation-exclusivity-remediation-preparation-inventory-v1.md`;
- `docs/canonical-native-effect-continuation-exclusivity-remediation-corrected-call-graph-v1.md`;
- `docs/canonical-native-effect-continuation-exclusivity-remediation-identity-lock-matrix-v1.md`;
- `docs/canonical-native-effect-continuation-exclusivity-remediation-adversarial-proof-matrix-v1.md`;
- `docs/canonical-native-effect-continuation-exclusivity-remediation-reading-ledger-v1.json`.

## Exact remaining stages

1. Batch 1 — corrected contracts and identities.
2. Batch 2 — atomic tuple winner and continuation custody.
3. Batch 3 — admission-derived continuation and receipt binding.
4. Batch 4 — adversarial process, contention and substitution proof.
5. Batch 5 — source-attributed evidence reconciliation and terminal Blackquill
   audit from clean merged Batch 4 `main`.

The original corridor Batch 7 remains suspended and is not one of these five
authorized stages. Batch 1 is not authorized by this handoff.

## Local documentary PHPUnit command

```powershell
php vendor/bin/phpunit tests/Imperium/Runtime/CanonicalNativeEffectContinuationExclusivityRemediationPreparationBatch0Test.php
```

Focused local result on the audited workspace: PHPUnit 13.3.0 / PHP 8.4.14,
5 tests and 181 assertions, `OK`. No CI result is claimed by this handoff.

No production runtime behavior, service wiring or configuration changed. No
authority/capability was issued or consumed; no credential was accessed; no
provider, network, mission, live trial or email operation occurred; Iron Gate
and Lazaretto remained closed; no non-disposable runtime state was published.

Stop here at
`PREPARATION_BATCH_0_COMPLETE_CONTINUATION_EXCLUSIVITY_GAPS_CLASSIFIED`.
