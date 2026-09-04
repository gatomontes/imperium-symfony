# Canonical Native Effect Process Custody and Formal Closure Remediation — Batch 5 terminal audit candidate v1

`BATCH_5_SEPARATELY_SEQUENCED_AUDIT_EXECUTED`
`LOCAL_FOCUSED_AND_FULL_PROOF_GREEN`
`TERMINAL_ACCEPTANCE_PENDING_SHA_BOUND_GITHUB_CI`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

## Audit baseline

Batch 5 began on a clean branch from merged Batch 4 `main` at `83fc4d6`. The
separate commit/merge chain entering the audit is:

| Stage | Commit | Merge |
| --- | --- | --- |
| Preparation Batch 0 | `7eec2a3` | `642b29e` |
| Batch 1 | `f07b7d7` | `eda148d` |
| Batch 2 | `e73d100` | `ce8fd9e` |
| Batch 3 | `96b3079` | `b66edd9` |
| Batch 4 | `c00e02c` | `83fc4d6` |

## Local evidence

The focused campaign command passed `73 tests / 637 assertions`. The first full
run completed `2368 tests / 50062 assertions` with one stale structural allow-
list failure caused by the governed receipt binder extraction. After correcting
that test-only expectation, its focused regression passed `7 / 65` and the
second full run passed `2368 tests / 50072 assertions` in 6:16.849 on PHP 8.4.14
for Windows.

After adding the three terminal documentary guards and reconciling the final
campaign-ready consumer, the exact candidate focused command passed `80 tests /
692 assertions` and the exact full candidate passed `2371 tests / 50100
assertions` in 6:01.249.

The Preparation Batch 0 guard was also corrected to verify its historical
snapshot as historical evidence instead of demanding that post-remediation
production bytes still equal the pre-remediation hashes.

## Adversarial verdict

The runtime satisfies the scoped custody/recovery design under the documented
trusted-local-store and single-host assumptions. Serialization, unserialization,
clone, fresh process and supported fork inheritance refuse custody. Runtime PID
is not confused with authority prose; a private nonce addresses PID reuse.
Callback execution cannot return existing receipts or bind sealed responses.
Forward completion requires an exact durable claim, has no callback/provider/
credential input, and is replay-idempotent without callback reinvocation.

Multi-host distributed custody, hostile memory inspection, direct compromise of
the trusted immutable store, and an upstream reconciliation-authority producer
remain explicit boundaries. No such capability is implied here.

## Pending evidence gate

This candidate deliberately withholds terminal acceptance until the Batch 5
merge is pushed and the repository's Ubuntu/PHP 8.4 GitHub Actions workflow is
green for that exact SHA. The run URL, run id, SHA, job and conclusion must be
recorded without inference. Until then, zero campaign stages may be reported as
remaining, but closure may not be claimed.

No provider, credential, AgentMail, network, mission, email, Iron Gate or
Lazaretto effect was exercised. Batch 7 remains separately suspended.
