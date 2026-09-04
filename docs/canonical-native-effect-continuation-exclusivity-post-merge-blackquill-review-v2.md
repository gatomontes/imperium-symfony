# Canonical Native Effect Continuation and Exclusivity — post-merge Blackquill review v2

`CONTINUATION_EXCLUSIVITY_REMEDIATION_FORMAL_CLOSURE_REFUSED_PROCESS_CUSTODY_UNPROVED`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

Reviewed merged baseline:
`dc62d4e564bfde3230117d740ec157e0928abf35`.

## Verified corrections

- authority-independent exact semantic tuple locking and one durable winner;
- unconsumed losing-authority disposition;
- removal of the caller authority array from callback/receipt construction;
- sealed admission-derived provider request and receipt input;
- callback-start publication before the provider double;
- no credential resolver, live transport, command or network edge.

These are bounded local/cooperative-filesystem corrections. They are retained as
candidate substrate.

## BQ-CNE-V2-01 — process-local custody is not process-bound

`NativeEffectContinuationCapabilityIssuer` recognizes object identity in an
in-memory registry. Neither the issuer nor capability records and validates an
actual OS process incarnation. The `processBoundaryId` is copied from the
authority execution-boundary reference, not derived from the running process.

The classes provide no `__serialize`, `__unserialize`, `__sleep`, `__wakeup` or
`__clone` refusal. PHP object serialization stores object variables by default.
A serialized issuer/capability graph, or a fork inheriting that graph, is not
covered by the fresh-empty-issuer/lookalike tests. Therefore the evidence proves
fresh-registry refusal, not non-transferable same-process custody.

## BQ-CNE-V2-02 — forward completion lacks its own governed boundary

`NativeEffectDoubleExecutionService::execute()` returns an existing receipt or
binds a sealed response before validating/consuming continuation custody.
Forward-only recovery is legitimate, but execution, reconstruction and
receipt-binding recovery are distinct acts. A fresh process currently reaches
forward mutation through the execution method without an explicit
reconciliation authority/claim.

## BQ-CNE-V2-03 — required sequencing and independent verification absent

The campaign plan required Batches 1–4 to be reviewed and merged before Batch 5
started from clean synchronized `main`. Instead Batches 0–5 were committed
together at `ee6e983941a23b75d9ee77b4ba4aa741a34bdbd6` and then merged.
The campaign's own Batch 5 audit correctly records formal closure as blocked.

No GitHub workflow run is attached to merge `dc62d4e...`. The reported
`2,291 tests / 49,398 assertions` result remains local evidence, not independent
CI verification.

## BQ-CNE-V2-04 — canonical steps and flow were not advanced

`docs/delegate-mission-flow.md`, `docs/handoffs/README.md` and
`todo/blackquill-todos.md` still describe the prior campaign as
`PREPARATION_BATCH_0_AUTHORIZED_ONLY`. The merged implementation and its refused
closure were not reconciled into the canonical continuation record.

## Verdict

The prior three defects were materially narrowed, but the strongest claim—
same-process continuation custody—remains unproved, and formal closure is
procedurally unavailable. Batch 7 remains suspended.

Closure requires process-incarnation binding with serialization/clone/fork
pressure tests, a distinct governed forward-recovery API, corrected canonical
state, separately merged implementation stages and an independent clean-main
terminal audit with source-attributed local and CI evidence.
