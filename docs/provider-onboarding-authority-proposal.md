# D2 authority integration proposal — O0 closure pass

> Current selected-route preparation and remaining dependencies are in the
> [provider closure report](handoffs/provider-onboarding-o0-provider-report.md).
> Earlier UNSELECTED/unapproved choice language below is historical where the
> selected-decision record supersedes it; it does not reopen settled choices.

> Current decision scope: [selected decisions](provider-onboarding-selected-decisions.md)
> records D2-A, DeepSeek/API key, FRESH and the approved retry ceiling. The
> [v1.3 retry amendment](../contracts/provider-onboarding-retries.md) adds separate
> predeclared attempts; unknown outcomes never permit replay, retry or refund.

**PROPOSED / UNAPPROVED / UNIMPLEMENTED.** Entry is reviewed candidate
`b8021914bbcfdcd990949ea332b308f5c042cbbe`, tree
`ec25528267f1b18bbe3b13356835d098e11cf63d`.
[Independent review](reviews/provider-onboarding-o0-independent-review.md) verifies
preparation and leaves O0 incomplete. This proposal supplies engineering choices;
the [decision sheet](provider-onboarding-owner-decisions.md) separates the actual
owner decisions from those choices. No authority is exercised or enlarged now.

The [F1/F2 v1.2 clarification](../contracts/provider-onboarding-continuation.md)
now owns progression identities, the complete effect registry and exact tagged
authority/claim variants. These are draft revisions, not new owner approvals.

## Recommended corridor and source constraints

Use a typed operator bootstrap corridor in the existing Citadel aggregate, with
Oracle as the assessment holder and infrastructure as credential/transport
custodian. It has no mission Curia, Courtthane mandate or new Office. It does not
translate existing signed records into another schema. Fresh constitutional
founding and existing-installation governed changes use distinct evidence types.

Existing source remains evidence, not an implementable authority bridge:

- [FormationSignatures::verify](../src/Imperium/Runtime/Citadel/Formation/FormationSignatures.php)
  authenticates `CITADEL_MISSION_FORMATION` only; its key cannot silently authorize
  bootstrap acts. [FormationJournal::change](../src/Imperium/Runtime/Citadel/Formation/FormationJournal.php)
  provides the existing aggregate/lock/commit boundary, not competence.
- [Founding assignment](../src/Imperium/Runtime/Imperator/FoundingAugurModelAssignmentService.php)
  checks a checksum-based development act. New onboarding must not manufacture it.
- [Augur activation](../src/Imperium/Runtime/Conscription/AugurResidentActivationService.php)
  expects a legacy standing approval with CURRENT_ACTIVE; [model-bound approval](../src/Imperium/Runtime/Imperator/ModelBoundProfileApprovalDecisionService.php)
  instead emits a development-local approval with activation and binding false.
  Neither record is an authenticated standing approval producer for this corridor.
- [Root installation](../src/Imperium/Runtime/Bootstrap/OperatorRootPersonnelInstallationService.php)
  and [operationalization](../src/Imperium/Runtime/Bootstrap/OperatorRootOperationalizationService.php)
  establish a separate founding path and permanent closure. Its v0 placeholders
  are not cognition-capable Profiles or proof of qualification.
- [Ledger](../src/Imperium/Runtime/Oracle/ModelIntelligenceLedgerService.php) and
  [evaluation opening](../src/Imperium/Runtime/Oracle/ModelEvaluationCaseOpeningService.php)
  have an incompatible runtime-binding interface. A new mapping retains both
  originals; it does not patch their hashes.

## Common proposed records and trust ingress

All names in this section are proposed schemas/classes, not existing symbols.
Use `Runtime/Onboarding/` for the new consumers listed below, with fixed
project-root DI. New records live in `state.onboarding` inside the existing
`var/imperium/citadel/formation` frame chain and `citadel-formation` lock. Its
maps are `trust, acts, revocations, policies, evidence, bindings, sequences,
commands, steps, slots, claims, applications, source_fences, attempt_outcomes,
group_inputs, assessment_views`. This is a typed subtree, not a second
claim journal or independent balance. Existing frame schema and old records stay
unchanged; strict new readers reject unsupported onboarding subtree versions.

