# Corridor Disposition Principal Authority Remediation interruption evidence

`BATCH_3_OFFLINE_REPLAY_AND_INTERRUPTION_PROOF_COMPLETE`

Batch 3 runs twelve disposable-root cases: scope-grant issuance and consumption, successor commit,
separate activation, and caller-authority issuance, each cut before authority consumption, after
consumption but before target commit, and after target commit.

Exact replay converges. Changed evidence refuses, expiry and revocation refuse, and single-winner
contention admits only the original offline consumer. Recovery reopens the immutable stores and
performs read-only recovery after convergence. Every target is explicitly offline evidence: no live
authority is issued or consumed, no principal or binding is activated, and no activation artifact
is mutated.

`REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE` remains the continuing custody refusal. The proof performs
no provider invocation, credential or capability handling, or external I/O, and creates no
production issuer, consumer, or current-state registry.
