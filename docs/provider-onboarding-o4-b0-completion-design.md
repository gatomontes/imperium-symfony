# O4-B0 completion design

This implementation continues the same branch from 4098f5a2cf1ebfdba952b2c6ac37e2f9a7eb1e5f. The submitted O4 entry remains 4711ea5def32148335069f396fc86ab6abc82df4 and the preparation baseline remains f544b696a0ebdf8c25a71f3a369e5a93738074e9. Earlier incomplete reports are retained as historical evidence. Final verification belongs to the completion report and package identities.

## Owning boundary and state

FormationJournal remains the only assignment aggregate and publication point. CommandLedger owns both initial application and explicit replacement. AssignmentMigration explicitly advances a quiescent v3 state to v4, retaining the exact bounded v3 predecessor, its digest, and predecessor head. The retained predecessor is limited to 8 MiB. Reads and previews never migrate. Original maps, signed/raw bytes, registrations and consumption remain immutable; sequence heads may advance through their existing owner.

The v4 applications and assessment_views maps contain at most 256 entries each and retain the existing overall state limits. A view map key is its exact record-reference key. An application entry is closed to `{key,receipt,command_ref,previous_application_ref,terms_ref,selected_set_ref,authority_key}`. Its key is the receipt-reference key; only the initial entry has a null previous application. Entries form one chronological, whole-pair chain. Current settings are derived from its latest receipt, with no separate mutable role map or alternate setter.

ApplicationHistory and StateValidation verify exact schemas, identities, originals, command/completion/slot links, authority consumption, deterministic initial selection, view derivation, chronological predecessors, and both generation transitions. Orphan records, broken links, partial rows, duplicate consumption and unsupported namespace entries refuse. This is structural/history verification, not fresh authority.

## Original evidence and selection

OriginalAssessment is a private trait shared by the public inspect-owning AssessmentResolver, CommandLedger and PersistentSettings. No public method accepts caller-supplied trusted state. Under the existing owner lock it reconstructs real W1/W2/W3 selected outcomes, claims, all custody checkpoints, retained envelopes and usage, frozen inputs, completed dependencies, exact mapping and semantic evidence through AugurAdapter. Classifier exceptions, R1 map limits and R2 pure bounded parse behavior remain unchanged.

The finite permitted-assignment-sets original is `{sets}` with rows `{set_ref,terms_ref}`. Each assignment-set original is `{assignments,profile_evidence_ref}`. There are 1–256 distinct pairs. Assignments are ordered Courtthane then Locksmith with exact fields `{role,provider,model_id,model_version,binding_ref,configuration_ref,profile_ref,profile_generation,binding_generation}`. Proposed terms are pre-enumerated admitted originals referencing the exact set; runtime derivation does not generate signatures. The genuine `imperium.bootstrap-assignment-selection-rule/v1` H carries the unchanged selected rule body.

The existing typed selector validates ranked coverage of the entire role FIT set before permissions are filtered, then uses the exact lower-middle tier and UTF-8 identity tie-break. W1 eligibility and role permissions constrain the choice. The independently selected complete pair must exist in the finite permitted set; a coupled refusal has no alternate search. Initial application creates no additional cognition request.

Profile-specific evidence is an admitted assignment-profile-evidence source `{rows}`. Rows contain `{role,binding_ref,profile_ref,profile_generation,binding_generation,predicates}`. Predicate keys are exactly the required predicates beyond the common CandidateClaim predicates. Each value is `{disposition,evidence_refs}`; selected predicates must PASS with original evidence. The fixed AssignmentEvidence deployment port independently validates those originals and current exact generations. Its default implementation refuses. A stored PASS or caller boolean is not a capability.

The assessment view body remains exactly `{policy_ref,group_outcome_refs,lineage_refs,predicate_results,permitted_assignment_set_ref,selection_rule_ref,selection_evidence}`. Predicate results retain common and Profile-specific findings. Selection evidence uses the exact contract fields, ordered by role. View sources bind policy, original outcomes, rule, selected complete set and Profile evidence. The view remains evidence only.

## Initial authority and atomic publication