Common record header H has exactly `schema, id, instance_id, citadel_id,
created_at, producer, sources, body, record_digest`. Producer is
`{service, source_commit}`; sources is a sorted unique list of refs. A ref is
`{schema,id,digest}` with `sha256:` plus 64 lowercase hex. IDs/versions use the
bounded identifier rules in the [contract](../contracts/provider-onboarding.md).
UTC integer Unix seconds only. Digest covers CanonicalJson of H without its own
digest. H is an integrity record; authority always resolves signed originals.

Proposed signed envelope: exactly `{payload,signature}`. Payload keys are
`schema, domain, instance_id, citadel_id, trust_fingerprint, issuer, effect,
object_digest, policy_ref, expected_head, issued_at, expires_at, nonce`.
Schema `imperium.operator-bootstrap-act/v1`; domain
`IMPERIUM_OPERATOR_BOOTSTRAP_V1`; issuer `{kind: operator, id}` or
`{kind: institution, seat, binding_ref, generation}`. Signature is detached
Ed25519 over canonical payload, 64 bytes base64; nonce is 48 lowercase hex.
Object digest binds a separately retained exact object, not prose or a path.
Policy ref is null only for policy authorization or an exact revocation act.
Expected head is the exact aggregate generation/digest. No field is a secret.

`BootstrapActVerifier` verifies separately installed public trust: exact public
key/fingerprint, instance/Citadel identity, competence, allowed effects, actor,
source generation, time and revocation. Trust record body fields:
`public_key, fingerprint, issuer, competence, effects, not_before, expires_at,
enrollment_receipt_ref`. Competence is `OPERATOR_BOOTSTRAP_POLICY` for the actual
human Operator, or an exact institution-specific effect set delegated by that
Operator with current incumbent evidence. No institution can authorize policy,
its own delegation, or an effect outside its real jurisdiction.

Public trust ingress is a separately authorized administrative ceremony: confirm
the human's competence and instance; independently confirm the raw 32-byte key's
SHA-256; register exact scope/validity under trusted storage; retain the public
receipt. Keys never come from incoming acts. Existing formation/native trust
neither automatically enrolls nor gains this competence. Reusing the same public
key, if later explicitly approved, still requires a separate exact competence
receipt. Key rotation is unsupported in v1; expiration/revocation stops fresh
effects. O0 registers no trust and keeps DEFER_ENROLLMENT.

Every effect rechecks actor, source, policy and revocation under the aggregate
lock. Revocation objects are exactly `{target_kind, target_id, expected_head}`,
where kind is `act|policy|issuer`; only competent Operator effect `REVOKE_BOOTSTRAP`
can append them. No deletion. An already committed effect remains historical;
revocation prevents later dependent effects. Audit copies of signed payloads are
never modified to advance their expected head.

Expected head is checked when admitting a new signed act. Later effects resolve
that retained admission and recheck its current permitted scope, not equality to
its historical head. Each new effect separately compares the caller's current
expected head; a conditional effect derives its exact source from the admitted
policy and consumes its own slot. Thus intervening valid frames do not require
re-signing unchanged policy, and a stale new mutation still refuses.

## Exact transition contracts

Each row specifies an H schema (prefix `imperium.` and suffix `/v1`), its complete
body fields, legitimate issuer/effect, future producer and consumer. Refs resolve
under trusted storage, not arbitrary caller-supplied arrays. Empty mandatory refs
or missing genuine evidence refuse. These fields extend neither existing schemas
nor their meanings.

