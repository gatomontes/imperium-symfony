# PPC3 — native tenure synchronization

Status: **SELECTED_FOR_LOCAL_OFFLINE_IMPLEMENTATION**. Countdown: **5–7 batches to the first live Courtyard interview**, targeting outcome **R2**. See the [countdown and counting rule](provider-first-interview-countdown.md). Preparing this campaign earns no countdown credit.

The owner requested “proceed. remember countdown” after PPC2's reviewed component integration. The previously approved [PPC2 clauses A–F](provider-profile-designation-amendment.md), especially D, supply this synchronization scope. The [approval record](handoffs/provider-profile-designation-approval.md) and [receiving review](reviews/provider-profile-designation-review.md) retain the authority and limits. No new model-approval jurisdiction is selected.

Baseline main: `cd3a46ba45d0b89208c68e39225a0f8e08ca6de6`, tree `451140919d570d196384b5e0802ac076c67ba579`. Start from the published `codex/provider-native-tenure-preparation` branch and record its actual commit/tree. [PowerShell setup and full prompt](handoffs/provider-native-tenure-ready.md).

## Why this batch comes next

PPC2 proved model-bound Profile envelope/chain mechanics and empty designation initialization. Its native-to-O4 binding correspondence still needs a competent source decision: mission/delegate sealing evidence does not automatically authorize the permanent O4 pair. Repeating that missing-source audit or inventing a generic owner approval effect would not close it.

Native tenure synchronization is a separately implementable prerequisite already described in approved clause D. This batch must deliver an actual supported writer/reader boundary and executable race/deadlock/reconstruction proof. Begin with the [PPC2 writer inventory](provider-profile-designation-writers.md), not a new broad discovery campaign.

## Required executable result

Make current institutional observations used by the Formation model-bound Profile path consume a coherent owner boundary shared with every supported writer that can change those institutions' effective tenure. The supported set includes the nine `FormationInstitution::SEATS` roles, the relevant native registry, and independent Courtthane/Locksmith appointments. The exact same root identity and Formation owner must be used throughout.

Use the existing `citadel-formation` fence as the outer publication/observation boundary for supported paths. Resolve the existing NativeBoundary → bootstrap → Formation inversion at actual ownership entry points. Preserve the pinned AtomicTransition implementation. Introduce explicit internal methods/frame context where needed so an already-held owner frame can be consumed without reacquiring the non-reentrant Formation lock. A request flag, arbitrary callback parameter or public constructor value cannot assert that a lock is held.

Document one global acquisition order, cover every indirect call into it, and prove it with production entry points in separate processes. NativeBoundary's current same-process held-root map is not proof that Formation is held. A global wrapper that causes nested Formation acquisition, or a local wrapper that leaves an inverse path, does not satisfy this campaign.

| Required owner path | Required behavior/proof |
| --- | --- |
| OperatorRootPersonnelInstallationService → OperatorRootOwnership::native | Preserve its **existing indirect Formation fence**. Do not repeat the preparation's corrected claim that the direct installer is unfenced. Cover direct and V0 callers. |
| RequiredV0PersonnelInstallationService, V0ActivationService, OperatorRootOperationalizationService, StateStore, MasterMason callers | Resolve indirect bootstrap/native/Formation ordering without granting bootstrap authority, changing historical state meaning or reopening installation. |
| ConstableSeatBindingService and GuildhallSeatBindingService | Fence actual publication and any decision reads whose freshness publication depends on, including alternate active-set effects. Do not cover only the final rename. |
| NativeTrust, NativeProtocol, NativeJournal | Serialize enrollment, roster adoption/succession/retirement, Garrison revision and revocations with observations that previously saw source files alone. |
| FormationInstitution and new model-bound candidate validation | Read exact intact native originals within the shared boundary; a native registry or unsupported successor cannot leave old raw occupancy accepted as current. |
| FormationPersonnel appointments | Preserve independent exact owner acts, holder generation and revocation semantics within the common frame. |

The inventory is a lower bound. Search indirect callers and generic storage entry points for alternate publishers affecting this set. Name every supported path and justify exclusions from actual target/storage semantics. Do not omit a competing writer because its output schema is currently unsupported; it can still invalidate the active set or authority meaning.

Prefer an explicit refusal for native successor/registry forms whose competent currentness adapter is not supported by the approved scope. Refusal must be ordered with the native change and remain observable in a new process; merely ignoring a native-authority directory is unacceptable. Do not add general institutional succession or reinterpret source-file incumbents as the registry's successors.

## Current observation and historical fact

