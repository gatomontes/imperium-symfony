# PPC2 implemented subset

Disposition: **PARTIAL_MODEL_BOUND_PROFILE_EVIDENCE; CURRENT_ASSIGNMENT_EVIDENCE_UNAVAILABLE**. Owner approval of A–F is recorded in [the approval record](handoffs/provider-profile-designation-approval.md). This document describes executable scope, not completion of A–F.

## Versioned Profile ingress

`FormationPersonnel::recordModelBoundProfile` accepts `{payload, signature}`. The payload is the historical personnel payload plus required `schema: imperium.formation-model-bound-profile-evidence/v1`; the complete payload is signed. The command operation is `record-model-bound-profile-evidence`. The old `record-personnel-evidence` operation rejects this schema-bearing payload. Its old accepted/rejected envelopes retain their meaning.

The new ingress accepts only existing delegated Laboratorium `DERIVED_PROFILE` competence. It grants no designation or revocation purpose. Content has exactly `seat` and `artifact`, and exactly two source references identify the admitted Persona and suitability. Targets are Courtthane and Locksmith; artifact class remains officer, steward Laboratorium. Generic Profile version remains 1.0.0. The model-binding member follows the generic closed shape, including the authorization ID and exact source line. Those fields are preserved declarations pending independent authorization-source verification, not self-authenticating model approval.

The immutable full Profile digest is checked before a temporary structural projection reuses historical officer predicates. The projection is never stored, returned, signed, approved or treated as an evidence identity. All examination, findings, approval, qualification, lifecycle and separate appointment refer to the full model-bound Profile envelope. Changing its model/configuration requires new immutable bytes and its own chain. Current validation additionally checks retained envelope hashes, original signatures, delegation identities, scope, exact role/kind and current actor for every new-path source and all four findings.

The resulting lifecycle ends at `approved`. Independent personnel appointment retains its existing holder-generation meaning. Neither creates current designation, authenticates an O4 binding generation, or grants settings/execution authority.

Retained candidate envelope identities are checked before selecting a schema validator, including on the historical path. Corrupting a versioned original by removing its schema/model-binding cannot downgrade it to the older predicates. Valid historical originals retain their meanings; corrupt identity-key substitutions refuse.

## Empty state initialization

`FormationProfileDesignationInitialization::initialize` is an explicitly constructed offline owner operation, excluded from default service discovery and not exposed through the command. It requires the exact journal head and existing owner signature for `INITIALIZE_FORMATION_PROFILE_DESIGNATIONS` over `{schema, citadel_id, expected_head}`. It creates the optional top-level `profile_designations` member with exactly `schema`, `initialization`, `delegations`, `events`, `current`. The initialization retains those terms and the original decision; all three collections are empty. Historical frames and onboarding schema remain unchanged.

Duplicate initialization refuses. Any recorded affected sessions, claims or reservations, or onboarding claims/source fences, conservatively refuse initialization; the operation does not guess that those owners are idle. Two signed operations at one head have at most one publication. This is initialization only: there is no designation delegation/event ingress, current-index reader, expiry/supersession/revocation implementation or native assignment verifier in this subset. No populated extension is interpreted as authority.

## Required missing correspondence

`ProfileModelBindingSealingService` emits `imperium.conscription-profile-model-binding/v1` with an immutable sealed Profile, source Profile, authorization digest and consumed sealing authority. `MissionAuthorizationDerivationService` supplies exact target, model specification, configuration and dossier line. Neither produces a binding generation or a schema-qualified correspondence to the O4 candidate-binding/configuration originals. The alternative delegate-mission sealing owner is for a separately commissioned delegate turn and does not supply the permanent O4 pair's original.

O4's admitted assignment tuple and predicate row carry `binding_generation`, but `AssignmentEvidence` must independently prove that value. A copy of the tuple, constant 1, count of designation events, Profile version, or generic admitted hash would not do so. The missing owner-produced correspondence needs a concrete competence/source contract identifying the native sealing original, exact O4 binding/configuration identity and immutable generation. This run does not add model-approval jurisdiction or invent that source through a new generic owner signature.

The [writer inventory](provider-profile-designation-writers.md) also retains an unresolved global synchronization boundary. No unsupported source or unsynchronized path is promoted to production assignment evidence. `MissingAssignmentEvidence` remains shared by application and persistent settings; no replacement always-refusing wrapper has been introduced. Account/access, base eligibility and cognition remain separately unresolved. Combined FRESH establishment remains blocked by the unchanged vacancy/native-publication ordering.
