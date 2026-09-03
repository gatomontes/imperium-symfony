# Canonical Native Effect Corridor Activation — implementation ledger v1

Baseline requested by the Operator:
`3f5d53ce8dfff0a74702b32500696373823b5e41`.

## Completed bounded stages

| Stage | Disposition | Principal artifacts | Required PHPUnit result |
|---|---|---|---|
| Preparation Batch 0 | Complete, documentary/structural only | preparation inventory, call graph, authority/effect cut matrix, reading ledger, handoff | 4 tests, 270 assertions |
| Batch 1 | Complete, contracts only | effect authority, admission and result contracts | full: 2,102 tests, 47,585 assertions |
| Batch 2 | Complete, inert exact-root join | `NativeEffectAdmissionValidator` | corrected full: 2,116 tests, 47,652 assertions |
| Batch 3 | Complete, atomic pre-I/O cut | secret-free capability/issuer and `NativeEffectAtomicAdmissionService` | full: 2,134 tests, 47,749 assertions |
| Batch 4 | Complete, provider doubles only | callback-start, raw response provenance, receipt and read-only reconstruction | full: 2,156 tests, 47,862 assertions |
| Batch 5 | Complete, adversarial/application proof | separate-process worker, contention/interruption/race proof, auto-discovered corridor facade | corrected full: 2,184 tests, 48,013 assertions |
| Batch 6 | Complete, package preparation only | blocked package template, pure evidence sanitizer, runbook and stop handoff | full: 2,189 tests, 48,255 assertions |

## Executable authority versus historical evidence

The exact native join, one-effect admission aggregate and provider-double
continuation are executable local code. The provider-double result is synthetic
proof only. No live provider-effect authority was issued or consumed; no live
credential or transport is wired; no retained live receipt exists. Historical
Iron Gate, journal, provider-binding and crash evidence remains evidence of its
own bounded campaigns and does not authorize this corridor.

## Remaining stages

1. **Batch 7 — blocked:** requires a new Operator message containing exactly
   `AUTHORIZE_CANONICAL_NATIVE_EFFECT_LIVE_TRIAL_ONCE` plus the exact approved
   disposable destination and `email.send` operation. Only then may one live
   command/credential/provider edge be implemented and one effect attempted.
2. **Batch 8 — blocked on Batch 7 evidence:** from clean merged Batch 7 `main`,
   independently verify the retained private/sanitized evidence and conduct a
   separate terminal Blackquill audit.

The campaign exit criterion is not met until both remaining stages complete.
Neither green tests nor this ledger supplies the missing authorization.

## Local command

```powershell
php vendor/bin/phpunit tests
```