| Transition / record | Exact body fields | Issuer and proposed producer → consumer |
| --- | --- | --- |
| Owner policy / `operator-bootstrap-policy` | `policy_version, installation_mode, provider, adapter_ref, credential_ref, candidate_bindings, targets, workload_ref, requirements_ref, evidence_policy, limits, allowed_effects, budget_ref, steps, effect_slots, assessment_groups, application_mode, expected_assignments, expires_at` | Operator `AUTHORIZE_BOOTSTRAP_POLICY` → `BootstrapPolicyAdmission` → every dependent consumer. Policy admits one bounded sequence, no standing spend. Candidate bindings have provider/model/version/configuration/adapter/mapping refs, not wildcards |
| Pre-Augur evidence / `bootstrap-public-evidence` | `kind, original_digest, locator, retrieved_at, effective_from, effective_until, provider, claims, limitations, authority` | Operator `ADMIT_BOOTSTRAP_EVIDENCE` over exact supplied public set → `BootstrapEvidenceAdmission` → `BaseSelection`. This admits source material for this bounded decision; it grants no Oracle canonical stewardship |
| Access authorization / `bootstrap-access-grant` | `provider, credential_ref, adapter_ref, destination, method, request_digest, allowed_observations, maximum, authority, expires_at` | Operator `AUTHORIZE_BOOTSTRAP_ACCESS` → `BootstrapAccessAuthorization` → shared claim/custody boundary. Pre-Augur infrastructure scope, not a Locksmith occupancy act |
| Access observation / `bootstrap-access-observation` | `grant_ref, claim_ref, provider, account_scope_digest, credential_version_ref, response_ref, observed_at, expires_at, predicates` | Trusted fixed adapter under consumed access grant → `BootstrapAccessRecorder` → `BaseSelection`. Predicates separately report authenticated request, model listing and exact model-invoke eligibility as true/false/unknown. Listing does not imply invocation permission |
| Pure selection / `bootstrap-base-proposal` | `policy_ref, evidence_refs, explanation_digest, selected_binding, evaluated_at` | `BaseSelection` computes existing proposed algorithm → founding/qualification consumers. No issuer act and no new authority |
| Fresh constitution / `bootstrap-founding-binding` | `policy_ref, base_ref, authority, installation_ref, occupancy_ref, profile_ref, window_fence_ref` | Operator `CONSTITUTE_FOUNDING_AUGUR` exercising existing pre-operational constitutional power → `FoundingAugurBridge` → `AugurBindingResolver`. Preserve OPERATOR_ROOT_INSTALLATION; never assert internally qualified/admitted founding provenance |
| Governed standing approval / `standing-augur-profile-approval` | `profile_ref, persona_custody_ref, suitability_ref, examination_refs, reconciliation_ref, model_binding_ref, authority, limitations` | Competent Imperator/Operator `APPROVE_STANDING_AUGUR_PROFILE` over complete genuine evidence → `StandingAugurProfileApproval` → Laboratorium designation and Recruiter qualification. Approval alone is not CURRENT_ACTIVE or occupancy |
| Profile designation / `standing-augur-profile-designation` | `approval_ref, profile_ref, predecessor_ref, generation, authority` | Current Laboratorium steward `DESIGNATE_STANDING_AUGUR_PROFILE` → `StandingProfileDesignation` → Recruiter. Exact derived Profile only; no model substitution |
| Qualified binding / `governed-augur-binding` | `approval_ref, designation_ref, qualification_ref, recruiter_authority, manifestation_ref, predecessor_binding_ref, generation, cutover_authority` | Current Recruiter `QUALIFY_BIND_AUGUR` after exact qualification; competent Operator `AUTHORIZE_AUGUR_CUTOVER` approves predecessor/next binding → `GovernedAugurBinding` → `AugurBindingResolver`. Neither actor grants provider use |
| Assessment authority / `bootstrap-assessment-commission` | `policy_ref, holder_ref, candidate_refs, rubric_ref, workload_ref, input_digest, allowed_result_schema, resource_ref, authority, expires_at` | Operator `AUTHORIZE_BOOTSTRAP_ASSESSMENT`, with actual current Augur → `BootstrapAssessmentCommission` → `OracleBootstrapAuthorityResolver` and shared resource/custody consumers. Scope only provider assessment; no external research tools |
| Assessment result / `bootstrap-assessment-result` | `commission_ref, claim_refs, response_refs, holder_ref, candidate_findings, proposed_assignments, limitations` | Current Augur as attributable holder, original validated responses → `BootstrapAssessmentAdmission` → `AssignmentApplication`. Each finding has candidate ref, predicate dispositions, source refs and rationale. Recommendation is not approval |
| Application receipt / `bootstrap-assignment-application` | `policy_ref, result_ref, authority, prior_assignments, next_assignments, consumed_effect_ids, commit_head` | Operator `APPLY_BOOTSTRAP_ASSIGNMENTS`, or exact conditional application effect authorized in initial policy → `AssignmentApplication` → `AssignedModelResolver`. One atomic whole-set commit; no appointment/Profile mutation/invocation granted |
| Catalogue mapping / `oracle-runtime-binding-map` | `snapshot_ref, provider, mappings, adapter_ref, authority, expires_at` | Operator `APPROVE_RUNTIME_BINDING_MAP` for infrastructure mapping → `CatalogueRuntimeBindingAdmission` → `OracleCandidateView`. Each mapping binds exact model ref, dispatch ID, configuration schema digest, supported limits and revision-pin limitation |

