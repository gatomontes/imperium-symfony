# Citadel readiness: R0 boundary and disposition

Current update (2026-09-08): [source integration and IR01 are accepted](citadel-readiness-integration-acceptance.md)
within their local scope. The owner confirmed `E:\htdocs\imperium`; public records
establish concrete Garrison/Guildhall schema and authority questions, while the
upstream chains and current competence remain unverified. The
[native lineage campaign](next-campaign-citadel-native-institutional-lineage.md)
collects those exact public prerequisites and supports only justified dormant
witness variants. B1 and live commissioning remain blocked. The original R0
source observations and matrix below are preserved with their historical date/scope.

Disposition: **READINESS_PREPARATION_COMPLETE_WITH_EXPLICIT_BLOCKERS**.
This is a local commissioning preparation package, not an operational installation.
CF01/CF02 remain accepted within the [reviewed scope](citadel-formation-correction-acceptance.md).

## Source identity and inspection boundary

Entry HEAD `7a7881b91f10f8c2382b28029ba2846d448370a9`, tree
`f655e4e20c207bc0955ea8a5968bf5627b9f23ce`, on
`codex/citadel-operational-readiness` in `E:\htdocs\imperium-citadel-readiness`.
Origin: `https://github.com/gatomontes/imperium-symfony.git`.
Accepted import `645d53bdbb80d537ef0a7f226b8ad48f192ea1ef` is an ancestor;
its accepted tree is `ae5d701c1ef69cc6610306cfe40f0f518cb7e5cc`.
Campaign and acceptance documents are present. Entry was clean; no applicable
AGENTS.md was found at E:\, E:\htdocs, or recursively in this checkout. No Git
lock was present in the common directory or this worktree's Git directory.
No reset, merge, fetch, push, branch deletion or installation inspection was used.

Inspected source and generated synthetic roots only. No owner public deployment
export was supplied. In particular, the other source-review worktree is not
evidence of the intended installation. No private journal, environment secrets,
private key, credential store or unrelated machine was inspected. Local Composer
dependencies were installed from the unchanged lock with scripts disabled; this
does not install or commission Imperium. Exact final identities belong to the
terminal report and packet manifests.

## Producer / consumer matrix

Paths below are repository-relative. VERIFIED_SOURCE means code was inspected,
not that an authentic deployment record exists.

