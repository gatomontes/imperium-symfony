# Canonical Native Effect Continuation and Exclusivity Remediation — Batch 5 Blackquill audit v1

`BATCH_5_LOCAL_TERMINAL_AUDIT_EXECUTED_FORMAL_CLOSURE_BLOCKED`
`LOCAL_REMEDIATION_PROOF_PASSED`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

Audited workspace: synchronized source baseline
`77d26f4c7f5655dcce67b5c3765714b5c0ede85e` plus uncommitted remediation
Batches 0–5.

## Claim

The remediation claims to close uninterrupted-continuation custody,
cross-authority semantic tuple exclusivity and caller-authority receipt
substitution without introducing provider/network behavior.

## Finding disposition

| Finding | Executable correction | Adversarial evidence | Verdict |
| --- | --- | --- | --- |
| BQ-CNE-01 | Only a newly published winner receives a registry-recognized continuation object; first callback consumes it; fresh replay gets none | Copied/fresh issuer direct refusal and admit-exit/fresh-process worker refusal before callback start | `RESOLVED_LOCALLY` |
| BQ-CNE-02 | Authority-independent full semantic tuple id drives one shared tuple lock/admission; authority consumption remains separate | Sequential and simultaneous distinct-authority/same-tuple cases produce one winner and one unconsumed loser | `RESOLVED_LOCALLY` |
| BQ-CNE-03 | Execute has no authority-array parameter; request and receipt use sealed admitted receipt input | Old-digest and resealed substitutions refuse; tampered admission digest refuses; receipt retains admitted provider/return/authority facts | `RESOLVED_LOCALLY` |
| BQ-CNE-04 | Historical local 48,255 and CI 48,253 totals remain distinct; new local runs are individually recorded | Versioned source-labelled evidence ledger contains failures, corrections and passes without inventing CI | `RESOLVED` |
| Additional lock objection | Callback start commits under continuation lock, then the lock releases before the double runs | Double acquires the same lock non-blocking during callback and succeeds | `RESOLVED_LOCALLY` |

## Weak point

Calling this a formally complete terminal audit would be bureaucratic theater.
The governing plan requires clean merged Batch 4 `main` and independent
verification. This run is deliberately uninterrupted: the same workspace and agent
produced and reviewed the changes, the tree is uncommitted, and no CI run
exists. Green local tests do not alchemize those missing facts into provenance.

The proof is also cooperative single-filesystem proof. `flock`, atomic rename
and provider doubles say nothing about multi-host exclusion, real credential
custody, remote authorship or provider-side idempotency. Those remain deferred
boundaries, not defects concealed by optimism.

## Verdict

The corrected local runtime and adversarial proof pass. Formal campaign closure
is `BLOCKED` on a clean merged Batch 4/implementation baseline followed by a
separate source-attributed Batch 5 rerun/audit. Batch 7 remains suspended. No
live authority is restored, and no exact live-trial marker can override the
missing merged-baseline audit.

## Stronger completion path

1. Review and commit/merge Batches 0–4 without the terminal closure claim.
2. Start from clean synchronized merged `main`.
3. Run the Batch 5 focused gate and complete full PHPUnit suite.
4. Record actual local and CI results separately.
5. Conduct a separate terminal Blackquill review of that merged tree.
6. Only that review may mark the remediation campaign complete or reconsider
   the original Batch 7 suspension.
