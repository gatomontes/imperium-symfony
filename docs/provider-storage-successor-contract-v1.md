# PPC4 — reserved institutional storage successor proposal v1

Status: **PROPOSED_FOR_OWNER_APPROVAL**. This is the concrete source-contract successor requested by the [PPC3 receiving review](reviews/provider-native-tenure-review.md). Preparation is authorized by the owner's latest “proceed”; approval of these newly written clauses is not inferred from that earlier instruction. Implementation acceptance remains a later receiving review.

Baseline: main `96d05d839320909d140d50337ccd16fe68dc1604`, tree `707b94f58beb80d5180790a5aa58843f99fe55c0`. PPC3's typed component is integrated in PR #818; R2 remains open. [Campaign](next-campaign-provider-storage-successor.md), [approval status and exact proposal hash](handoffs/provider-storage-successor-approval.json).

## A. Bounded successor and historical preservation

Authorize an offline successor to the two existing generic storage write APIs solely to close the reserved institutional target bypass. `ImmutableRecordStore::put`, `MutableStateStore::compareAndSwap` and `compareAndSwapGuarded` must themselves enforce the new boundary. A newly named wrapper that callers can bypass is insufficient. Keep class/service identities compatible where possible; public ordinary-target behavior remains compatible under D.

The original immutable source has normalized SHA-256 `51acaf101d0c14301cd2d29f19581473ff93fdc82179fa34225566282f97ae7a`. Preserve the original reading ledger and terminal reading ledger byte-for-byte, including their predecessor history. Preserve a non-executable copy of the exact predecessor source and its provenance in the successor evidence. Do not relabel a newly computed hash as an already reviewed implementation.

Use a new versioned successor record containing exact predecessor and candidate source hashes, paths, base/tested identities, this approved proposal hash and the actual conversational approval reference. Amend only the successor-recognition branch of `NativeInspectionSnapshotConsistencyPreparationBatch0Test::testLedgerPinsTheRequiredDocumentsAndCompleteTracingSources` to recognize this explicit bounded chain. Retain every existing source assertion and all five original test cases. Unlisted paths, wrong predecessors, duplicate entries, missing approval references and source mismatches must still fail. Negative tests must exercise these refusals. The record describes an authorized implementation candidate until receiving source review and full hosted CI accept its exact tree; it cannot confer that acceptance itself.

No wildcard, self-generated allow-any hash, original-ledger edit or blanket pin exemption is authorized. `AtomicTransition.php`, CI/coverage guards and all unrelated source contracts remain unchanged. If another independently enforced pin actually conflicts, retain the exact failing evidence and present that additional conflict; this clause does not silently supersede it.

## B. Exact reserved targets and alias handling

Classify the final path addressed by each write, including immutable directory plus ID plus `.json`, before acquiring its primitive lock, reading current state, invoking a guard, creating directories or publishing bytes. Validate the existing API grammar first. Classification is based on storage location, never caller-provided schema, Seat, payload, claimed purpose or a permission boolean.

Reserve these locations relative to the exact canonical project root:

| Location | Extent and reason |
| --- | --- |
| `var/imperium/bootstrap-state.json` | Bootstrap presence changes the current institutional adapter's result |
| `var/imperium/operator-root/` | Entire subtree: installations, operatives, packages and root ownership evidence |
| `var/imperium/native-authority/` | Entire subtree: even partial directory creation changes supported currentness |
| `var/imperium/citadel/formation/` | Entire subtree: trust, originals, journal, independent appointments and revocations |
| `var/imperium/offices/<office>/occupancy/` | Entire occupancy subtree for every Office, including unknown Office names; avoid payload- or known-Seat-only classification |

The inventory must verify these against actual source paths and include equivalent reachable paths that create a reserved ancestor or alter any original consumed by the R2 current reader. Unrelated Office custody, testimony, commission and terminal evidence is not reserved just because it is under `offices/`.

Account for both validators' accepted repeated separators, single-dot segments, trailing directory separators and case behavior on Windows and Linux. Compare effective destinations, not raw prefixes; similarly named sibling paths must stay ordinary. Canonicalize the root consistently with the real Formation owner. Resolve existing parent aliases or refuse ambiguous/reparse/symlink paths before effects when a reserved destination cannot be ruled out; include absent final targets and dangling links. An alias pointing into reserved custody must never become ordinary. State any deliberate refusal of previously accepted ambiguous aliases as an explicit compatibility change. Do not silently broaden the validators to admit traversal or absolute paths.