The authoritative F2 registry includes every effect, including REVOKE_BOOTSTRAP.
This matrix maps transition records; it does not independently define an effect
allowlist. All authority fields use F2 tagged variants. Recruiter and cutover
authority fields accept signed_act only. A policy lists only the permitted
subset needed for its route. Institution acts require separate role delegation
and incumbent checks. Records cannot gain competence from a producer class name.
The proposal does not call the development-local Profile decision a trusted
native issuer. A future adapter must resolve original genuine acts or refuse it.

## Fresh founding and existing installation fences

FRESH: verify an actual instance identity, protected custody and an unconsumed
pre-operational window. A missing seal file alone is insufficient. `FoundingWindowFence`
binds original root source identities, absence observation, proposed exact Augur
placement and policy. Under the same root's `citadel-formation` lock, reserve the
one-use founding slot, use the existing root producer, retain exact installation
receipt, then publish the typed binding. Root writer and operationalization writer
must participate in that lock protocol before this bridge is usable. Older
uncoordinated writers must be excluded by separately approved deployment custody;
otherwise refuse. O0 does not alter those writers.

A crash after reservation fences that exact placement. Recovery recognizes only
the matching original installation receipt; it cannot retry installation under a
new ID or infer success from ACTIVE. Closing the window concurrently prevents
fresh reservation; closure after a proven completed founding act does not erase
its provenance. A placeholder supplies no cognition: the fresh act must bind an
actual supplied cognitive Profile/model. Fresh root constitution does not create
the legacy standing-approval schema or fictional internal qualifications.

EXISTING: resolve exact current Augur lineage. If supported and unchanged, use it
only with a fresh bounded assessment grant. If missing or being replaced, require
the ordinary Garrison → Guildhall → Laboratorium → Senate reconciliation → exact
approval → steward designation → Recruiter qualification and competent cutover
chain. A vacant Seat does not reopen founding. Unsupported successor formats
refuse until an exact prospective adapter is independently reviewed. Changes to
Augur itself are excluded from automatic application; no self-replacement.

First implementation should support one selected installation mode per policy;
the owner must identify the intended mode from separately supplied public evidence.
This design does not inspect or classify the installed application.

## Canonical catalogue interface and bootstrap evidence

Pre-Augur evidence remains explicitly `bootstrap-public-evidence`, not an Oracle
snapshot. Once a legitimate Augur exists, ordinary canonical admission can retain
its own lineage. No source is promoted by renaming the bootstrap record.

