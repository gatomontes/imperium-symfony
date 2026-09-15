# PPC7 establishment protocol v1 — implementation provision

Status: local implementation in progress under exact owner-approved PPC7 A–F; no receiving acceptance. The unchanged proposal and approval govern. R4 only; R3, R5 and R6 remain open. Countdown 3–5, decrement zero.

## Identity and competence

The selected route performs unchanged real FRESH founding first. Both existing independently enrolled Operator and Formation-owner trusts authenticate separate domain-separated envelopes over identical complete establishment terms. Terms retain exact policy, constitution, founding command/completion/holder, canonical root, instance/Operator/Formation identities, both trust fingerprints, exact nine-member native package, expected journal head, validity, nonce, correlation and reason. No incoming trust enrollment and no O2/O4 allowlist expansion.

## Transaction and publication

Explicit owner-authorized initialization creates one optional strict versioned extension. EMPTY permits one joint reservation only; PENDING irrevocably retains the exact authorized package and original signatures before any native write; COMPLETE retains the original completion. No pruning, replacement package, new nonce reset or second incumbent registry. Existing native installation/occupancy originals remain the incumbent source.

The second transition holds Formation then native ownership, revalidates both current authorities and the original genuine founding holder, validates the complete deterministic file set, writes its exact intent and every placement, verifies all native files and publishes package completion, then commits journal completion. These are distinct publication points. A complete native package without journal completion refuses current authority. Exception and process termination must be tested at each boundary.

Authority and the founding holder are checked again after native publication, at the completion decision. If either expires during that phase, the journal remains a valid PENDING reservation with the exact native files retained. The protocol never writes a completion timestamp outside the establishment interval. Focused regressions advance the controlled clock after package completion to expire either the establishment grant or the founding holder.

## New-route native adaptation

A versioned closed wrapper retains each complete native installation or occupancy original and exact establishment reference. The wrapper exposes no legacy seat, status, role, binding authority or artifact fields. Existing legacy schema readers cannot treat a partially published wrapper as an old incumbent. The explicit Formation adapter may unwrap only under a live same-root owner after validating the journal completion and every native original in the package. Generic installation and historical package recovery remain unchanged. Detached new-route witness/current calls must refuse unless they obtain their own proper Formation observation; callbacks already owning Formation must pass that owner.

## Forward recovery and bounds

Recovery recognizes only deterministic, intact files belonging to the already reserved package. Unknown files, changed bytes, missing authority or invalid/expired/revoked trust and holder refuse with pending evidence retained. It never deletes, rolls back or reseeds. Completed replay returns the original historical receipt without current authority or repair of deleted files. Native completed authority derives from actual intact currentness owners; establishment authorization is consumed history.

The implementation enforces one package, exactly nine artifact-backed OFFICER members, 4 MiB ingress terms, 8 MiB extension, 100,000 visited JSON values, nesting at most 32, native files at most 2 MiB each, 16 MiB aggregate native bytes, at most 256 native directory entries and scan depth eight. Establishment lifetime is at most 3,600 seconds, within both independently enrolled trusts; epoch seconds pass the existing bounded time validator. Nonces are exactly 48 lower-case hexadecimal characters. Correlation/reason/identity text is nonempty and at most 1,024 bytes. Expected heads use the existing finite native integer/digest contract. Unknown keys refuse. These limits do not enlarge the existing Formation journal's accepted boundary.

### Closed state and reference map

The optional `fresh_institutions` object has exactly `schema`, `initialization`, `reservation`, `completion`, and `preparations`. Schema is `imperium.fresh-institutional-establishment/v1`. Initialization contains exact terms and its separate Formation decision. Reservation and completion initially are null; their null/non-null combinations define EMPTY, PENDING, COMPLETE. Completion without reservation refuses. `preparations` is initially empty and permits only the two explicitly signed downstream initialization receipts described below.

Reservation (`imperium.fresh-institutional-reservation/v1`) contains schema/id/terms/operator/formation/record_digest. Its identity hashes all three signed originals. Completion (`imperium.fresh-institutional-completion/v1`) contains schema/id/reservation_ref/native_package/completed_at/expected_head/record_digest. References have schema/id/digest; their digest is the native lower-case SHA-256 hex, not an O4 `sha256:` value. Native references are compared exactly and are never passed as O4 references. Founding H originals retain their original O4 references, record digests and schemas. Mapping sources are constructed with the actual H owner and its ID/digest validator.

Native wrappers have exactly schema/id/reservation_ref/original/record_digest, using `imperium.fresh-native-installation/v1` or `imperium.fresh-native-occupancy/v1`. Their `original` is the complete existing native installation or occupancy record. Package completion (`imperium.fresh-native-package/v1`) has schema/id/reservation_ref/files/record_digest; files maps the eighteen exact native paths to their wrapper digests. Placement paths and IDs are deterministic derivatives of the exact package using the existing native record constructor. The intent is the package completion path plus `.pending`; placement temporaries are their exact final path plus `.pending`. A temporary and final placement together refuse. No historical package recovery method accepts these versions.

### Separate downstream initialization

