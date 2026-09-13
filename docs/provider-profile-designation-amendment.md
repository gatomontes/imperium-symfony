# PPC2 proposal: governed Profile designation and current binding evidence

Status: **PROPOSED_NOT_APPROVED**. This document is a reviewable amendment proposal, not a changed contract, institutional act, or implementation acceptance. Publishing it does not approve it. The requested decision is approval of clauses A–F below for one offline implementation campaign, subject to the stated refusal boundaries and complete acceptance gate.

Decision metadata: [PPC2-AF decision record](provider-profile-designation-decision.json).

Baseline: main `88e0b5d50b07443468785750de6107c1fb2dc0dc`, tree `edd17a3b5c5133fadecd1c293d70cf9c3409cd13`. PPC1's constitution subset is integrated through [PR #812](https://github.com/gatomontes/imperium-symfony/pull/812); its assignment port remains incomplete. Read the [receiving review](reviews/provider-institutional-evidence-review.md) and [source matrix](provider-institutional-evidence-matrix.md).

## Problem and exact decision

The Profile lifecycle requires the destination steward to designate an approved immutable version current/active, with explicit supersession. Formation authenticates derivation, examination, approval, qualification and appointment, but does not issue that designation. Its current officer Profile envelope also excludes model binding. O4 needs current exact Profile/model/configuration facts at both application and actual settings use; an approved candidate or sealed non-current revision cannot supply them.

Approve a versioned Formation specialization for these facts, using Laboratorium's existing stewardship, the existing institutional signing/delegation boundary, and the existing Formation journal. This permits offline implementation of new designation/revocation ingress and associated schema and synchronization changes. It confers no new artifact-approval jurisdiction, appointment power, provider authority or live permission.

The unresolved synchronization boundary is part of the implementation obligation. Approval of this proposal is not evidence that synchronization already exists. If the bounded supported native producer paths cannot meet clause D without widening authority or changing pinned historical contracts, return the exact remaining incompatibility and a partial result.

## Source basis

| Source | Verified fact and implication |
| --- | --- |
| [Profile contract](../contracts/profile-artifact.md), lifecycle and installability | Destination steward designates after competent approval. One current version per steward/target; successor requires `SUPERSEDES` and predecessor supersession attestation. Profile lifecycle does not itself grant Seat occupancy. |
| [Formation contract](../contracts/citadel-formation-runtime.md), institutional provenance | Names Laboratorium stewardship and the existing examined-Profile approval path. Its lifecycle projections explicitly stop before `current_active`. |
| [FormationProfileContract](../src/Imperium/Runtime/Citadel/Formation/FormationProfileContract.php) | Requires steward `{kind: office, id: laboratorium}`. Its closed current envelope excludes `model_binding`; silently adding it to that accepted shape is incompatible. |
| [FormationInstitution](../src/Imperium/Runtime/Citadel/Formation/FormationInstitution.php) | Maps Laboratorium to `laboratorium.alchemist`; resolves exact existing operator-root installation/occupancy. Governed successor schemas currently fail `CMF123_GOVERNED_SUCCESSOR_ADAPTER_REQUIRED`. |
| [FormationPersonnel](../src/Imperium/Runtime/Citadel/Formation/FormationPersonnel.php) | Current delegation is scoped to actual institutional actor. The closed role/kind mapping permits Laboratorium `DERIVED_PROFILE` only; existing candidate lifecycle ends `approved`. |
| [ProfileModelBindingSealingService](../src/Imperium/Runtime/Conscription/ProfileModelBindingSealingService.php) | Creates an authorization-derived immutable revision, explicitly non-current and pending access/activation preparation. A sealing receipt is not current designation. |
| [FormationJournal](../src/Imperium/Runtime/Citadel/Formation/FormationJournal.php) | `changeAtHead` supplies one predecessor and one publication point; read/use checks can consume the caller's frame without a nested lock. |
| [OperatorRootPersonnelInstallationService](../src/Imperium/Runtime/Bootstrap/OperatorRootPersonnelInstallationService.php) | Persists installation and placement files separately; inspected writer does not acquire the Formation fence. A journal-held read of those files alone cannot serialize changes to them. This is a concrete writer finding, not a complete writer inventory. |
| [ApplicationOwner](../src/Imperium/Runtime/Onboarding/Assignment/ApplicationOwner.php), [PersistentSettings](../src/Imperium/Runtime/Onboarding/Assignment/PersistentSettings.php) | Both invoke AssignmentEvidence. Current Formation mapping additionally equates O4 `profile_generation` with actual holder generation. Preserve that meaning. |

