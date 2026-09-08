# Citadel Recruiter currentness and Garrison authority interfaces — A0–A3

Disposition: **AUTHORITY_INTERFACES_SELECTED_LOCAL_IMPLEMENTATION_ONLY**.

## Objective and accepted starting point

Build the smallest executable, independently reviewable path for public evidence of the ordinary Recruiter and an explicit, legitimate Garrison authority revision. Deliver implemented preparation/inspection interfaces, a dormant producer/consumer path exercised in disposable fixtures, meaningful adverse proof and exact future owner commands. Run A0–A3 locally as one bounded campaign; do not stop after a general inventory.

[PR #771](https://github.com/gatomontes/imperium-symfony/pull/771) integrated accepted N0–N3 and its documentation closeout at `fb2916b0579ce6483f47bbde2fdaf6e9dd76dfa4`, tree `9e2f30eb52fe123371b1d75128a6f935a63f4aa2`, after GitHub CI run `34238541870` passed. The campaign-selection documentation merge becomes this run's entry commit; record it before editing. See [native-lineage acceptance](citadel-native-lineage-acceptance.md).

The exact Guildhall acceptance is resolved: `guildhall-acceptance-0a1361819c5739081088`, planning-only, structurally consistent and insufficient for formation authority. The 16-record follow-up retains the earlier 14 originals unchanged. Do not repeat its lookup, repair pending originals or reopen N0–N3. Raw public records remain external to Git. Their absence from a new chat does not block source implementation using explicitly synthetic fixtures; never claim an unperformed installation observation.

## Why these two interfaces

`App\Bootstrap\MasterMason` emits T04 succession output. `ConstableConscriptionService::ordinaryRecruiter()` and `GuildhallConscriptionService::ordinaryRecruiter()` derive their actor from private `StateStore` data. A qualification packet's embedded actor is not independent public proof of current incumbency. The standalone collector deliberately does not open that private state.

`ConstableSeatBindingService::bind()` now emits admission and custody powers, but the retained historical occupancy lacks them. The same binding identity cannot be replayed with changed bytes: persistence rejects conflicting content and an already occupied Seat. `SubordinatePersonaAdmissionIntakeService::currentConstable()` requires those powers and one exact occupancy. Adding a second ACTIVE file or choosing a maximum generation is not a legitimate transition.

`OperatorRootUpgradePlanningService` prepares a plan from an operator-root operationalization seal. That is neither proof the installed native lineage has that seal nor authority to migrate it. `FormationInstitution` accepts its existing operator-root format and refuses unsupported native lineage. Preserve that refusal until a complete, action-specific trust and provenance chain actually exists.

## Scope and execution boundary

Implement source, contracts, command/DI adapters, tests and documentation in a fresh campaign worktree. Run code only against explicit disposable fixture roots or already exported public bytes. Test-only identities, keys and authorities must be unmistakably synthetic and never presented as real installation evidence.

Do not read `E:\htdocs\imperium\var\imperium\bootstrap-state.json`, private journals, credentials or real keys. Do not run bootstrap, enroll trust, install code into `E:\htdocs\imperium`, bind/upgrade a real officer, invoke an acceptance act, call a provider or execute a mission. Do not change dependencies or an existing installation's vendor/configuration. Existing offline dependencies may be reused without modifying the source installation. Do not activate new DI bindings or production transports as a side effect of preparation.

A future native exporter may legitimately need to read its private backing source inside the owner's installation. Implement and prove that restricted interface using synthetic backing state now; document its private read/lock footprint and separate later authorization. Keep it separate from the public-only Python collector. Never silently widen the existing collector's scope. If a read acquires a lock or creates a lock file, disclose that write; do not describe it as having zero filesystem mutation.

## Required source reading

Read these documents before implementation:

- `docs/citadel-native-lineage-acceptance.md`, `docs/citadel-native-lineage-report.md`, `docs/citadel-native-lineage-authority-matrix.md`, `docs/citadel-native-lineage-runbook.md`.
- `docs/citadel-readiness-runbook.md`, `docs/citadel-readiness-matrix.md`, `docs/delegate-mission-flow.md` and `docs/next-lifecycle-delegate-mission-route.md`.
- `docs/citadel-formation-correction-acceptance.md`, `docs/citadel-mission-formation-decisions.md`, `docs/citadel-mission-formation-implementation.md`, `contracts/citadel-formation-runtime.md`.

Trace the following source and directly relevant tests; resolve related helpers by references rather than scanning runtime data:

- `src/Bootstrap/MasterMason.php`, `src/Bootstrap/StateStore.php`, `src/Bootstrap/CanonicalJson.php` and their transition/receipt tests.
- `src/Imperium/Runtime/Conscription/ConstableConscriptionService.php` and `GuildhallConscriptionService.php`.
- `src/Imperium/Runtime/Garrison/ConstableSeatBindingService.php`, `ConstableProvisioningService.php`, `ConstableConstructionCommissionService.php`, `SubordinatePersonaAdmissionIntakeService.php`, `SubordinatePersonaCanonicalAdmissionService.php`, `AdversarialReviewerBootstrapSeedAdmissionIntakeService.php`, `GarrisonInventoryInquiryService.php`, `GarrisonInventoryResponseService.php`.
- `src/Imperium/Runtime/Bootstrap/OperatorRootUpgradePlanningService.php`; inspect its issuer and seal prerequisites before considering reuse.
- `src/Imperium/Runtime/Citadel/Formation/FormationInstitution.php`, `FormationPersonnel.php`, `FormationPublicationEvidence.php`, `FormationPreparation.php`, plus their trust/journal references and existing CF01/CF02/IR01 tests.
- Existing immutable-record, atomic-transition, authority-consumption and currentness/revocation primitives reached by the chosen implementation. Reuse only where their real issuer and effect scope apply; an unrelated Root, ProtectedMission competence or synthetic resolver is not a substitute.

## A0 — decide and bind the interfaces

Produce a short source-backed decision record, then implement the chosen design in A1/A2. Separate these facts: historical succession occurred; the actor is current at a stated observation boundary; that actor has specific institutional powers; formation delegation exists. No one receipt proves all four by convention.

Identify the authoritative succession/currentness source and all writers relevant to its consistency. Specify source identity, instance, Seat, predecessor/successor, occupancy generation, consumed commission, qualification digest, observation revision/cutoff, supersession/revocation boundary and producer provenance. Decide which fields can safely be public and which claims cannot be established by the available source. No copying entire private state into a public envelope, and no digest of an unrelated source presented as proof of a redacted projection.

For Garrison, specify an immutable, attributable authority-revision act bound to the existing occupancy ID/digest, exact actor and generation, prior authority revision, requested two powers/scopes, effective/expiry conditions, issuer competence and one-time use/replay identity. Preserve occupancy generation unless an actual succession occurs; an authority revision is not automatically a new occupant. Bind a new revision's identity separately and preserve the original binding bytes.

Name the legitimate authority issuer and trust-resolution path from actual contracts. If issuance competence or a currentness source is absent, implement the typed preparation and refusing verification seam with a precise missing prerequisite. Do not invent owner decisions or grant power through command flags. This is a scoped implementation limitation, not a reason to substitute another inventory campaign.

## A1 — public Recruiter evidence producer and verifier

Implement a least-disclosure public output with its own schema/version and digest, source/projection relationship and producer provenance. Preserve distinctions among raw source identity, projection identity, authenticated statement and independently confirmed currentness. A self-computed hash, caller-supplied key or Git commit is not an authority root.

Implement preparation/inspection and a dormant native production interface as justified by A0. Bind the output to the exact T04 predecessor/successor, consumed commission, qualification and parent instance. Handle absent, failed, duplicate, contradictory, retired and superseded transitions explicitly. Do not select the last convenient record or largest generation as new doctrine. Currentness is a claim at a defined authoritative revision/time boundary, never indefinite validity from one old export.

Demonstrate the actual command and resolver path using disposable backing data and an injected clock only in tests. Where a coherent snapshot is required, use the relevant writer exclusion/revision protocol and prove its boundary. Offline inspection must state which claims it verifies and which need the authoritative current resolver. It must refuse tampering, unsupported versions, borrowed provenance and absent trust. Export itself grants no formation delegation, spawning, appointment or mission authority.

## A2 — explicit Garrison authority revision and consumer closure

Implement an unsigned request builder and inspection path plus the minimal dormant, authenticated revision producer/resolver justified by A0. Public signature assembly may consume detached public signatures; private signing remains the owner's separate operation. Synthetic signing in tests is permitted. No implementation default may certify an unknown real owner or populate missing decisions.

The exact requested extension is Persona-admission disposition and custody registration with their defined scopes. Do not automatically add reservation, Profile handoff, selection, execution or other powers merely because a newer producer emits them. Existing independent powers must be represented honestly, not silently revoked or broadened.

A successful fixture transition requires an authentic, effect-scoped authority chain, current predecessor and matching revision. Refuse expired/revoked/wrong-role authority, changed predecessor bytes, wrong instance/actor/generation and competing revisions. Authority validation and consumption, currentness checks and durable effect must share the relevant exclusion boundary. Use immutable evidence and exact idempotent recovery; a crash or uncertain outcome cannot invite a new act, a second effect or regained authority. Revocation must not erase authentic completed effects.

Demonstrate that a real admission consumer can resolve and enforce the resulting revised authority in a disposable fixture. Map every direct reader touched by the chosen representation, including custody/admission and inventory readers. Avoid a second ACTIVE occupancy that breaks existing uniqueness checks or leaves a stale raw-record bypass. Preserve original public bytes, historical receipts and prior authority attribution. Readers not safely supported must refuse the new representation explicitly, with a documented path to completion.

Do not widen `FormationInstitution` or `FormationPersonnel` simply because this prerequisite is now representable. Formation suitability, role-limited delegation and the other institutions remain separate. If a new formation witness format is actually necessary within this scope, both current validation and versioned retained-publication verification must be handled together and proven against CF02; otherwise leave the production formation adapters unchanged and state that limitation.

## A3 — adverse proof, exact owner runbook and review packet

Use meaningful tests through commands, DI, producers and consumers, not only DTO constructors. Cover forged/self-sealed public evidence, a trusted key with the wrong competence, wrong instance/actor/generation, missing or conflicting succession, stale currentness, changed source/projection, wrong source digest, old Garrison records without powers, overbroad authority, stale revision, exact replay and conflicting replay. Exercise interruption before/after durable effect, concurrent competing revision and revocation/currentness races when implementing a mutable transition. Demonstrate retention of the original occupancy and no second effect.

Seed synthetic private backing state and errors with distinctive secret sentinels. Check normal output, refusal output, exception handling, temporary artifacts and public serialization for disclosure. Keep raw private data out of the public serializer by allowlist. Preserve the existing public collector's path restrictions and tests. No test may contact a provider, read the actual installed private state or use real credentials. State the real limits of process/network isolation; do not describe PHP function restrictions as an OS sandbox.

Run focused tests, the 16 existing Python tests when affected or required for collector compatibility, and the complete offline PHP suite on the final executable commit. Record exact commands, UTC times, exits, commit/tree, test counts and warnings. The accepted supplied PHP baseline is 2,789 tests / 53,334 assertions with four documented linked-worktree warnings; retain comparison evidence without forcing the old assertion count or weakening assertions. Distinguish tests actually rerun from historical results only inspected. If dependencies are unavailable offline, complete source work and report the exact unrun gate; do not install packages without authorization. No code may change during the final run; subsequent executable changes require relevant gates again. Attribute Markdown-only closeout separately.

Deliver an owner runbook with exact implemented command names, flags, public input schemas, prerequisite records/issuer, read/write footprint, expected exits, resulting artifact identities and stop/recovery behavior. Clearly distinguish commands safe to run now from future private-state export, signing, authority revision and installation actions. Exercise future commands only against disposable fixtures here. No invented signing, enrollment or deployment command; unresolved interfaces must be named, not hidden behind “configure trust.”

Return the entire review folder: campaign report and decision record; source/consumer and changed-test maps; unsigned owner request shapes and exact runbook; test/proof output; tested and final source ZIPs with complete Git/blob/byte manifests; bounded history bundle with verified prerequisites; exact post-test diff; clean/dirty status and source identities; full folder SHA256SUMS plus separate archive SHA-256. Keep real public/private installation originals outside Git. Synthetic proof must be explicitly labelled. Preserve all prior packets, commits and review qualifications.

## Stop point and remaining path

Close as `AUTHORITY_INTERFACES_COMPLETE_LOCAL_PENDING_REVIEW` for supported dormant interfaces, or `AUTHORITY_INTERFACES_IMPLEMENTED_WITH_EXPLICIT_ISSUER_OR_CURRENTNESS_BLOCKER` when an authentic external prerequisite prevents a positive path. In either case deliver executable preparation/refusal behavior and evidence; do not claim the installation has changed.

Next boundaries after independent acceptance are separate owner authorization for installation/private export and genuine institutional acts, then the remaining formation-specific competence, custody/trust, other Seat witnesses, candidates/appointments and B1. No future implementation push/merge or live ceremony is automatic. This campaign selects neither provider/model nor policy changes to remote timeout/billing guarantees. Unknown outcomes retain exposure without retry/refund; default formation transport refuses.

Settled flow: Citadel receives; Castellan interviews; admitted understanding closes interview authority; separate explicit drafting approval permits a proposal; separate mission approval precedes legitimate child-Curia constitution/handoff; receiving assessment grants no execution authority. Preserve CF01/CF02/IR01, historical Delegate Steps 1–69 and the separately authorized source-review capability.

*Hoc est pretium solitudinis.*