| Boundary | Legitimate producer → consumer | Local evidence | Real prerequisite / action |
| --- | --- | --- | --- |
| Installation and custody | Owner's intended installation and protected storage → fixed `%kernel.project_dir%` DI, native reference validation | VERIFIED_SOURCE; checkout identity above | UNVERIFIED: owner identifies actual root, source commit/tree, custodian and public export time. No caller-selected production root is introduced. |
| Formation public trust | `FormationSignatures::enrollPublicTrust` → `verify` | VERIFIED_SOURCE: 32-byte Ed25519 public key, SHA-256 fingerprint confirmation, `not_before`, `expires_at`; competence `CITADEL_MISSION_FORMATION`; distinct Citadel ID, revocation | MISSING: public trust projection and independent fingerprint/custody confirmation. Enrollment is an administrative effect, absent from ordinary CLI. Existing ProtectedMission trust is insufficient. |
| Institutional incumbents | `Bootstrap/OperatorRootPersonnelInstallationService::install` v3 package → native v2 installation/ACTIVE occupancy → `FormationInstitution::witness` | VERIFIED_SOURCE: exact installation digest, occupancy binding, parent identity; ambiguity/corruption refused | UNVERIFIED: nine native public witnesses. Use `imperium:citadel:public-institutions` after reviewed source is separately installed in the identified root. Missing export is not permission to install institutions. |
| Successor lineage | Actual governed successor producer → corresponding institutional resolver | UNSUPPORTED unless specific legitimate schema is supplied; `CMF123_GOVERNED_SUCCESSOR_ADAPTER_REQUIRED` | Owner supplies exact public successor record and its producer when export reports CMF123. No concrete deployment successor was supplied; no speculative adapter created. Never reopen the sealed founding window. |
| Persona admission | Garrison/Constable under role-limited owner delegation → `FormationPersonnel::record`, `candidate` | VERIFIED_SOURCE: `ADMITTED_PERSONA`, identity/version/digest and admission evidence | MISSING genuine Garrison judgment; a synthetic Persona or owner-authored eligibility flag is not evidence. |
| Suitability | Guildhall/Guildmaster → `SUITABLE_CANDIDATE`, candidate source chain | VERIFIED_SOURCE: exact Persona reference, target Seat, rationale | MISSING genuine suitability and attribution. |
| Profile | Laboratorium/Alchemist → `DERIVED_PROFILE`, `FormationProfileContract` | VERIFIED_SOURCE: immutable target, Persona, steward, transformation, cognitive payload, qualification criteria and content digest | MISSING genuine derived Profile. Lifecycle projections are not activation. |
| Examination | Four distinct Senate committee actors → signed findings → Lord Speaker reconciliation `EXAMINED_PROFILE` | VERIFIED_SOURCE: consistency/governance/practice/security, exact Profile sources, PASS, no security block | MISSING actual attributable findings/reconciliation. Merely naming four committees is insufficient. |
| Profile approval and qualification | Imperator `APPROVE_FORMATION_PROFILE` + Conscription/Recruiter `QUALIFIED_MANIFESTATION` → `FormationOfficerAssemblyService::assemble` | VERIFIED_SOURCE: approval binds Profile/examination/scope/Seat; qualification criteria and approval digest; assembly derives identity | MISSING genuine signature/qualification. The founding installation producer explicitly does not prove later internal admission/qualification. |
| Appointments | Imperator exact `APPOINT_CASTELLAN` / `APPOINT_FORMATION_LOCKSMITH` → `currentCastellan` / `currentLocksmith` | VERIFIED_SOURCE: candidate, Citadel scope, Seat, next generation; succession invalidates grants | MISSING exact eligible candidates and current appointment evidence. Assignment or model configuration is not appointment. |
| Interview source and authority | `CitadelIntakeService`, `FormationCognition::authorizationSource` → `GovernanceProviderResourceDecisionService::formationSession` v2 | VERIFIED_SOURCE: exact opening exchange, intent version, holder digest; signed bounded evolving interview scope | MISSING real intake and exact owner terms. Preparing them neither grants resources nor activates cognition. |
| Lease and consumed claim | `FormationSessionLeaseService::derive` inside `FormationCognition::call` aggregate transaction → bounded transport, response envelope | VERIFIED_SOURCE: authority/lease consumed with reservation; exact request digest, provider/model/destination, maxima, expiry; current holder/Locksmith start fence | Existing `ClaimBoundCredentialBroker` refuses this claim schema/ID before issuing any credential capability. Test proves zero credential access. |
| Credential custody | `LaCortine/CredentialBroker::issue/consume` behind `Clavium/ClaimBoundCredentialBroker` → brokered provider callback | VERIFIED_SOURCE legacy contract, not formation support | UNSUPPORTED formation credential adapter: needs authentic aggregate claim/currentness/consumption validation at this custody boundary, not renaming the ID or minting legacy claims. Actual credential reference/custody unverified; no secrets requested. |
| Provider and response | `SymfonyAiBrokeredDelegateProviderInvoker` → `DeepSeekSymfonyPlatformAdapter` → `.asText()`; `ProviderResponseEnvelopeService::seal` retains bytes/claim | VERIFIED_SOURCE legacy text route and configured DeepSeek name only | NO CURRENT OWNER SELECTION for this campaign. No tariff adopted. Text-only response is not trustworthy token/cost usage. Existing response envelope preserves bytes; it cannot manufacture provider usage evidence. |
| Enforceable transport | `BoundedFormationTransport::inspect/invoke` → `SessionExposure`, sealed admission | UNSUPPORTED live contract. `UnavailableFormationTransport` remains the DI default (`CMF034`) | Decision B1 below. No network adapter or activation bypass added. Missing tariff/limits must refuse before I/O. |
| Drafting | Current understanding + exact Charter → `AUTHORIZE_EXACT_DRAFTING`, Planning Authorization → dossier | VERIFIED_SOURCE and offline command rehearsal | Later separate exact approval. Nonempty Office investigation/external effects remain outside supported present-material drafting. |
| Mission / child / receiving | Exact numbered presentation → separate mission review → generation reservation → MasterMason → `ReceivingFormationHandoffService` | VERIFIED_SOURCE; original and new offline proofs retained | Separate eligible child Seneschal, Chamberlain and Secretary/Isolde; authentic receiving assessment under its own grant. Step 1 validation grants no execution. |
| Recovery | Atomic exposure + sealed response; retained child publication fence and witnesses → `recover-response`, `deliver-handoff` | VERIFIED_SOURCE; CF01/CF02 regressions retained | Unknown calls retain maxima, no retry/refund. Receipt recognition grants no new authority. Local publisher time/custody and process-interruption proof are not independent timestamps or power-loss durability. |