The generic [Profile envelope schema](../contracts/profile-artifact.schema.json) already permits an exact authorization-derived `model_binding`. This proposal changes the narrower Formation specialization and adds designation custody; it does not reinterpret a generic schema allowance as a completed institutional act.

## A. Competence and scope

1. Supported targets are exactly `courtyard.courtthane` and `clavium.locksmith`, artifact class `officer`, steward `laboratorium`. The author is the actual current `laboratorium.alchemist` incarnation in the same parent instance, authenticated by existing native tenure evidence and a narrowly scoped delegation. No incoming public key enrolls itself.
2. Existing Garrison admission, Guildhall suitability, four Senate findings, Lord Speaker reconciliation, exact competent Profile approval and Conscription qualification remain separate required evidence. Preserve the existing Formation approval jurisdiction; this amendment does not create general Imperator approval power.
3. Add a distinct delegation purpose and signed ingress for `DESIGNATE_FORMATION_PROFILE` and `REVOKE_FORMATION_PROFILE_DESIGNATION`. An existing `DELEGATE_PERSONNEL_EVIDENCE`/`DERIVED_PROFILE` authorization does not gain either purpose retroactively. Reuse existing owner trust and institutional key authentication, with explicit purpose, target, actor, parent, bounds and revocation; no new root of trust.
4. Designation authenticates the exact approved Profile and its authorized model specification. It cannot approve that specification, choose a provider, grant account access, appoint an occupant or apply settings. O4's exact separately authorized complete assignment pair still controls settings publication.
5. Standing Augur effects remain unsupported. S/B migration, general institutional succession, automatic installation and broad personnel selection remain outside PPC2.

## B. Versioned originals and state

Use a separately versioned Formation evidence envelope/validator for model-bound officer Profiles. Retain the generic immutable Profile `contract_version: 1.0.0` and its existing model-binding shape. The old Formation ingress and its accepted/rejected bytes retain their old meaning. A changed model or configuration creates a new immutable Profile version/digest and undergoes its own exact examination, approval and qualification; prior approval cannot be copied onto it. A sidecar hash must not simulate an approved model-bound Profile.

Add an optional, explicitly initialized top-level Formation state member `profile_designations`, schema `imperium.formation-profile-designations/v1`. Its closed shape contains `schema`, `initialization`, `delegations`, `events`, and `current`. It lives in the same journal frame as `onboarding`, personnel, appointments and revocations. Do not change the historical outer frame schema or relabel onboarding v1–v4 as new state. Absence means unavailable, never implicit currentness.

Initialization is an explicit offline owner operation at an exact expected journal head, retaining the predecessor reference and an empty designation state. It grants no actor, imports no current designation, and creates no native incumbent. Historical frames remain byte-identical. Refuse duplicate/conflicting initialization and initialization with in-flight affected operations. Older readers that cannot validate the extension must fail closed for its new consumer path; they may retain historical reads.

Retain original signed events and immutable artifacts. `current` is a verified index derived from events, not independent authority. Key it by exact parent instance, Formation identity, steward and target. Retain tombstones; never recycle a designation identity or generation after expiry/revocation. Unknown fields, malformed references, missing originals, conflicting versions or a corrupted index refuse before publication. Define finite limits and overflow refusal; never prune authority history to admit new events.

## C. Signed transitions, generations and replay

The new envelope is `{payload, signature}` with Ed25519 over the existing canonical JSON encoding of the complete payload. Domain separation uses schema `imperium.formation-profile-designation-act/v1` and exact effect. The payload has the following closed fields; implementations may introduce no implicit authority defaults.

