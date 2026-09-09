# Citadel formation runtime, version 1

FC0–FC3 adds the versioned [formation claim custody contract](citadel-formation-claim-custody.md): optional exact signed transport terms, v2 prepared claims, durable one-use custody and default-dormant adapter seams. Existing v1 evidence/recovery remains unchanged; no live authority or B1 amendment follows.

This is the local implementation mapping of the provisionally accepted
[decisions](../docs/citadel-mission-formation-decisions.md). It does not enroll a
real operator, choose a live provider, install personnel, activate bootstrap,
commission execution, or confer semantic competence on a fake response.

## Jurisdiction and institutional provenance

`citadel.castellan` is a Citadel Seat for reception, relevance judgment, bounded
interview, attributable understanding, and separately authorized proposal work.
It is not a root principal, bootstrap Officer, replacement Seneschal, personnel
selector, resource approver, or execution commander. This mandate supersedes the
retired architecture's intake allocation only. The Castellan prohibitions in the
historical bootstrap manifest and the self-construction retirement in
`Bootstrap/MasterMason` remain intact. No sealed bootstrap artifacts change.

`FormationInstitution` resolves existing, non-placeholder institutional incumbents
from the production root-installation record and its exact current occupancy.
Missing, ambiguous, substituted or unsupported successor lineage fails closed.
The adapter does not open the root-installation window or create an incumbent.
Garrison admits the Persona; Guildhall determines suitability; Laboratorium
derives the immutable officer Profile; four distinct Senate committee incumbents
author consistency, governance, practice and security findings; the Lord Speaker
reconciles the exact findings; the Imperator approves the exact examined Profile;
Conscription attests the qualification criteria; its formation assembly service
derives the manifestation identity. Appointment remains a distinct exact act.

The signed evidence adapter carries these decisions, not an automatic selection
policy. Each evidence signature belongs to an exact current institutional actor
under an explicit owner delegation limited to role, parent instance, scope, key
and expiry. No public key from incoming evidence becomes trusted on its own.
Evidence references must already exist. The Profile must retain admitted Persona
identity, Laboratorium stewardship, immutable target Seat, transformation,
cognitive payload, qualification contract, lineage and content digest. Lifecycle
attestations are projections of the authenticated candidate, examination and
approval evidence; they never assert `current_active` or activate the Profile.

The supported incumbent source is the existing non-placeholder operator-root
installation/occupancy producer. A governed successor represented by another
schema stops at `CMF123_GOVERNED_SUCCESSOR_ADAPTER_REQUIRED`; it is not silently
treated as a founding incumbent. Real deployment must resolve that specific
adapter when applicable. Synthetic proof creates its pre-existing institutions
through the same root-installation producer in a newly generated temporary root.

Castellan and the formation Locksmith have separately signed appointment terms:
`candidate`, `scope`, `seat`, `generation`. Succession invalidates affected grants.
Each child has separate Seneschal, Chamberlain and Secretary/Isolde manifestations.
The mission presentation names the exact eligible candidates; it does not choose
them. Citadel, intake, parent instance, mission and child Curia identities remain
distinct. Storage-generated Citadel identity is not a new Imperium principal.

## Authentic decisions and public trust

Deployment owns public-only trust enrollment through `FormationSignatures`.
Enrollment is deliberately absent from the ordinary command protocol. It requires
the confirmed SHA-256 public-key fingerprint and a bounded validity interval.
It does not import or enlarge ProtectedMission's existing
`APPROVE_CANONICAL_MISSION_PLAN` competence. The formation-specific verifier checks
Ed25519 signatures using the same canonical JSON implementation, exact effect,
object digest, Citadel identity, trust fingerprint, time bounds and revocation.

The envelope is `{payload, signature}`. Payload fields are:

```json
{
  "schema": "imperium.citadel-owner-decision/v1",
  "citadel_id": "<returned-storage-identity>",
  "trust_fingerprint": "<64-lowercase-hex>",
  "effect": "<exact-operation-effect>",
  "object_digest": "<sha256-of-canonical-object>",
  "issued_at": 1788796800,
  "expires_at": 1788797400,
  "nonce": "<48-lowercase-hex>"
}
```