## B1: precise transport decision

The accepted interface requires enforcement of output, time **and cost** ceilings
at the actual transport boundary and trustworthy settlement. Source currently has
neither a formation-compatible credential consumer nor a bounded usage-returning
provider adapter. The configured legacy DeepSeek name does not select it for this
campaign. No changing provider claim is adopted here, so no current tariff or
external documentation is asserted as verified.

An owner may supply a selected provider/model/destination and contractual evidence
of enforceable remote bounds, billing semantics, pricing validity and trustworthy
usage. That evidence would permit a concrete adapter assessment. Alternatively,
the owner may explicitly amend the accepted contract to distinguish local
transmission deadline and tariff-based worst-case reservation from a guaranteed
remote billing/cancellation ceiling. An amendment must say which costs remain
outside the bound and how unknown outcomes consume exposure; it cannot merely
rename a timeout or estimate as enforcement. No such policy amendment is made here.

The missing adapter cannot be honestly completed by passing existing MessageBag
content to `.asText()` or accessing an environment key directly. An eventual
adapter must bind exact serialized wire bytes and destination/model before I/O,
reject redirects/material changes, enforce supported output and local transmission
limits, consume the legitimate claim at custody, retain trustworthy response ID
and usage, and preserve maxima on unknown outcomes. The new preparation surface
has no credential, provider, journal or activation dependency.

The adjacent governance path was also traced: `Clavium/GovernanceCognitionInvoker`
uses `GovernanceClaimBoundCredentialBroker::claimFor/consume`, not the Delegate
broker. It requires native `imperium.clavium-governance-cognition-invocation-claim/v1`
records in `var/imperium/runtime/governance-cognition-invocation-claims`, with
matching native cognition requests, `GOVERNANCE_INVOCATION_CLAIMED_DURABLE_PRE_IO`,
`lease_consumption`, `governance_authority_consumption` and a fixed DeepSeek binding.
Formation instead stores `imperium.citadel-session-call-claim/v1` inside the
aggregate journal, with `derivation`, `derived_authority_consumed` and
`lease_consumed`. The shared ID prefix does not make these schemas interchangeable;
the governance broker refuses unavailable or mismatched native records with
GCA450/GCA451. Its invoker reaches the same text-only platform adapter, so it also
does not satisfy the bounded transport contract. This conclusion is source-traced;
the new focused credential spy test specifically exercises the Delegate broker.

`DeterministicJournalBoundCredentialBroker` and stationary credential resolution
were checked as adjacent boundaries as well. Their admitted deterministic effect
journal, execution claim, `email.send` operation and AgentMail capability path do
not authorize formation cognition. They are not an alternate route around Clavium.

## Public export schema and owner evidence

`docs/citadel-readiness/public-evidence-request.json` is the exact top-level input.
Null means missing. Supplied objects remain `SUPPLIED_UNVERIFIED_OWNER_EVIDENCE`;
hashes establish supplied-byte identity only. The trust byte check verifies
fingerprint/competence/time/revocation syntax but cannot certify enrollment.

Fill `installation` with owner-confirmed `root`, `commit`, `tree`, `custodian`,
`exported_at` and public custody evidence references. Fill `trust` only from the
custodian's public projection: `citadel_id`, `public_key` (base64 raw Ed25519),
`fingerprint` (SHA-256 raw key), `competence`, `not_before`, `expires_at`, `revoked`.
Retain the public enrollment receipt if one exists; no private journal export is
needed. Fill `institutions` with the public-institutions command's result.
Fill `appointments` with the custodian's exact public appointment terms/decisions,
candidate references and public evidence closure, or explicit absence. Fill
`transport` with the owner's selected provider/model/destination, public claim
custody design, pricing source/date/validity, supported ceilings and disclosure;
leave it null when unselected. These nested records are review evidence, not a
new runtime import schema or positive readiness certificate.

First action and separated future commands: [owner runbook](citadel-readiness-runbook.md).