The supported threat model is cooperating current runtime binaries and fixed filesystem topology during an owned operation. Arbitrary PHP execution, administrator file replacement and concurrent external filesystem topology attacks do not become covered by a lexical classifier. If symlinks/junctions cannot be tested on a host, report the limitation and require suitable-host evidence before claiming that platform's alias proof.

## C. Owner-aware write methods and lock order

The old generic write entry points must **refuse reserved targets before their own lock or any guard/effect**. They must not automatically acquire Formation from a possibly lower-lock caller. Add explicit owner-aware counterparts for legitimate typed reserved writes, taking the actual live `FormationOwnerFrame` and validating exact root and scope before entering the original primitive operation. A frame is synchronization, not appointment, enrollment, model approval or schema authority.

Migrate every legitimate typed reserved call site to pass the owner already held at its actual decision/publication boundary. Fresh high-level operations acquire Formation at their owner entry. Preserve **Formation → native → bootstrap or target storage** ordering and the supported PPC3 nested-owner paths. Do not reacquire non-reentrant Formation, create a second incumbent registry, accept a serialized/stale/cross-root owner, or add a caller-controlled held-lock flag. An ordinary generic guard attempting a bare reserved write must refuse; internal callbacks must not ascend the order or reacquire an already-held primitive lock. Trace indirect callbacks as well as direct calls.

Retain the original primitive's conflict check, guard and commit under its existing target lock, with the live Formation owner continuously held for reserved operations. Establish preflight/owner assertions without running user guards early. A public owner-aware method cannot manufacture current authority; competent typed producers still perform all existing checks. Detached generic reads retain their historical meaning and must not be promoted into transferable currentness evidence.

## D. Ordinary storage and existing authority semantics

For unambiguous ordinary destinations, preserve validators, lock keys, canonical sealing, conflict/error behavior, exact idempotent immutable replay, CAS digest comparison, guard order and single invocation, temporary-file/rename behavior, and reads of historical originals. Preserve normal provider-transition/mission storage use and service construction. The only intended generic write behavior changes are the reserved-target owner requirement and explicitly documented ambiguous alias refusals.

Keep PPC3 positive current consumers, unsupported native/bootstrap successor refusals, package interruption refusal and historical completed-fact recognition. Preserve FRESH vacancy, `B225_FRESH_ROOT_OWNED`, old onboarding schema meaning, independent appointments and revocations. No installation-window reopening, model-approval jurisdiction, generic Profile redesign, designation implementation or `MissingAssignmentEvidence` promotion belongs here.

## E. Proof and acceptance

Prove all three old write entries refuse every reserved class and relevant alias before effects or primitive locking, including from a lower-lock guard. Prove explicit owner-aware positive publication using real typed producers and the real current Formation consumer, both process race orderings, wrong/expired owner refusal, exception/interruption lock release and fresh-process reconstruction. Retain PPC3's historical gap evidence and replace its current expected-open regression with an expected-closed test; never continue asserting the old bypass as desired behavior.

Use controlled process barriers, independent nonblocking lock observations, bounded timeouts and native exits. Test no-effect refusal, immutable replay/conflict/corruption, CAS conflicts/guard failure, ordinary-target concurrency and alias/sibling distinctions. A writer that succeeds only after the current observer releases its frame provides serialization evidence; elapsed sleeping alone does not. Partial publication must never authenticate an incomplete package. This is not a power-loss atomicity claim.

Commit the executable candidate before the unchanged full eight-partition PHPUnit/source/exact-case coverage gate and all 11 Python guards. Include the original five source-pin tests plus the bounded successor negative cases. Retain every failure, skip, warning, source identity and platform limitation. Fresh complete receiving hosted CI remains required before runtime integration; producer-local green results alone do not close R2.

## F. Scope, delivery and countdown

Use disposable roots and ephemeral generated fixture keys only. No actual enrollment/appointments, installed private state, provider/account access, deployment, activation or execution. All five operational flags stay false; `DEFER_ENROLLMENT`, the empty actual retry allowlist and the separate R1/R3/R4/R5/R6 gaps remain. Synthetic model declarations remain explicitly unverified authorization evidence.

Return the full public PPC4 packet and report described in the campaign. Count **5–7 → 5–7** during preparation and submission. Propose **4–6** only if receiving review accepts complete R2 closure, with exact source/integration evidence and no new scope. Partial storage improvements earn no decrement. Approval of A–F permits this bounded offline implementation and exact successor mechanism; it does not accept an unseen executable candidate or authorize a live operation.