| Field | Required binding |
| --- | --- |
| `schema`, `effect` | Exact new schema; one of the two effects in A.3 |
| `instance_id`, `citadel_id`, `steward`, `target` | Exact owner and supported steward/Seat |
| `delegation_ref`, `actor` | Original purpose-limited delegation and exact native actor including occupancy generation, binding and installation digests |
| `expected_head` | Existing journal `{generation, digest}` immediately preceding the act |
| `predecessor_ref` | Exact latest designation event for this key, or null only when no event has ever existed |
| `profile_ref`, `profile_evidence_ref` | Exact immutable Profile identity/version/content digest and original Formation evidence reference |
| `approval_ref`, `examination_ref`, `qualification_ref` | Exact original chain for this Profile version; no older-version substitution |
| `model_binding` | Exact authorized provider/model/version, configuration and binding originals; retain their schema-qualified references and the authorization/source-line correspondence |
| `designation_generation` | First event is 1; each subsequent accepted event for this key is predecessor + 1; checked integer bound |
| `binding_generation` | Retained O4 binding identity generation, authenticated against its original; never inferred from the event counter |
| `not_before`, `issued_at`, `expires_at` | Integer UTC seconds; not_before <= issued_at <= observed time < expires_at, within delegation and all required continuing authority bounds |
| `nonce`, `correlation_id`, `reason` | Nonce is 48 lowercase hex; nonempty bounded correlation and reason; replay scoped to actor/delegation and owner |

For designation, validate the complete chain and exact model binding before publishing the new current index and lifecycle attestations. Initial designation links to the exact approved attestation. A successor has immutable `lineage.supersedes` naming the exact prior Profile version/digest; atomically append predecessor supersession and successor current-active attestations under the same signed act and journal commit. Both retain actor, time, correlation, reason, prior attestation and original act reference. An aborted transition publishes neither.

Revocation identifies the exact current designation with the same identity, originals and predecessor fields. It records a terminal revocation attestation, advances event generation and clears current use atomically. It does not select a replacement. Current steward tenure and exact revocation competence remain required. Expiry is enforced at the observed clock on every current read without requiring a read-side write; a later recorded expiry is historical mechanics, never an unsigned grant. A successor after revocation/expiry still names the last event and exact prior Profile lineage, preserves the terminal attestation, and does not reclassify a revoked/expired predecessor as superseded.

An identical retained signed request returns its historical receipt before stale-head/time checks, with no new generation or authority. Changed bytes under a used nonce conflict. Historical recognition never makes an expired, revoked or superseded designation usable. Two competing acts signed at one head can have only one successful publication; even an unrelated head change requires newly signed terms. No delayed act can overwrite a newer event.

Keep three counters distinct: designation-event generation, immutable binding generation, and existing O4 settings application generation. O4 `profile_generation` continues to mean the exact current appointed holder generation required by `PersistentSettings::verifyFormation`. The new designation counter is separate evidence. A Profile successor cannot silently rebind an occupant: old settings become unusable until an independently authorized exact appointment/mapping and O4 application match the new Profile. A binding-only change likewise cannot mutate an approved Profile or existing receipt.

## D. Native tenure and publication synchronization

Select the existing `citadel-formation` fence as the shared outer fence for the supported native tenure producers and the new current designation consumers. Do not create a second authoritative incumbent registry. Do not modify the pinned `AtomicTransition` implementation to manufacture reentrancy.

Before implementation acceptance, inventory every supported production entry point that can publish, replace or invalidate any native installation/occupancy used by the new verifier, including alternate paths affecting the same Seat. Include shared storage writers reached indirectly. The initial inspected root-installation writer above is a required starting point, not an exhaustive list. Add the common fence at supported producer ownership boundaries, before reading decision inputs and through publication. Any inner locks follow one documented global order: Formation first, then native local locks. Pass the already-held frame into internal verifiers; never reacquire Formation from a callback holding it. Prove affected historical entry points do not invert this order.

While this fence is held, reconstruct native tenure from exact intact native originals and compare it to the signed actor, parent, delegation and current target holder. At assignment publication and actual settings use, repeat the required current-tenure checks with the same owner frame. Replacing an incumbent before the fence invalidates the old authority; replacement ordered afterward cannot rewrite the completed historical fact, but prevents later use. No constructor snapshot, imported H record, digest-only receipt or `synchronized: true` flag substitutes for this ordering.

Native multi-file publication must fail closed after interruption: no missing installation, partial placement or ambiguous active set may authenticate an actor. Do not claim multi-file atomicity merely because individual files rename atomically. Any new recovery protocol must preserve original decision authority and remain within this bounded writer scope. If that requires a broader native installation/succession contract amendment, retain refusal and report it rather than silently adding it here.

This is trusted local custody with cooperating supported runtime writers, not proof against administrator replacement of all stores or an old executable writing around the fence. Unknown/unintegrated writer paths and unsupported successor lineage do not qualify for a positive supported-deployment claim. No live deployment is approved by this proposal.