For existing canonical snapshots, `OracleCandidateView` consumes the exact
unchanged snapshot and separately approved map. It emits H schema
`imperium.oracle-evaluation-candidate-view/v1`, body exactly
`snapshot_ref, mapping_ref, criteria_digest, candidates, excluded`.
Each candidate is `{model_ref, knowledge_ref, access_ref, admissibility_ref,
runtime_binding}`; runtime_binding is `{provider, model_id, model_version,
dispatch_id, configuration_schema_digest, adapter_ref, mapping_ref}`.
Join on exact provider/model/version; no alias guessing or runtime fields added
to the old snapshot. Missing, duplicate, expired or contradictory mapping excludes
the candidate with a reason. Mapping never supplies capability, access or
admissibility evidence.

A future versioned case-opening consumer must accept this view, bind both digests
and freeze its candidates. Keep old consumers and historical records unchanged;
do not feed the view to v1 by disguising it as an old snapshot. Tests must exercise
real ledger output through mapping admission and the new consumer, not inject
the required fields directly into a fixture.

## Shared resource/custody and atomic application

`OracleBootstrapAuthorityResolver` returns the original policy/commission/current
holder and exact remaining rights. It does not reuse a Courtthane resolver.
Proposed `BootstrapResourceDecision` derives no more than the signed commission's
exact provider/model/destination/wire/pricing/maxima/expiry. It yields a typed
`imperium.bootstrap-cognition-claim/v1`, not a renamed formation session claim.
The exact claim body and tagged authority_source/lease_consumption variants are
defined in F2. Access binds its real infrastructure executor, custody and access
grant; assessment binds its current Augur and commission. Neither case has an
invented/null authority placeholder. All source-specific validation and one-use
consumption remains mandatory; model cognition cannot consume an access grant.

One shared coordinator in the existing aggregate reserves against the same
identified budget for both old formation and new bootstrap calls. Typed validators
remain distinct. Sum settled usage plus all unsettled maxima before admitting a
new reservation; neither a new operation ID, alias nor policy ID resets exposure.
Existing FC claims remain readable and require their existing validation. The
extension must prove cross-type contention and preserve original settlement rules.

Delivery, consumption and dispatch commit before their respective side effects;
no lock spans credential/network work or another store lock. Typed currentness is
rechecked at dispatch. Unknown outcomes retain maximum exposure; no reissue,
retry or refund. Usage metadata and immutable envelopes are required for recovery;
a caller-supplied body is never sufficient. B1/default refusals remain a live gate.

Application resolves policy, authenticated assessment, permitted exact tuple set,
valid Profiles and expected role generations under one aggregate lock. It writes
the complete next assignment set, consumes the single application effect, and
retains one receipt in that frame. Its commit_head refers to the predecessor
head (the serialized boundary), avoiding a frame hashing itself. All next rows
are `{role, generation, binding_ref, profile_ref, prior_ref, policy_ref}`. Consumers
read this authoritative set; separate file exports are non-authoritative views.
No partial set or receipt-only assignment becomes current. Crash before commit
leaves old set; crash after commit permits receipt-only recognition. Out-of-policy
or changed-generation results refuse without applying any role.

An initial policy may explicitly contain a one-use conditional application effect
binding the finite permissible tuples, result schema, predicates and expiry.
It is consumed only after those exact conditions pass; model text cannot extend
them. Otherwise a later exact signed APPLY act is mandatory. Neither form changes
institutional appointments, grants execution or replaces Augur automatically.

## Review and implementation gate

Review this proposed competence allocation and decision sheet before schema
freeze. Before implementation acceptance: prove real producer-to-consumer chains,
foreign-key/schema rejection, stale/revoked issuer refusal, both founding modes,
joint-root closure contention, access-listing versus invoke permission, exact
catalogue join, all custody crash checkpoints, cross-type budget contention,
atomic multi-role application and retained historical recovery. These are future
tests, not results of this pass. No agents, PHP execution or runtime changes now.
