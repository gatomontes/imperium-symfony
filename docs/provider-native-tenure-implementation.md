# PPC3 implementation and compatibility matrix

**PARTIAL_NATIVE_TENURE_SYNCHRONIZATION**. PPC3 implements a shared typed cooperating-writer boundary and a model-bound current Formation consumer. It does not implement designation events, independent O4 binding correspondence, AssignmentEvidence, combined FRESH establishment or live readiness. The [writer map](provider-native-tenure-writers.md) identifies the supported paths and lock order.

## Current versus historical evidence

The live owner capability is issued only inside acquisition of the existing Formation lock and expires on callback exit. It is not a field in a signed request, stored frame, constructor snapshot or receipt. `candidateInOwner` additionally compares the supplied state to the actual held journal frame, preventing an unrelated array from supplying current state. The two current holder methods reconstruct that frame themselves. Native observations happen before model-bound assembly/appointment publication or current holder return, while the outer owner is still held.

Model-bound candidate checks retain the immutable Profile digest, all original scoped institutional signatures, original delegation decisions and revocations, actor incarnation/parent/Seat/generation/binding/installation, exact findings, examination, approval and qualification. The owner-aware current holder methods consume the independent appointment too. They confer neither model-specification approval nor settings permission. Returned assemblies/holders describe an observation completed inside the boundary; later use must repeat current verification under its own owner. No detached model-bound candidate result is accepted as current authority.

Exact completed appointment replay retains its existing path before new candidate validation. Registry enrollment does not erase the original appointment or increment the holder generation; it makes new current use refuse. Existing signature/time/revocation requirements on historical appointment recognition remain unchanged. NativeProtocol's stronger retained accepted-at replay behavior also remains unchanged. This batch does not broaden either historical replay contract.

## Multi-file publication

The root installer still writes the existing installation and placement originals without changing their schemas or historical bytes. For a genuinely new package it first durably writes an exact package intent in `operator-root/packages/<package digest>.json.pending`, naming every expected installation digest. Only after every placement returns successfully does it rename that intent to the completion record. Every current actor verification requires the completed record and verifies **all** named installation and placement originals, including other members of that package.

This is fail-closed completion custody, not a claim of multi-file atomicity. Interruption with only an intent, between installation and placement, after a member placement, or after every placement but before completion refuses current authority. A pending package refuses replay; no automatic recovery or rollback authority is invented. Old unknown packages without completion evidence remain historically readable but cannot acquire a completion certificate through exact replay. A completed package may replay existing exact originals without creating a second installation. Missing or corrupted completion/originals refuses. Operating-system/power-loss persistence beyond the existing file APIs is not newly claimed.

## Matrix

| Case | Result / preservation |
| --- | --- |
| Actual new artifact-backed package containing the nine institutional Seats | Positive current actor validation after exact complete-package verification |
| Authentic model-bound Profile, complete institutional evidence and separate Courtthane or Locksmith appointment | Positive owner-bound current holder in a fresh process; assembly remains component-only |
| Registry enrollment ordered before candidate use, raw occupancy unchanged | `CMF123_GOVERNED_SUCCESSOR_ADAPTER_REQUIRED`; no fallback |
| Candidate observation ordered first | Completes under Formation; later registry change makes the next process refuse; earlier fact remains retained |
| Any bootstrap state, including unknown/partial forms and old T03/T04 successors | Synchronized refusal; no interpretation as artifact-backed root tenure |
| Alternate Constable active set or Guildhall cohort | Existing intact/ambiguity checks plus owner-bound cohort refusal; bootstrap prerequisite itself is also a refusing current form |
| Incomplete package or missing old package-completion evidence | `PPC304_COMPLETE_NATIVE_PACKAGE_REQUIRED` or existing missing-institution refusal; no migrated completion |
| V0 placeholders, root operationalization and MasterMason | Existing installation/replay/closed-window behavior; ordered ownership, no schema migration |
| Wrong root, escaped or serialized live capability | Refusal; exception and process interruption release the real lock |
| Direct generic mutable publication retires an authentic source occupant | **Open synchronization path:** generic guard can acquire Formation independently; completed retirement makes new-process current use refuse, but racing writes are not synchronized |
| Existing Formation and native history | Original source records and schemas retained; completed fact recognition is separate from continuing tenure |
| Old Profile ingress | Still rejects model-bound versioned envelope |
| Model authorization declaration in fixtures | `SYNTHETIC_UNVERIFIED_DECLARATION`, explicitly separate from authentic institutional signatures |
| FRESH, `B225_FRESH_ROOT_OWNED`, onboarding v1–v4 and source pins | Unchanged; no installed lifecycle or combined FRESH establishment proven |
| AssignmentEvidence and account/access/base/cognition production ports | Continue refusing; service wiring and approved provider/model/fee policy unchanged |

All five operational flags remain false. `DEFER_ENROLLMENT` remains; actual retry allowlist is empty. Disposable fixture enrollment and appointments are tests, not operational enrollment or appointments.

Whole-supported-set completion is withheld because direct generic tenure storage remains unfenced under a retained source pin. See the exact conflict and proposed versioned boundary in the writer map. No deployment set including those paths is accepted.
