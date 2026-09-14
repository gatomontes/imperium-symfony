# PPC5 — permanent-seat model authorization and O4 correspondence

Status: **PROPOSED_FOR_OWNER_APPROVAL**. This proposal selects the missing source and competence boundary for R1. It is not evidence that the current runtime already has that competence. The owner's “proceed. Imperium via solitaria est” authorizes preparation; approval of the newly written clauses below is a separate concrete decision. [Campaign](next-campaign-provider-model-correspondence.md), [exact proposal hash and approval status](handoffs/provider-model-correspondence-approval.json).

Baseline: main `a0c3042d08574da3c54a0b0e2d4ffc87ea47b3a1`, tree `31cd602f2aa3df058b8b9f09fc13eee12ebb8b7a`. R2 is accepted through PPC3/PPC4. Countdown: **4–6 remaining**, no credit for this preparation.

## Source findings and selected decision

| Source inspected | Established fact | Consequence |
| --- | --- | --- |
| [PPC2 originals matrix](provider-profile-designation-matrix.md) | Native mission/delegate seals lack authenticated permanent O4 binding/configuration/generation correspondence | Do not repeat the audit or call a synthetic model declaration an original |
| [MissionAuthorizationDerivationService](../src/Imperium/Runtime/Curia/MissionAuthorizationDerivationService.php) | Derives numbered model-specification lines and one-use Conscription sealing authority from an approved mission dossier | Preserve that mission scope; it is not a permanent-seat authorization producer |
| [ProfileModelBindingSealingService](../src/Imperium/Runtime/Conscription/ProfileModelBindingSealingService.php) | Produces an immutable non-current Profile revision carrying authorization ID, source line, model string and configuration | Its consumed sealing authority is not an O4 binding identity/generation or designation |
| [FormationModelBoundProfileContract](../src/Imperium/Runtime/Citadel/Formation/FormationModelBoundProfileContract.php) | Checks the full generic model-bound Profile shape and digest | Does not authenticate issuance of the independent model authorization |
| [FormationSignatures](../src/Imperium/Runtime/Citadel/Formation/FormationSignatures.php) | Authenticates exact effect/object, existing enrolled trust, time and revocation | Cryptographic ability to verify a new effect does not establish that effect's competence |
| [Formation runtime jurisdiction](../contracts/citadel-formation-runtime.md) and [Profile contract](../contracts/profile-artifact.md) | Exact examined-Profile approval is distinct from model/resource choice, designation and appointment | Preserve the existing artifact-class approval jurisdiction; no general SuperAdmin Profile approval |
| [AuthorityStore](../src/Imperium/Runtime/Onboarding/AuthorityAdmission/AuthorityStore.php), [Admission](../src/Imperium/Runtime/Onboarding/AuthorityAdmission/Admission.php), [Rules](../src/Imperium/Runtime/Onboarding/AuthorityAdmission/Rules.php) | O4 H originals have closed envelopes and schema/id/digest references; ordinary admitted source wrappers use `imperium.bootstrap-source/v1` | Preserve the existing source envelope and admission rules; admission is not the missing native authorization |
| [Policy](../src/Imperium/Runtime/Onboarding/AuthorityAdmission/Policy.php), [AssignmentRule](../src/Imperium/Runtime/Onboarding/Assignment/AssignmentRule.php) | Candidate provider/model/configuration and permitted tuples are structurally exact | Caller-supplied tuples, PASS rows and policy admission cannot supply native binding provenance |
| [PersistentSettings](../src/Imperium/Runtime/Onboarding/Assignment/PersistentSettings.php) | `profile_ref` refers to an O4 mapping that retains the full Formation Profile; `profile_generation` is appointed-holder generation | Do not equate Profile version, mapping identity, binding generation, designation counter or application generation |

**Selected proposal:** establish a separate, narrowly scoped native Formation model-preparation authorization for the two permanent Seats. It uses the existing owner trust, but a new explicit purpose and a competent current Conscription sealing delegation. It joins exact immutable native originals to O4 references before a strict model-bound Profile ingress may treat the model specification as authorized. A separately signed model-preparation act is required even when the same key signed an O4 policy or Profile approval.

## A. Exact competence provision

Approve a versioned, bounded Formation provision assigning the already authenticated Formation owner the operation **AUTHORIZE_FORMATION_MODEL_PREPARATION**, solely for an `officer` Profile targeting `courtyard.courtthane` or `clavium.locksmith`, in the exact same parent instance and Formation identity, with Laboratorium stewardship. This is an explicit new permanent-seat preparation purpose; it is not inferred from mission dossier approval, O4 admission, artifact approval or key possession.

