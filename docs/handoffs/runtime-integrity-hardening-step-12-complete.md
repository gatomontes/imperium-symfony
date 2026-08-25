# Runtime-integrity hardening Step 12 complete

The critical provider and terminal boundaries now share one authoritative replay-fingerprint contract.

- `ReplayFingerprint` canonically hashes complete authoritative input sets and performs constant-time matching.
- Provider-invocation claims use the shared primitive without changing their stable idempotency keys.
- Terminal retirement stores the complete custody, binding, terminal, and authorization fingerprint before its first mutation.
- A changed terminal payload or state lineage cannot reuse an existing transition.
- Provider-independent turn recovery returns an existing turn only to the same exact recovery authorization.
- Contract tests prove canonical key ordering, changed-input rejection, conflicting terminal replay, and cross-authorization recovery rejection.

This is the first migration slice of system-wide replay standardization. It grants no authority and never repeats provider I/O or terminal side effects.