The assessed_assignment_set rule retains exactly `{kind,permitted_tuple_set_ref,result_group_ids,required_predicates_ref,expected_assignments_ref,selection_rule_ref}`. The selected application slot is conditional. Mode A requires policy_effect; mode B requires a distinct retained signed APPLY act for the exact derived pre-enumerated terms. Changing only the policy mode without its compatible slot still refuses.

Historical command recognition precedes fresh head/currentness/selection checks. Fresh application rechecks exact head and predecessor, trust/policy/act, original sources and fences, complete evidence and current generations, and one-use consumption. The initial predecessor remains the approved two three-field rows `{role,generation:0,binding_ref:null}`. Initial application cannot be reopened by a different command, sequence or policy ID once settings exist.

One existing journal commit publishes the two selected tuples with generation 1, assessment view, application receipt, command, completion, step/slot and authority consumption. Receipt bodies remain exactly `{policy_ref,result_ref,authority,prior_assignments,next_assignments,consumed_effect_ids,commit_head}`; commit_head binds the observed predecessor without a self-reference. Refusals publish nothing and consume no command or right.

## Explicit replacement

CommandLedger::replace accepts the closed `imperium.assignment-change/v1` request: ordinary command identity/head/policy/predecessor fields plus `authority` and `terms_ref`; mode is advance, step_id is null, evidence_refs is empty. Authority must be signed_act. Its proposed terms reference an assignment-change original with exactly `{expected_application_ref,prior_assignments,next_set_ref,assessment_view_ref}`.

Replacement binds the exact current application, old complete tuple pair/generations, current application command predecessor, original evidence view, compatible current policy and permitted next whole set. Current evidence and Profile/binding generations are independently checked again. A changed Profile/evidence scope requires another governed evidence path and is refused here. An operator may explicitly choose another eligible permitted pair; this is not an automatic new recommendation.

A replacement consumes its own fresh signed nonce under `[instance,trust_fingerprint,nonce]` and a separate `slot.change-<nonce>` entry. It never reuses the bootstrap slot. Its command and receipt enter the same aggregate; both role generations advance together. Replacement commands leave the cognition sequence head unchanged; the application chain owns their predecessor. Historical initial/replacement commands remain recognizable after later replacement, revocation or expiry.

## Settings consumer and authority separation

PersistentSettings::snapshot returns `{application_ref,assignments,dispatch_authority:false}` after history validation. resolve(role) additionally validates current authority, evidence and the exact tuple; revalidate(application_ref) checks the complete pair against that exact current application without changing anything. Expired, revoked, unavailable or changed evidence blocks affected use but preserves historical settings. No fallback, catalogue expansion or credential-triggered substitution exists.

SettingsBoundTransport is the explicit opt-in consumer at the existing BoundedFormationTransport boundary. Signed FormationCognition terms may bind a full `model_settings` tuple. The wrapper checks its exact persisted identity and generation before inspect or invoke. SettingsPreparedTransport preserves PreparedFormationTransport and checks before prepareOperation; wrapping a prepared transport in the nonprepared wrapper refuses. FormationPreparedOperation binds optional model_settings into its exact serialized operation.

FormationPersonnel, FormationSessionAuthority, FormationSessionLeaseService and FormationCognition retain their separate personnel/session/lease/dispatch checks. Settings do not appoint anyone or authorize a call. Service configuration and global wiring are unchanged. Deployment must construct the fixed ports and opt into the consumer through the independently authorized path.

## Validation scope

Positive tests use actual O1/O2/O3 producers with synthetic temporary-root authority and exact MockHttpClient transport. Process workers reopen public synthetic evidence without credentials or signing. Fault injection exits immediately before the fsynced temporary file rename, or immediately after successful rename and before a response. This proves process interruption at that publication boundary, not arbitrary machine power-loss durability.

CommandLedger callbacks and settings current reads share the existing synchronous StrictJson::within scope. Policy raw records are parsed before evidence records so the existing bounded cache can reuse their encoding. Cache bounds, exact-byte/value equality, exception cleanup, and the prohibition on validity/currentness caching are unchanged.

The completion proof matrix and full logs state which checks passed and the full-suite outcome. Source review and fresh full hosted O4 CI remain integration gates. All five operational flags remain false and the actual retry allowlist remains empty.