Real FRESH founding retains a completed access claim. The original PPC5/PPC6 initializers conservatively reject any retained onboarding claim, including settled history. They remain byte-identical. The additive `FreshInstitutionalPreparation` adapter first verifies this route's entire completed package, then invokes the unchanged AuthorityStore/LedgerState validator over the actual onboarding history. It requires empty source fences, no outer affected sessions/claims/reservations, and every retained claim settled with all five custody checkpoints ending in RESPONSE_RETAINED. It neither deletes claims nor creates a fake empty ledger.

Each empty downstream extension still requires its own exact existing `INITIALIZE_FORMATION_MODEL_PREPARATION` or `INITIALIZE_FORMATION_PROFILE_DESIGNATIONS` Formation-owner decision at the current head. Establishment signatures cannot substitute. The receipt records that separate initialization, the checked custody digest and predecessor head under `preparations`; later reads authenticate its original decision and match the exact downstream initialization. This applies the earlier approved refusal of affected in-flight/ambiguous work without rewriting the conservative initializers. Completed custody remains byte-identical. The new package alone grants no preparation, designation or appointment.

### Future public-input runbook

This is a source-review/offline API provision, absent from service wiring and user commands. On a disposable canonical root, use the existing public-trust enrollment, signed O4 admission, native ConstitutionSource, base selection/mapping and FRESH CommandLedger owners first. Enroll the independent Formation-owner public trust through its existing deployment owner. Prepare initialization terms from that same root's actual committed holder/head; the owner signs their exact canonical bytes. Initialize, prepare the complete package terms, obtain both independently scoped signatures, reserve, then complete using only its retained exact reference. Never copy a holder/installation from another root or feed synthetic institutional currentness into the protocol.

Following interruption, inspect through the same trusted owner and retry `complete` with the exact retained reservation reference. Expired/revoked/unknown/corrupt pending cases stay refusing; no repair/reset route is provided. After successful completion, resolve through `FormationInstitution::actorInOwner` in the consuming operation. Standalone `actor` takes its own Formation observation. Subsequent changes can invalidate that observation; a returned array is not an owner capability. Produce strict PPC5 and PPC6 chains and independent appointments through their own owners. The mapping helper produces a factual H source only; O4 admission, initial application/replacement and settings delivery remain R3 work.

### Executable requirement map

| Requirement | Candidate proof |
| --- | --- |
| Real supported order, nine fresh-process actors, both permanent Seat chains | FreshInstitutionalEstablishmentTest |
| Joint signatures, exact terms, malformed packages, currentness, generic/v4/legacy refusals, no repair | FreshEstablishmentRefusalTest |
| Two reservations, revocation, native enrollment, generic installer and operationalization in both lock orders | FreshEstablishmentOrderingTest |
| Before/after both journal renames, intent and package-completion publication | FreshEstablishmentCrashPublicationTest |
| Before/after all eighteen native placements | FreshEstablishmentCrashPlacementTest |
| Before/after all eighteen native temporary writes | FreshEstablishmentCrashWriteTest |
| Original initializer refusals, separate scoped adapter, retained completed custody and affected/corrupt refusals | FreshEstablishmentPreparationTest |

Data providers enumerate eighty actual process-death/recovery points. Worker inputs carry only public originals and signed envelopes. Each child event log records UTC, PID, boundary and native exit; externally supplied synthetic base facts remain exact pinned public inputs, while the constructor fixes the real native constitution/holder owners. The final report distinguishes executed passing coverage, failed development attempts and remaining matrix gaps; this table itself grants no acceptance.

Every child input also retains its actual pre-process public Formation frame so that independent per-root keys and enrollment originals remain available after fixture cleanup. The worker never uses that exported observation as current authority; it constructs the actual store and rereads the live journal. Refusal evidence exports retain exact malformed inputs and native refusal strings. The refusal harness explicitly rejects a successful operation and does not catch PHPUnit's own failure exception as an expected domain refusal.

## Conservative source inventory

The pre-edit inventory retains exact predecessor bytes/digests for 234 candidate readers/writers and line matches for owner, placement, package and seal operations. It distinguishes current authority adapters, raw legacy occupancy readers, native/bootstrap succession, generic installers and seals, historical publication observations, canonical source artifact readers and unrelated mission/operational occupancy.

Specific seams: OperatorRootOwnership native/nativeInOwner; OperatorRootPersonnelInstallationService install/installInOwner/withOwner and private deterministic record construction; RequiredV0PersonnelInstallationService and V0ActivationService; OperatorRootOperationalizationService; NativeInstallationPackage; NativeBoundary and NativeProtocol; FormationInstitution actor/actorInOwner/witness; FormationPersonnel authoritySource/delegate/record/candidate; MasterMason ChildCuriaFormationService publication and CitadelPublicInstitutionsCommand historical observation. Raw Garrison/Guildhall/Laboratorium/Conscription/Senate consumers and native successor original readers must refuse new wrappers; only the explicit new adapter supplies current authority.

This inventory is a candidate set, not a claim that all consumer proofs have passed. Exact classifications, executed tests and any remaining gaps will be retained in the final report and packet. Frozen predecessor inventories and source pins are not regenerated.
