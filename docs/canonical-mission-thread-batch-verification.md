# Canonical mission thread batch verification record

All commands ran locally in `E:\htdocs\imperium` without credentials, providers, network access,
remote mutation, or a live trial.

| Batch | Verification | Result |
|---|---|---|
| 0 | Focused reconciliation baseline command recorded in the Batch 0 dossier | pass: 124 tests / 1,078 assertions |
| 1 | `php vendor/bin/phpunit tests/Imperium/Runtime/CanonicalMissionThreadBatch1Test.php` | pass: 3 tests / 11 assertions |
| 2–3 | Mission/capability plus reconciliation propagation focused set through shared-exclusion Batch 3 | pass: 83 tests / 415 assertions |
| 4–6 | Four canonical campaign test files | pass: 13 tests / 78 assertions |
| Compatibility correction | Previously failing invalid-expiry negative test | pass: 1 test / 5 assertions |
| Terminal full suite | `php vendor/bin/phpunit tests` | pass: 2,662 tests / 52,385 assertions; 06:58.812; 148 MB |

An earlier full-suite attempt produced one error because the test-only mission fixture rejected an
intentionally invalid target issuance window before the established production validator. The
fixture mission validity was separated from the deliberately invalid requested issuance validity;
the isolated negative test then passed with its original `CNE610` result. The terminal full-suite
result above is the subsequent clean run, not the failed precursor.

