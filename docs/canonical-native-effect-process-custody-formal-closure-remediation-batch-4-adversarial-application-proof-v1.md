# Canonical Native Effect Process Custody and Formal Closure Remediation — Batch 4 adversarial/application proof v1

`BATCH_4_ADVERSARIAL_APPLICATION_PROOF_COMPLETE`
`BATCH_5_SEPARATELY_SEQUENCED_TERMINAL_AUDIT_NEXT`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

## Proof perimeter and result

The combined disposable suite covers `SER01-SER05`, `CLN01-CLN03`,
`PRC01-PRC09`, `LIF01-LIF03`, `CUT01-CUT11`, `API01-API03`, `REC01-REC08`,
`CB01-CB04`, `BND01-BND04`, `WIN01`, and conditional `LIN01`.

- Serialization, crafted unserialization and cloning refuse issuer,
  capability, admission outcome, process incarnation and combined graphs.
- The current runtime PID plus an issuer-private nonce distinguishes custody
  from authority-supplied labels. Fresh interpreters and issuers cannot restore
  the nonce; PID reuse is therefore insufficient. Missing or changed PID is a
  fail-closed condition. On Windows the spawned-process cases run through
  `proc_open`; where `pcntl_fork` exists the inherited-memory child refuses and
  the parent retains its unconsumed exact custody. The present Windows host
  records the fork case as platform-inapplicable, not as Linux evidence.
- Each corridor call creates a fresh continuation issuer. Kernel/container
  sharing supplies construction only and never substitutes for incarnation or
  exact-object custody.
- Admission and callback interruption cuts retain their previous atomic,
  immutable and unknown-replay behavior. First execution cannot return an
  existing receipt or bind a sealed response. Read-only `reconstruct()` is the
  only existing-receipt route.
- Exact, unexpired reconciliation authority is required to derive a durable
  forward-recovery claim. Missing, expired, digest-substituted and lineage-
  substituted inputs refuse before receipt publication. Exact claim replay
  returns the one receipt without provider or callback reinvocation.
- Tuple, authority, capability, response, expiry, cancellation, interruption,
  worker and container substitution regressions remain in the combined suite.
- Recovery and worker sources contain no credential resolver, provider
  transport, environment lookup or network implementation. Multi-host custody
  remains `DEFERRED_BOUNDARY`; it is not claimed by this campaign.

## Deployment and threat assumptions

The local immutable store and lock root are trusted against arbitrary direct
filesystem writes. Reconciliation authorities are produced by the named
governance issuer outside this corridor; this campaign admits their exact
sealed record and does not add that upstream producer. No claim is made for a
distributed lock, shared filesystem across hosts, crash-proof secret
zeroization, hostile debugger/memory access, or PID uniqueness without the
private incarnation nonce.

## Provenance entering Batch 4

Preparation Batch 0, Batches 1, 2 and 3 were separately committed and merged:
`7eec2a3`/`642b29e`, `f07b7d7`/`eda148d`, `e73d100`/`ce8fd9e`, and
`96b3079`/`b66edd9`. GitHub CI evidence is deliberately reserved for the
separately sequenced Batch 5 audit and must be tied to the pushed candidate SHA.

Provider doubles and disposable local state only were used. No credential,
provider, network, mission, email, Iron Gate or Lazaretto effect occurred.
