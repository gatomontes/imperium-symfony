# Formation permanent-seat model preparation v1

This provision implements the exact owner-approved PPC5 clauses A–F. It assigns
the enrolled Formation owner `AUTHORIZE_FORMATION_MODEL_PREPARATION` solely for
officer Profiles at `courtyard.courtthane` and `clavium.locksmith`, with
Laboratorium stewardship, in the same parent instance and Formation identity.
It grants one exact Conscription seal and no Profile approval, designation,
appointment, access, budget expenditure, settings application or execution.
The existing exact examined-Profile approval jurisdiction is unchanged.

`DELEGATE_FORMATION_MODEL_SEALING` is a separate owner act for the actual current
native `conscription.recruiter` incarnation, a fixed public key, exact binding
and finite lifetime. Personnel-evidence qualification delegations, mission,
delegate and tool-provider seals never supply this competence. Incoming acts
cannot enroll their own keys. Existing Formation trust verifies each distinct
effect and exact object; key possession alone supplies no purpose.

## Closed originals and acyclic creation

Native originals have exactly `schema`, `id`, `body`, `record_digest`. Their
reference is exactly `schema`, `id`, `digest`, using the native bare lower-case
SHA-256 of canonical JSON with `record_digest` omitted. O4 H references retain
their `sha256:` prefix. Both encoders must agree on accepted H bytes after
omitting its digest; complete H metadata, content bytes and content digest are
checked and retained. Objects are compared canonically, never by bare ID.

Creation order is:

1. Complete supporting H originals, in dependency-first order, then the existing
   `imperium.bootstrap-source/v1` configuration and candidate-binding originals.
   Every H source reference must resolve to exact already supplied bytes; missing,
   forward and duplicate originals refuse. Supporting originals are factual and
   confer neither admission nor preparation authority. The candidate-binding and
   request-configuration H originals, with exact schema/id/digest and all
   envelope metadata. Candidate content is the selected-policy literal model
   alias; configuration is the unchanged selected-policy request shape.
2. `imperium.formation-model-specification-binding/v1`: exact context,
   specification, full H originals, H references, native lineage and generation,
   predecessor, complete `supporting_originals` and observed publication head. This factual candidate is not
   authority. Provider/model/version spelling is explicit, never split from a
   display string. External model/version claims remain independently unverified.
3. `imperium.formation-model-specification-line/v1`: line number 1, full
   specification, exact native binding reference, H references and native
   generation, plus the native digest of that entire numbered line.
4. `imperium.formation-model-sealing-delegation/v1`: closed terms and original
   separately signed owner envelope, naming context, binding, current Recruiter,
   public key, validity and exact predecessor journal head.
5. `imperium.formation-model-source-profile/v1` wraps the exact retained, signed
   Laboratorium evidence envelope in a closed native original. `sourceOriginal`
   mechanically derives its ID/digest from those bytes; authorization checks its
   exact journal membership and current original institutional competence. This
   factual wrapper changes no historical personnel envelope or generic Profile.
   `imperium.formation-model-preparation-authorization/v1`: independently chosen
   unique authorization ID, closed terms and signed owner envelope. Terms bind
   purpose, context, binding, entire line, full source Profile, retained authentic
   typed complete Laboratorium source original, delegation, validity, head, nonce, correlation
   and reason. The authorization exists before output Profile construction.
6. `imperium.formation-model-preparation-seal/v1`: domain-separated signed
   `imperium.formation-model-sealing-act/v1` payload and complete resulting
   Profile. It binds authorization/delegation/binding references, full numbered
   line, typed source-evidence reference, context, complete source and output Profile, head and nonce. The generic
   Profile model_binding fields remain unchanged: authorization ID and source
   line now resolve to these authentic originals.

There is no forward reference or authorization/Profile hash cycle. A Profile
revision increments the bounded numeric minor version, preserving two/three-part
version shape and resetting an existing patch part to zero. Its exact source
identity becomes `lineage.supersedes`; other source bytes remain unchanged except
the model_binding and recomputed content digest. A second seal may not reuse the
same Profile ID/version. Each changed artifact requires its own examination,
approval and qualification chain.

## State, lineage and synchronization

The optional `model_preparation` extension has schema
`imperium.formation-model-preparation-state/v1` and exactly initialization,
bindings, lineages, delegations, authorizations, seals, consumed and nonces maps.
Absence means unavailable. Explicit owner-signed
`INITIALIZE_FORMATION_MODEL_PREPARATION` names schema, parent, Formation and
exact head. Existing affected sessions, claims, reservations or onboarding
source fences prevent initialization. Outer journal/onboarding schemas remain
unchanged. The initialization receipt is a historical fact, not current model
authority.

Each map is bounded to 256 entries; generation is 1–256. Lineage ID is derived
from context/provider/model ID, preventing caller renaming from resetting a
generation. Initial publication requires vacancy. Every successor references
the exact last native original and increments exactly one. IDs are never reused.
This immutable provenance history is not a current Profile/incumbent registry.
Only its latest binding may support new or current preparation authority.
Validity intervals are positive integral seconds, at most 3,600 seconds and
within the existing finite timestamp domain; authorization expires no later
than its delegation and signed owner decision. Text, content and Profile sizes
have explicit bounds in the closed implementation.
Each complete O4 H wrapper is at most 131,072 canonical bytes and has at most
64 source references; the content-byte bound alone is not the envelope bound.
The supporting-original list has at most 64 entries and 1,048,576 canonical
bytes. The complete typed source-evidence original is at most 262,144 canonical
bytes. Its artifact-only bound cannot substitute for this envelope bound.

All mutations enter Formation once. Current institution/package checks,
generation choice, authority verification, one-use decision and seal publication
share the same live same-root owner frame. Formation remains outside native and
bootstrap/target storage. No upward acquisition, caller-supplied held-lock flag,
new incumbent registry or changes to AtomicTransition/storage primitives occur.
One existing Formation journal frame binds complete seal and consumption. A
pending file is not an accepted fact; exact historical replay returns the old
receipt without changing state or conferring continuing authority.

## Strict consumer and lifecycle

`FormationPersonnel::recordAuthorizedModelBoundProfile` accepts only
`imperium.formation-model-bound-profile-evidence/v2`. Its Laboratorium content
adds a closed correspondence object containing seal reference, exact O4 binding
and configuration references and native binding generation. The fixed internal
`FormationModelPreparation::verifyInOwner` is consumed at ingress and again by
the model-bound candidate lifecycle validator. The explicit
`FormationPersonnel::authorizedModelCandidateInOwner` entry requires v2 and
refuses PPC2 evidence before assembling the lifecycle. It reads fresh journal custody
through the live owner frame; no detached currentness certificate is issued.

PPC2 v1 evidence remains structurally limited. It is never accepted by this
strict ingress or promoted to v2. Current v2 verification checks original
bytes/signatures, source Laboratorium evidence, current owner trust, both
decision revocations and expiry, native Recruiter tenure and complete native
packages. Historical replay does not bypass these current checks.

Laboratorium derivation, four Senate findings, Lord Speaker reconciliation,
competent exact Profile approval and Conscription qualification remain separate
acts. R3 designation/application/use is unavailable here. MissingAssignmentEvidence
and all production account/access/base/cognition ports remain refusing. R2 stays
accepted; R3/R4/R5/R6 stay open. All five flags remain false, DEFER_ENROLLMENT
remains, and the actual retry allowlist stays empty.