Provide a fixed internal owner-aware institutional observation/verifier seam that the real model-bound candidate path consumes. Detached current reads must not mint a currentness certificate usable after the fence is released. State exactly where authority is observed and what publication/use it fences. An immutable receipt is historical evidence, not continuing tenure or current settings permission.

Compare actual actor incarnation, parent instance, Seat, occupancy generation, binding and installation originals, delegation and revocation at the relevant boundary. Preserve the old historical publication/receipt recognition path; later changes can invalidate new use without rewriting a previously completed fact. Keep all signed originals and all existing corruption/ambiguity refusals.

No designation or AssignmentEvidence implementation is required in this batch: their prerequisites remain open. The new synchronized seam must be exercised through a real current Formation consumer and be usable later by an owner-frame assignment verifier. An unused helper or an always-refusing replacement with no positive supported producer case is not R2 completion.

## Interruption and compatibility limits

Native installation writes multiple files. A common lock alone does not provide crash atomicity. Prove that interrupted/partial/ambiguous state cannot authenticate a current actor for the supported consumer scope. If a coherent package publication/recovery boundary is needed, implement it only within existing exact producer authority and the approved scope; otherwise keep the affected case refusing and state the remaining blocker. Do not fabricate completion markers for old unknown state or claim individual file renames make a whole package atomic.

No changes to pinned AtomicTransition, historical source pins or historical artifact bytes to get tests green. Preserve old serialized envelopes and source evidence. If a pinned implementation truly prevents the approved synchronization, provide the exact conflict and smallest proposed versioned boundary without silently rewriting the pin. A partial result does not close R2.

Keep accepted O0–O5, PPC0, PPC1 constitution and PPC2 component behavior. In particular, do not bypass FRESH vacancy or `B225_FRESH_ROOT_OWNED`, treat onboarding v4 as permission to publish native institutions, reopen a root window or clone installed incumbents into a synthetic parent. The combined FRESH establishment outcome R4 remains separately open.

## Acceptance proof

| Case | Required observation |
| --- | --- |
| Supported positive current path | Actual disposable native producer → authentic scoped personnel originals → model-bound candidate/current consumer, with the coherent owner boundary; synthetic model specification remains labelled unverified authorization. |
| Native change before current use | Actual enrollment/registry change, alternate binding, revocation or supported tenure change makes old authority refuse; no stale-file fallback. |
| Competing update/current operation | Controlled process barriers prove both orderings at actual owner entry points. Use bounded process timeouts and assert native exits; elapsed sleeping alone is not ordering evidence. |
| Indirect lock order | Direct installer and V0/StateStore/native routes, nested supported owner operations, exceptions, wrong-root context and lock release after process interruption; no deadlock or forged held-frame bypass. |
| Retained evidence | New process reconstructs from intact public originals, sees intervening revocation/native change, and distinguishes current refusal from historical completed-fact recognition. |
| Partial native writes | Interrupt around installation/placement/publication boundaries; missing or ambiguous lineage refuses, exact recoverable completed facts do not create duplicate authority. |
| Compatibility | Original Formation and native tests, independent target appointments, service wiring, FRESH fences, source pins and existing history preserve their accepted semantics. |
| Complete gate | Committed executable candidate, unchanged full eight-partition PHPUnit/source/exact-case coverage gate and all 11 guards; fresh receiving hosted CI before runtime integration. |

Use disposable roots, generated in-memory fixture keys and real native producer methods. No actual installation/enrollment, private installed state, provider/account calls, credentials, deployment, activation or mission execution is authorized. Do not parallelize agents unless the local owner separately requests that workflow; ordinary process concurrency tests are required.

## Deliverables and countdown decision

Return the implementation report, exact supported writer/caller/lock-order map, positive/refusal compatibility matrix, test map and future public-input runbook. Preserve complete source/evidence archives, manifests, tested/final commits and trees, exact diffs, bounded bundle and checksum verification in `imperium-ppc3-public-review.zip` plus its separate `.sha256` file. Retain all diagnostic failures and actual platform limits. Stop at local commits for receiving review and complete hosted CI.

Use `NATIVE_TENURE_SYNCHRONIZATION_IMPLEMENTED_OFFLINE` only when the whole declared supported set and its interfering paths pass the proof above. Otherwise report `PARTIAL_NATIVE_TENURE_SYNCHRONIZATION`, identify exact remaining paths and leave R2 open. Neither outcome claims current AssignmentEvidence, model-authorization correspondence, FRESH establishment or live readiness.

Report **5–7 remaining before review**. Propose **4–6 only if R2 is fully accepted**, with no new scope discovered; the receiving review applies the decrement and records its exact integration evidence. Preserve all five false operational flags, `DEFER_ENROLLMENT` and the empty actual retry allowlist.
