# Canonical consumer correction — Batch 2 complete

`CANONICAL_CONSUMER_CORRECTION_BATCH_2_COMPLETE`

The established journal-bound consumer, effect-start journal and direct adapter
now require native-aware claim interpretation. Ten effect-side descriptor readers
and inactive credential eligibility run under the native outer lock and refuse
native state. Generic email execution and direct transport fail closed without a
binding root. The existing email command exposes read-only consumer inspection;
normal sending remains refused.

See `docs/executable-atomic-transition-canonical-consumer-integration-correction-implementation-v1.md`
for all D01–D11, A01–A05 and E01–E10 dispositions and preserved archival meanings.
The original descriptor remains BOUND_INACTIVE, historical v3 NOT_IMPLEMENTED,
and UNKNOWN_REPLAY_PROHIBITED unchanged. No live transition or effect was run.

Focused regression passed 30 tests / 166 assertions. The corrected full PHPUnit
run passed 1994 tests / 45969 assertions. The old mechanical email smoke now
asserts the intended generic-bypass refusal rather than a fabricated receipt.

Continue the already authorized Batch 3: real Kernel/container and Console
Application proof, corruption/interruption/expiry/revocation, identity substitution,
separate-process contention and no-effect assertions. Terminal refusal remains
until the separately sequenced Batch 4 audit from clean merged Batch 3 main.
