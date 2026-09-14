# PPC3 receiving review — R2 remains open

Disposition: **TYPED_NATIVE_TENURE_COMPONENT_ACCEPTED_AND_INTEGRATED**. Whole-outcome disposition remains **PARTIAL_NATIVE_TENURE_SYNCHRONIZATION**. Countdown: **5–7 before review → 5–7 after this partial result; decrement 0**. This component cannot close R2 while generic storage can publish institutional state outside Formation.

## Packet and source identity

The supplied outer SHA-256 matches `b600abff2af43e7f861818202a5cf3bc227542707ed37e13d94c8c7c501ed426`. Verified all 13 packet payloads, exact archive membership, 3,547 source files and 856 evidence files; all sizes/SHA-256 hashes and archive modes; source Git blobs/modes; exact bundle prerequisite; all three trees and full-index binary diffs; documentation-only tested-to-final changes; and the standalone report's byte identity with the source report. The submitted countdown and generic/AtomicTransition primitive bytes match preparation exactly.

| Identity | Commit | Tree |
| --- | --- | --- |
| Published preparation | `eccd068322e06dd0449586d77703215faf18b12b` | `047aa833c54da20ca17be139e57e2de823903dc6` |
| Producer executable candidate | `6ecf1e4417453a35f9781b21fbc16632965febec` | `7cef4598673365b70e41a83af72fc4ba36851de8` |
| Producer final documentation | `45f6787122cecbc435ef29750adf6cf9749d681c` | `55d7f6028c6226aa9efb64de68499e7aa6ddf875` |
| Receiving PR #818 candidate | `23feb28e2e8010fdd826beee11b3df2d03fd15e5` | `55d7f6028c6226aa9efb64de68499e7aa6ddf875` |

## Component source review

No blocking source finding was identified for the bounded submitted component. `FormationOwnerFrame` can be issued only while holding the real existing Formation lock, validates same-root ownership, rejects serialization and becomes inactive on callback exit. The journal supplies that capability through explicit internal seams. NativeBoundary retains the actual held owner across nested same-root native operations and rejects cross-root native nesting before acquiring a second owner. V0 enters before bootstrap locking and passes the held owner to installation/operationalization.

The direct root installer already used Formation indirectly before this work; that earlier preparation mistake remains corrected. PPC3 orders the surrounding native/bootstrap owners and adds Guildhall's actual decision/publication boundary. The current model-bound ingress/candidate and owner-aware holder methods inspect original institutional evidence under the owner. Raw native-registry or bootstrap state and unsupported successor/cohort forms cause refusal rather than a stale-file fallback. Historical old-schema methods do not acquire a new currentness guarantee through this component.

New package custody writes a pending exact installation set and publishes completion only after placements succeed. Current actor validation checks every installation/placement named by the completed package. Tests retain four process-interruption boundaries, including all placements written before completion. Unknown old packages acquire no completion marker through replay. This is fail-closed completion verification, not multi-file atomicity, automatic recovery or power-loss durability proof.

Controlled process tests cover both registry-enrollment/current-consumer orders, including a nonblocking independent probe of the actual Formation lock. Fresh processes verify both target holders and refuse after native change. Exact appointment replay preserves its prior historical behavior without restoring current tenure. Targeted tests also exercise wrong/escaped/serialized ownership, exceptions, indirect V0/StateStore routes, interruption and the known generic-writer gap. Full compatibility tests are still required; these tests do not prove a synchronized set containing the open generic paths.

The public final fixtures contain 34 retained Formation frames for each target. Independent Python verification matched all frame digests/links and 32 original institutional Ed25519 signatures per target (64 signatures total). The fixture payloads contain only the number/key/string forms for which the checked canonical encoding matches the repository encoder. This is public fixture integrity, not current institutional authority or an authenticated model authorization. The model/source-line declaration remains explicitly synthetic and unverified.

## Concrete R2 blocker

`ImmutableRecordStore::put` and `MutableStateStore::compareAndSwap/compareAndSwapGuarded` still use their own generic locks and accept reserved institutional targets. Already-owned typed callers retain their outer fence; bare generic entry paths do not. Their writes can race current observations. The submitted test demonstrates a successful independent Formation lock acquisition from inside the generic mutable guard; completed retirement subsequently refuses, but that does not serialize the competing write itself.

