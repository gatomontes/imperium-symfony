# Canonical Mission Thread and Authority Provenance terminal audit

## Audit boundary

Baseline is exact local commit `2527b33925bf3ef47d029786e60a6aefe752737b`. The reviewed
implementation tip is `12a8f27f823e283a3a155397321261e7f859e6b1` on local branch
`codex/canonical-mission-thread-authority-provenance`. The implementation diff adds the mission
kernel and tests, binds the reconciliation issuance path to mission authority, removes its
unauthenticated corridor factory, and adds documentation. No remote state is part of this audit.

## Completion reconciliation

| Criterion | Executable/local evidence | Disposition |
|---|---|---|
| Explicit Operator mission accepted | `OperatorMissionBoundary` + Batch 2 test | proved |
| Exact grant authorizes every reference act | five grants/five consumptions asserted in Batch 4/5 | proved for the reference mission |
| Same mission identity throughout | transition and consumption loops plus reconciliation propagation assertions | proved on covered paths |
| Narrow, expiring, non-replayable capability | signature/binding/expiry/revocation/replay tests | proved in process-local mission boundary |
| Consumer cannot self-authorize | consumer-only interface and absent corridor factory | proved for named writers |
| Harmless reference mission terminal | exact baseline SHA fixture reaches `COMPLETED` with one evidence reference | proved |
| Progress inspectable | direct `status.json` projection assertions | proved |
| Refused/aborted/expired truthful | terminal receipt tests | proved |
| Adversarial fail-closed | campaign adversarial matrix and Batch 6 test | proved within declared matrix |
| Complete suite | 2,662 tests / 52,385 assertions | pass |
| Remote/live boundary | no network command, credential access, provider invocation, push, PR, merge, or live trial performed | observed local campaign fact |

## Bounded claims and residual limits

Coverage is limited to the four writers named in the adversarial matrix. The campaign does not
claim that every authority-like path elsewhere in Imperium has been mission-migrated. The
two-contender capability proof uses two same-process Fibers sharing one verifier; the existing
reconciliation shared-exclusion tests separately cover multi-process durable writer contention.
No claim is made that an in-memory reference capability registry is a distributed authority
service.

The concrete reference adapter uses fixed in-memory file fixtures associated with the exact
baseline SHA. It neither asserts that those bytes were re-read from Git objects nor generalizes its
single finding to the entire repository. Its purpose is to prove the authorized mission thread and
truthful terminal machinery without touching the inspected target.

Every terminal prose claim above is bounded to an executable test, an inspected local diff, or a
directly observed local command result.