The FRESH Augur vacancy restriction remains unchanged. Pre-existing native institutions under the same root cannot be fabricated around `B225_FRESH_ROOT_OWNED`. Prove supported stage ordering using actual producers if claiming combined end-to-end readiness; otherwise report the unsatisfied institutional establishment dependency separately. A disposable-root native personnel test is useful component evidence, not proof that installed FRESH onboarding can establish those institutions.

## E. Assignment consumer integration

Introduce a fixed internal owner-frame specialization of `AssignmentEvidence`, analogous in purpose to PPC1's owner-aware constitution interface. It receives the existing AuthorityStore/Formation owner and already-held full owner frame. Detached calls to the native current verifier refuse. Preserve the original interface for existing tests/consumers; request data cannot choose a verifier or turn off current checks.

Use the same implementation in `ApplicationOwner::prepareApplication` and `PersistentSettings::current`, including revalidation, role resolution and `verifyFormation`. Load all required designation, model-binding and institutional originals from verified custody. Match exact permitted O4 tuples, Profile predicates, native current holder/mapping and all distinct generations. Check the frame's designation history/index and fresh authority at the actual use boundary. A role-specific lookup must not hide invalidity of the complete applied pair when whole-set validity is required by the accepted application contract.

Publication remains the existing atomic complete Courtthane/Locksmith pair with existing expected predecessor, exact current authorization and one-use effect accounting. Revocation/succession/expiry may make historical settings unavailable for current use; they do not erase receipts, refund exposure, retry cognition or silently choose another tuple. Settings verification creates no dispatch permission. The existing session, lease, reservation, start and response-admission fences remain independently required.

Dormant Composition may select the native verifier only after its implemented path passes the acceptance gate. Default deployment/service wiring remains unchanged. Account/access, base eligibility and cognition evidence remain separate missing production ports.

## F. Offline proof and finish line

| Proof | Required observation |
| --- | --- |
| Real positive path | Actual native institutional producers, scoped signatures, complete findings/approval/qualification, model-bound Profile, designation, separate appointment, complete O4 application and actual settings consumer; synthetic external dependencies identified individually |
| Competence | Validly signed derivation-only delegation, foreign/stale Alchemist, wrong target/class/parent, substituted key and generic owner act all refuse with unchanged journal |
| Exact originals | Missing/mutated approval, examination, qualification, model authorization, Profile, configuration, binding or mapping refuses; valid hash alone never passes |
| Lifecycle | Initial designation, atomic live-predecessor supersession, successor after revocation/expiry, duplicate identity, missing SUPERSEDES, out-of-order generation and replay/conflicting nonce |
| Current use | New process after designation, revocation, expiry, delegation revocation, incumbent replacement and target appointment replacement; old receipts readable, stale current settings refused |
| Competition | Two designations at one head; designation versus revocation; incumbent replacement versus designation/application/use; application versus Profile change; actual producer paths share the fence |
| Interruption | Before and after journal rename, native multi-file interruption, exact replay/recovery, no half supersession, no second application or lost revocation |
| Compatibility | Old Formation/Profile envelopes and fixtures keep meaning; missing new state refuses native path; no onboarding v1–v4 reinterpretation; historical source pins and default wiring unchanged |
| Complete gate | Committed candidate, unchanged full eight-partition PHPUnit coverage/source gate and all 11 guards; retain real failures, platform skips, times, counts and source identities |

Report separate outcomes for designation mechanics, native tenure synchronization, assignment/application/use integration and combined FRESH lifecycle feasibility. Only claim `ASSIGNMENT_EVIDENCE_IMPLEMENTED_OFFLINE` when the first three are proven through actual supported owners; explicitly retain any combined lifecycle gap. If synchronization or necessary competent originals remain unavailable, report partial implementation and keep the production verifier refusing that case. Do not mark PPC1 two-port success from a contract proposal or a synthetic parent shortcut.

The implementation packet follows the [conditional handoff](handoffs/provider-profile-designation-ready.md): source/evidence archives, full manifests, exact tested/final commits and trees, unchanged complete gate, exact diffs, bounded bundle and SHA-256 checks. PPC1's earlier green CI is historical baseline evidence only.

All five operational flags remain false. `DEFER_ENROLLMENT` and the empty actual retry allowlist remain. Approval of A–F authorizes offline contract/implementation work only; live custody, enrollment, credentials/provider calls, deployment, activation and execution remain separately deferred.