The act permits one exact Conscription model-binding seal from one exact source Profile and explicitly named model/configuration/binding originals. It grants no Profile approval, designation, appointment, provider access, budget expenditure, settings application or execution. It neither chooses the final O4 pair nor changes existing selection rules. Different authorized candidate preparations may remain non-current until the independent later decisions occur.

Implement the provision in a new versioned normative contract and explicit native producer/verifier. Preserve the generic Profile contract, existing Formation approval jurisdiction and historical mission/delegate/tool-provider routes. The old routes remain outside positive R1 proof for the permanent pair. Do not add a general owner `APPROVE_MODEL` operation or broaden the O2/O4 effect list, standing Augur effects, provider universe, fee policy or FRESH route.

## B. Original identities and acyclic provenance

Create closed versioned native originals for specification/binding identity, authorization and seal. Retain complete content and the original signed envelopes in the Formation journal. Every reference specifies its schema, identity and digest with an explicit checked conversion between native and O4 digest conventions. No schema-free hash or bare ID establishes correspondence.

The specification must bind the exact provider, `model_id`, `model_version`, explicit `provider_model_version` spelling, configuration content/reference, constraints, fallbacks and `access_assertion_required: true`. Do not guess by splitting a model string or matching a display name. Preserve the accepted O4 configuration and selected-policy bytes; literals used by that policy are not newly verified claims about an external provider.

The native binding original must authenticate the exact O4 `binding_ref`, `configuration_ref` and immutable `binding_generation`. Load and compare the complete O4 originals, including envelope metadata and content, rather than copying the caller's admitted tuple. Use the existing `imperium.bootstrap-source/v1` wrapper where admission requires it; a typed native original can bind that wrapper without widening Admission's accepted schemas. Configuration contents must retain the existing `request-configuration` kind and exact shape. An admitted wrapper with a fresh digest but altered metadata is a different reference and requires new explicit authorization.

Binding generation belongs to a declared native binding lineage. Initial generation 1 requires a verified vacant lineage; a successor names the exact predecessor and increments by one under the owner fence, with finite integer/history bounds and no reuse after revocation. This counter describes immutable binding provenance, not current designation or an incumbent registry. The owner act pins the producer-established generation. No Profile version, holder generation, event counter or caller-supplied integer may substitute for it.

Use an acyclic dependency order: immutable O4 content/reference and native specification/binding identity first; a numbered specification line derived from those exact inputs next; a separately signed owner authorization referencing them and the exact source Profile next; then the signed Conscription seal retaining the resulting complete Profile bytes. Reserve authorization identity independently before constructing the final Profile. Do not make an authorization hash depend on a final Profile that itself embeds that authorization hash, or require a binding original to reference an authorization that already hashes the binding. Pre-authorization specification records are factual candidates and confer no authority.

The authorization's closed terms must bind: exact instance/Formation/target/steward, source Profile identity/version/digest, binding/configuration references and generation, complete specification and numbered source-line identity/digest, exact sealer delegation, expected journal head, finite validity bounds, nonce/correlation/reason and one-use purpose. The sealed Profile retains the existing generic `model_binding` shape: its authorization ID and source line must resolve uniquely to this actual native original. Its new immutable version, `SUPERSEDES` lineage and full content digest are mechanically derived from the authorized source; no approved artifact is modified in place.

## C. Current sealer and one-use publication

Introduce **DELEGATE_FORMATION_MODEL_SEALING** as a distinct owner-signed purpose for the actual current `conscription.recruiter` incarnation. Scope it to exact parent, Formation, target, binding identity/generation, key and finite lifetime. Existing `QUALIFIED_MANIFESTATION` or generic personnel-evidence delegations do not acquire sealing competence retroactively. No incoming key enrolls itself.

Require a domain-separated signed Conscription sealing act over the exact authorization, delegation, source Profile, specification/binding/configuration originals and computed output Profile. The deterministic producer checks those bytes and fresh competent authority; a callback's claim that it is Conscription is insufficient. The later Laboratorium derivation evidence and all examination/approval/qualification acts remain separate.

Use the accepted R2 live same-root Formation frame across decision reads, current sealer validation, authorization consumption and seal publication. Do not reacquire Formation inside the frame or enter it from a lower lock. Reserved generic storage must use the approved owner-aware methods if used at all; AtomicTransition and the accepted storage successor remain unchanged. No second native incumbent registry.

Add an explicitly initialized, optional versioned Formation state extension for these originals and one-use consumption records. Define its closed keys and finite bounds, initialize at an exact signed expected head, preserve the historical outer frame/onboarding schemas, and refuse affected in-flight/ambiguous initialization. Absence means unavailable, not implicit authorization. One existing journal publication must bind consumption and seal together; no partial record may certify an authorized Profile.