`signature` is base64 Ed25519 over `CanonicalJson::encode(payload)`. No secret is
accepted by the command. The owner envelope for mission review binds **terms,
line_digests and rationale**, including all numbered lines for approval. Revoking
a decision records an authenticated `REVOKE_DECISION` act over `{nonce}`.

## Session, planning and invocation mapping

The three phases are `interview`, `drafting`, `acceptance`. Their distinct effects
are `AUTHORIZE_INTERVIEW_SESSION`, `AUTHORIZE_EXACT_DRAFTING` and
`AUTHORIZE_RECEIVING_ASSESSMENT`. The source must be obtained from
`authorization-source`; it binds the holder and exact intake lineage, drafting
request or receiving packet respectively.

Session terms contain `source`, `provider`, `model`, `destination`, `pricing`,
`per_call`, `total`, `visible_intakes`, `disclosure`, `expires_at`. Both ceilings
have integer `calls`, `input_tokens`, `output_tokens`, `cost_microusd`,
`milliseconds`; a per-call ceiling permits exactly one call. The signed session
covers the evolving transcript within unchanged intent and disclosed visibility.
It does not approve each question separately or fund another phase.

`GovernanceProviderResourceDecisionService::formationSession` is the authentic
v2 specialization of the existing resource-decision producer. It records the
actual signer and parent institution rather than the v1 development actor. For
drafting it also derives an explicit Planning Authorization bound to the exact
Charter, approval, holder, resource ceilings, conditions, expiry and revocation.
Its only permitted commission is the exact planning-only per-call authority.
The legacy fixed-model v1 consumer cannot consume this v2 session as an unlimited
grant. `FormationSessionLeaseService` derives the exact authority and appointed
Locksmith lease inside the aggregate budget reservation transaction. Claim,
authority and lease are consumed at the same durable commit.

The adapter must inspect pricing and enforceable worst-case limits without I/O,
then enforce exact outgoing content, destination, token ceilings, cost and time.
All pending or uncertain attempts retain their maximum exposure. Trustworthy
usage may settle actual consumption; unknown outcomes never refund or retry.
Calls occur outside registry locks. The final local start fence rechecks the
current holder, Locksmith, grant and deadline. Existing provider response
envelopes retain the exact return and claim digest; admission rechecks current
lineage and response schema. A sealed response can be recovered without another
provider call. These records are specialized aggregate storage, not duplicated
legacy per-request authority files.

The shipped `UnavailableFormationTransport` refuses every call. A later real
adapter needs enforceable provider/pricing/usage and the applicable Clavium and
external-boundary controls; a string-returning provider adapter is insufficient.
The proof's in-process fake enforces synthetic byte-token accounting and a fixed
synthetic tariff. This establishes control mechanics, not real token pricing.

## Drafting and formation

The interview return contains only disposition, understood intent, one question,
dissent, unknowns, overlap and drafting readiness. Proposal fields are rejected.
Understanding with dissent is valid. A signed reply preserves exact bytes and
invalidates affected understanding; changed intent advances its version.

The exact drafting request has identity, version, author, doctrine, lineage and
a proportionate Charter: scope, questions, inputs, participating offices,
external effects, disclosure, expected return, stop conditions, amendment
triggers, retention and expiry. This implementation supports present-material
drafting. Nonempty `offices` or `external_effects` stops with the concrete need
for a separate competent investigation commission. It does not pretend that
such a commission occurred or silently investigate.

Drafts have immutable versions and numbered lines, all eleven disclosure
categories, explicit formation dependencies and the complete existing Delegate
Step 1 plan schema. An objection cites exact line digests. Subsequent authorized
drafting receives prior versions and reviews. Mission approval, constitution and
three exact appointments are separately typed effects in one owner presentation.
Changed registry generation requires attributable overlap reassessment; unchanged
review terms can then be reused. New versions or changed intent cannot pass an
earlier approval at reservation or delivery.

Reservations fence identity before any child-root effect. `RESERVED_NO_EFFECT`
may expire into a preserved tombstone and be replaced by a newly valid review.
`EFFECT_UNCERTAIN_IDENTITY_FENCED` cannot expire into another identity. MasterMason's
child-formation consumer reads the exact fenced reservation, revalidates its
owner decision and qualified occupants, and writes the immutable child receipt.
It creates neither a root principal nor a bootstrap state. Parent publication
is idempotent and reconciles the same child receipt after interruption.

