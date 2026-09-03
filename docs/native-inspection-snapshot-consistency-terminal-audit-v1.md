> Historical status: `HISTORICAL_TERMINAL_ACCEPTANCE_INVALID_UNAUTHORIZED_SCOPE_EXPANSION`.
> This audit remains technical evidence, but it cannot supply the missing authority
> for Batches 1–5. Current disposition is in
> `docs/native-inspection-snapshot-consistency-corrective-admission-audit-v1.md`.

# Native Inspection Snapshot Consistency terminal Blackquill audit v1

`NATIVE_INSPECTION_SNAPSHOT_CONSISTENCY_TERMINAL_AUDIT_ACCEPTED`
`NATIVE_INSPECTION_SNAPSHOT_CONSISTENCY_CAMPAIGN_COMPLETE`

Audit baseline: clean committed Batch 4 `main` at
`f44a4cf5375bf592fa6da6a51ce95a34b2fb645a`.
The exact reviewed implementation and explicit predecessor-hash supersessions are
pinned in `docs/native-inspection-snapshot-consistency-terminal-reading-ledger-v1.json`.

## Claim

Unlocked native inspection now returns an existing public result only from one
coherent observation of its declared cooperative local filesystem read set, with
at most two attempts and conservative refusal under continued instability. It
does not become execution, retry, recovery or provider authority.

## Weak points challenged

### “Whole read set” could be ceremonial language

It is not. `NativeInspectionSnapshot` names the claim, authorization issuance,
effect journal, native event, all `NativeState::SOURCES`, trust and registered
legacy bases. It records missing bases, directories, membership, type and every
non-lock regular-file digest. Symlinks and capture failures refuse. The old
fragmented claim/binding and inner-reconstructor checks remain defense in depth.

### Nested snapshots could certify different worlds

They do not. A process-local scope keyed by canonical state identity encloses the
outer entrypoint; nested `forJournal`, `forClaim`, `interpret`, `read` and
reconstruction execute inside that same attempt. Cleanup occurs in `finally`.
The proof records exactly one manifest-A/manifest-B pair for a stable nested
interpretation.

### “Bounded” could hide an infinite retry euphemism

The code has `MAX_ATTEMPTS = 2`, no recursion-based retry, sleep, repair or
fallback. Two separate-process mutations force two discarded attempts and the
existing conservative result. This is attempt-bounded, not a wall-clock service
level objective: filesystem size and operating-system I/O latency remain real.

### A read lock could smuggle mutation and deadlock into inspection

The new component has no `AtomicTransition`, `flock`, mkdir or write operation.
Already-locked authorizing callers do not reacquire the native lock. A terminated
inspector leaves the semantic state unchanged and no inspection lock exists.

### A snapshot token could become counterfeit authority

No manifest, digest, epoch, token or new field escapes. Existing public key order
and classifications remain intact. Inspection outputs retain false effect/retry
fields, the direct reconstruction retains false execution/effect fields, and the
lower-level read result gains no authority. The CLI cannot transfer freshness;
any later authorizing consumer must inspect and authorize under its own boundary.

### The race proof could be synchronized theater

The barriers are constructor-only test instrumentation with no container binding
and live outside the semantic manifest. Competing transition/migration,
revocation and churn actors are sibling PHP processes using canonical writers.
The interrupted-journal and expiry cases use fresh inspector processes. The
tests assert post-race classifications, attempt count and byte stability, not
merely that a rendezvous marker was reached.

### Integration could stop at direct construction

It does not. The real Symfony-discovered `NativeBindingReader`, real
`imperium:email:send-agentmail --inspect-claim` command and established
journal-bound broker are exercised. The proof checkpoint is null. CLI inspection
is byte-stable and excludes credential/payload material. The broker still refuses
committed native state before credential access or callback start.

## Residual boundaries

- A valid result can become stale immediately after return. There is no
  linearizability, lease, cache, signature or admission-transfer guarantee.
- Cooperative single-host atomic-rename storage is assumed. Network filesystems,
  hostile same-content ABA replacement, physical power loss and state outside
  the declared project root remain `DEFERRED_BOUNDARY`.
- The two-attempt cap bounds retries, not scan duration or directory-size cost.
- `.lock` files are deliberately non-semantic. Their contents cannot influence a
  classification; aliases remain prohibited even when named as locks.
- The optional checkpoint is a test seam. Binding it in production would require
  a new campaign and invalidates this acceptance.

These are honest limits, not defects against the selected contract.

## Verification and correction

Every batch passed its focused PHPUnit gate before the next batch began. The
first repository-wide run completed 2,091 tests and exposed two documentary hash
tripwires: the prior consumer audit still pinned the pre-campaign reader and the
preparation ledger still pinned the pre-completion campaign pointer. No runtime
assertion failed. The correction preserves those predecessor hashes and requires
their exact successors in the terminal reading ledger. The affected tests then
passed 15 tests / 743 assertions, and the corrected repository-wide run passed
2,092 tests / 47,277 assertions.

## Verdict

Accepted. The earlier inspection path was a patchwork of individually sensible
checks with no right to call itself coherent. The campaign replaced that gap with
one enforceable observation boundary and then attacked the boundary at its
publication cuts. No material defect remains inside the declared cooperative
single-host, non-authorizing scope.

The campaign closes at
`NATIVE_INSPECTION_SNAPSHOT_CONSISTENCY_CAMPAIGN_COMPLETE`. `BOUND_INACTIVE`,
historical v3 `NOT_IMPLEMENTED`, `UNKNOWN_REPLAY_PROHIBITED`, the bounded pre-effect
acceptance, and the prohibition on opening Iron Gate or Lazaretto
remain controlling.

## If a stronger version is wanted

Linearizable or transferable inspection would require an explicit lease/epoch,
reentrant lock ownership, timeout semantics, lifecycle rules and consuming-side
authorization checks. Calling the current result “fresh” would be bureaucratic
fiction. Open that work only as a separately authorized campaign.
