# Native Inspection Snapshot Consistency separate-process proof v1

`NATIVE_INSPECTION_SNAPSHOT_CONSISTENCY_BATCH_3_COMPLETE`

The adversarial proof uses disposable `native-transition-*` roots and sibling PHP
processes. Test rendezvous files are outside the declared semantic manifest.
They are proof instrumentation, not production state or request-controlled input.

Proved cases:

- an inspector paused after manifest A while a canonical transition publishes
  its journal, registered legacy retirement and final commit discards the mixed
  attempt and returns the stable post-publication `COMMITTED_CURRENT` result;
- signed revocation crossing an inspection produces stable
  `COMMITTED_NOT_CURRENT`, never current authority;
- two separately published semantic changes exhaust exactly two attempts and
  yield existing `INCOMPLETE` / `UNKNOWN_REPLAY_PROHIBITED` refusal;
- process death after journal publication remains byte-stable `INCOMPLETE` over
  two fresh inspector processes;
- the same committed fixture is `COMMITTED_CURRENT` at its accepted `at` and
  `COMMITTED_NOT_CURRENT` after expiry;
- terminating a paused inspector leaves every semantic file byte-identical and
  creates no inspection lock.

These tests prove cooperative local publication crossing and bounded refusal.
They do not claim distributed-filesystem behavior, hostile ABA resistance or
freshness after return. No credential, provider, callback or external I/O is
present.