### Local correction: terminal refusal and completed-effect recognition

A session that records refusal is terminal. Every later control is refused,
including signed OPEN or DEFERRED controls issued before the refusal. Admission
also checks retained refusal controls, so an OPEN projection written by older
code cannot revive the grant. The old ledger and control history remain intact.
Never-refused deferral/resumption reuses the valid grant and remaining aggregate
budget. New work after refusal needs a genuinely new, separately granted session.

Child publication now serializes its final current approval, dossier, occupant
and revocation checks with the bounded local receipt write under the Citadel
journal lock. It does no cognition, credential handling or external I/O there.
The receipt retains `imperium.citadel-child-publication/v1`: the exact journal
generation/digest, reservation and prepared-content digests, observed authority
time and native institutional occupancy/installation witnesses. Original signed
approval and personnel evidence remain in the exact prepared packet.

`deliver-handoff` first recognizes a present exact receipt. It resolves the
referenced frame through the verified retained journal chain; checks the original
reservation token, source and target identities and exact prepared content;
reverifies the original approval and appointment authority at the recorded
publication boundary; and verifies retained institutional signatures and native
witnesses. That read-only path can survive later expiry, revocation or succession.
It records `RECOGNIZED_COMPLETED_EFFECT`, the original authority time/frame and
the current recognition time, with `new_authority: false`. Concurrent recognizers
publish one parent transition and never rewrite the receipt or add acceptance.

The historical verifier cannot create a child. If no receipt exists, only the
ordinary current-authority producer can write one. Expired/revoked permission
does not pass that producer, including after an interruption before child rename.
An uncertain identity cannot expire into a replacement identity. An existing
receipt with mismatched bytes or missing/corrupt/unverifiable publication evidence
stops at `CMF130_HISTORICAL_PUBLICATION_AUTHORITY_UNVERIFIABLE`. Older receipts
without this provenance remain fenced; no timestamp or witness is synthesized
for them. Receipt absence with expired permission remains fenced at the current
approval check (`CMF094`), rather than being reported as a completed effect.

Revocation ordered before the serialized publication prevents the effect. A later
journal generation revoking the decision preserves that revocation while allowing
recognition of the prior fact, even at the same clock timestamp. Historical
verification never renews a grant, lease, appointment or execution permission.
Receiving assessment and Step 1 retain their own current-authority checks.

The timestamp and observed incumbency are trusted local publisher observations,
bound to retained custody, not an independent timestamp authority or proof against
an administrator rewriting all stores. The unkeyed digests detect mismatches;
they are not signatures. Original decisions and institutional evidence are signed.
The correction proves process interruption around native rename, not power-loss
durability. Preexisting unknown effects without trustworthy provenance require
separate custody investigation and remain fenced.

The receiving Seneschal assesses the entire original packet under its own exact
bounded session. `ReceivingFormationHandoffService` places the sealed assessment
in the child's handoff store. It accepts responsibility or returns a concrete
gap. Receipt recovery does not re-interview, reapprove or call the provider.
Step 1 validation checks the approved schema, target, holder and receiving
receipt. It derives no demand, commission, deployment or execution authority.

## Historical boundary and storage assumptions

Fresh Curian audience/deliberation, their direct gateway/authority consumers,
supplied-plan stores/builders and downstream planning consumers cannot treat
missing origin as a legacy exemption. Explicit owner-signed migration inventories
identify exact historical records. There is no normal CLI inventory registration.
ProtectedMission scratch reconstruction carries only the exact inventoried
protected input and original limits. Historical reads remain available; old
development actors do not become authentic Citadel owner decisions. The retired
profile smoke driver's fabricated chain is exercised by a test-owned historical
driver, never admitted as a production fresh-request workflow.

Runtime storage is a trusted custody boundary. The journal uses immutable,
hash-linked generation frames, one lock, a flushed temporary file and atomic
rename. Interrupted unpublished files confer no authority. This is process-crash
recovery; it is not a proof of filesystem power-loss durability or protection
against an administrator replacing all trusted storage. Child paths are generated
and existing link/junction aliases are refused. No root path is accepted in a
normal command. Snapshot storage is intentionally simple and has linear read and
growing historical storage costs; large-installation performance is not proven.