Exact retained request replay returns only the original historical result. Changed bytes under a reused nonce or consumed authority conflict. Competing seals have at most one accepted result. Current verification must freshly check owner trust, authorization/delegation revocation and expiry, exact native sealer tenure and all originals; retained consumption is historical fact, not continuing permission. Existing signed decision-revocation machinery may revoke authorization/delegation nonces without rewriting seals. New current checks refuse after that revocation; historical receipts remain readable. Process interruption must leave either the complete committed fact or no accepted consumption/seal, with bounded recovery and no duplicate authority.

## D. Strict correspondence consumer and lifecycle separation

Provide a fixed internal owner-aware correspondence verifier consumed by an actual strict model-bound Profile ingestion/validation path. It must join the complete native specification → owner authorization → Conscription seal → resulting Profile chain to exact O4 binding/configuration originals and generation. Supply the full held owner frame; do not issue a detachable currentness certificate or let requests select a weaker verifier.

Preserve PPC2's historical model-bound evidence schema and its deliberately limited structural meaning. If the strict path adds fields or stronger meaning, version that specialization explicitly. Old synthetic evidence must never silently acquire an authorized-model disposition; future R3 consumers must require the new strict seam, with no fallback to old shape validation. An unused helper or a new test-only truth source does not complete R1.

Exercise the produced Profile through its own actual Laboratorium evidence, four Senate findings, reconciliation, exact competent approval and Conscription qualification. Changed model/configuration bytes require a new full Profile and its own chain. Preserve separate appointment semantics. A seal alone does not make a Profile approved, current, installed or usable.

Do not promote `MissingAssignmentEvidence`, wire a production settings verifier, implement designation events or claim actual settings use in this batch. R3 owns those outcomes. The O4 `profile_ref` mapping and appointed-holder `profile_generation` remain separate from the native artifact identity and binding generation. R1 delivers the competent originals and strict reusable consumer prerequisite; it does not grant the complete applied pair or dispatch permission.

## E. Required proof

Use actual disposable native institutions and the new competent production authorization/sealing entry points. Generated fixture keys may sign those actual bounded acts; they may not replace an unimplemented issuer with fabricated JSON. External model capability, account access, pricing and cognition evidence remain synthetic or unavailable and must be labelled separately.

| Case | Required result |
| --- | --- |
| Both permanent Seats | Authentic specification/binding originals → separately signed owner act → current delegated Conscription seal → strict Profile ingress and exact full lifecycle chain |
| Wrong competence | Mission/delegate/tool seal, Profile approval, O4 policy/admission/PASS, owner signature for another effect, or qualification-only delegation refuses as model-preparation authority |
| Original substitution | Wrong schema, ID, digest, owner, parent, Seat, source line, model/version spelling, configuration bytes, H-wrapper metadata, source Profile or binding generation refuses before effects |
| Generation and dependency order | Vacant initial lineage and exact successor; stale predecessor, duplicate/reused identity, overflow and circular/forward references refuse |
| Continuing checks | New process after authorization/delegation revocation, expiry, trust change or native Recruiter replacement refuses current authorization while preserving historical facts |
| One-use and races | Two processes at one head, changed replay bytes, authorization revocation versus sealing/current verification, and sealer tenure change versus publication; actual R2 lock and bounded barriers |
| Interruption | Before/after publication, no half consumption/seal or duplicate result; native partial packages remain refusing |
| Compatibility | Old Profile and mission/delegate routes retain meaning; PPC3/PPC4 tenure/storage tests, prior source pins and default deployment wiring remain intact |
| Complete gate | Committed executable candidate; unchanged eight partitions/source/exact-case coverage and all 11 guards; fresh receiving hosted CI before integration |

Retain every failed diagnostic, native exit, warning, skip, platform limitation and before/after source identity. Do not substitute prior PPC4 CI for this campaign's validation. Any independently incompatible frozen contract requires its own exact source decision; no pin rewrite or unrelated compatibility weakening is included in A–F.

## F. Acceptance and countdown

Use **NATIVE_O4_MODEL_CORRESPONDENCE_IMPLEMENTED_OFFLINE** only when both targets have competent originals and the real strict consumer proof above. Otherwise report **PARTIAL_NATIVE_O4_MODEL_CORRESPONDENCE** with the exact missing producer, correspondence or synchronization path. Architectural permission to add the new purpose is not its implementation.

Countdown **4–6 → 4–6** during preparation/submission. Receiving acceptance of complete R1, with no new scope, permits exactly one decrement to **3–5**. R2 remains accepted and earns no second credit. R3/R4/R5/R6 remain open or deferred. A new blocker changes the estimate only through an explicit explained revision.

All five operational flags remain false. `DEFER_ENROLLMENT` and the empty actual retry allowlist remain. No real credentials/providers, installed private state, actual enrollment/appointments, deployment, commissioning, activation or execution. No new agents are requested. Return the full public source/evidence packet specified in the campaign; stop at local commits for receiving review and fresh hosted validation.