The [snapshot reading ledger](../native-inspection-snapshot-consistency-reading-ledger-v1.json) pins ImmutableRecordStore's normalized SHA-256 to `51acaf101d0c14301cd2d29f19581473ff93fdc82179fa34225566282f97ae7a`. The [terminal ledger](../native-inspection-snapshot-consistency-terminal-reading-ledger-v1.json) requires unlisted later changes to fail closed. The retained diagnostic test rejected the attempted primitive change, and the producer restored the generic primitives. No source pin, test or guard was rewritten to obtain the final Windows suite result.

R2 needs an actual reviewed successor contract for generic storage's reserved institutional targets, followed by implementation and proof at the old generic entry points. It must route or refuse those targets before acquiring the primitive lock, respect an already-held live owner, cover both mutable APIs and immutable put, and preserve ordinary non-institutional targets. The precise target set and path aliases must be based on the existing validators and actual custody paths. An unused wrapper or a newly calculated ledger hash is insufficient. This review does not approve an unseen successor design or mark that work complete.

## Validation

The unchanged aggregate verifier was rerun successfully against the supplied final Windows evidence: **3,767 tests / 64,326 assertions / zero skips**, 614 files and every enumerated case exactly once. Source digest `ebbfeae4d1aef76f085077aa9c7bec74b04d5c9e1ac6f9c3bb282d0961b38230`; longest JUnit partition 1,799.737655 seconds. The actual Windows launcher interval for that partition exceeded 30 minutes; it does not prove the hosted job budget. Targeted producer evidence reports 22 tests / 623 assertions, including all 17 new cases and the unchanged five-test pin regression.

The Windows guard result remains 10 passes and one symlink privilege error (`WinError 1314`), zero guard skips. Four suite warnings and earlier interrupted/failing attempts remain retained as diagnostics. No mixed diagnostic run is represented as a final successful gate.

Reviewer-local PHP is unavailable. Correct Python guard discovery passed nine tests with two explicit PHP-dependent skips. No local PHPUnit execution is claimed. The [fresh hosted run](https://github.com/gatomontes/imperium-symfony/actions/runs/34831143654) on [PR #818](https://github.com/gatomontes/imperium-symfony/pull/818) passed: **3,767 tests / 64,310 assertions / four explicit skips**, all 614 files and every enumerated case exactly once, all eight workers and **11 guards passed with no guard skips**. Aggregate job `103938777628`; PHP 8.4.25 / PHPUnit 13.3.0. Longest partition 887.508029 seconds; workflow interval 2026-09-14T10:02:55Z → 10:18:16Z (15m21s). Source digest `d7fbeaf73289200ff829925bb144584af63050da626f2b622139e92eeb52aad3`. Platform assertion/skip differences remain explicit.

CI checkout `e7fb46ebc4cf085e23e97998818e1878f56974dc` was independently verified to have tree `55d7f6028c6226aa9efb64de68499e7aa6ddf875`. PR #818 merged as `fe65bd91efa3eb5cc5bc07a1ac48658f12e46edf`, with the same independently verified tree. [Machine integration record](../provider-native-tenure-reviewed-integration.json). This receiving review and countdown/status follow-up are documentation-only changes after the accepted runtime gate.

## Countdown and remaining scope

[Countdown](../provider-first-interview-countdown.md): previous **5–7**, accepted complete outcome **none**, revised **5–7**, change **0**. The generic-writer gap remains inside R2's existing scope; this partial submission does not justify a decrement. If further review shows additional batches beyond the working estimate, revise the estimate explicitly rather than claiming progress through renamed or partial batches.

Current designation/revocation/supersession, native-to-O4 binding correspondence, shared AssignmentEvidence and combined FRESH establishment remain open. Account/access, base eligibility and cognition remain separate missing production ports. All five operational flags remain false, `DEFER_ENROLLMENT` remains and the actual retry allowlist is empty. No current synchronized deployment set or live commissioning is accepted.
