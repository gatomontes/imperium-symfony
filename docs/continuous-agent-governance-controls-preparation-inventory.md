# Continuous Agent Governance Controls — Preparation Batch 0 inventory

## Status and boundary

Preparation Batch 0 is complete as a read-only runtime inventory against source commit
`5695527f10cfa3375f7902ce85687fc6bfae8dbf`.

This document changes no runtime behavior and opens no Iron Gate, Lazaretto, sortie,
revocation-propagation, telemetry, containment, incident-handling, or credential-platform
boundary. It creates no Delegate Mission Step 70, Runtime Integrity Hardening Step 36,
Credential Boundary Batch 18, or Institutional Decision Integrity Batch 7.

Classification vocabulary:

- `EXISTS_CANONICALLY`: one explicit, versioned contract and executable enforcement path
  already establish the requirement for its claimed scope;
- `EXISTS_FRAGMENTED`: lifecycle-specific Folia or enforcement prove useful parts, but no
  system-wide contract establishes the complete requirement;
- `ABSENT`: the requirement is in scope for a future internal-governance contract but no
  adequate canonical or fragmented implementation exists; and
- `DEFERRED_BOUNDARY`: the requirement depends on a deliberately unopened perimeter,
  revocation, telemetry, containment, or incident lifecycle.

## Runtime-principal inventory

| Principal | Current binding and lifecycle evidence | Competent actor | Classification | Exact gap |
| --- | --- | --- | --- | --- |
| Accountable owner | Operator-root bootstrap records and Imperator decisions identify human/institutional accountability in lifecycle-specific forms: `src/Imperium/Runtime/Bootstrap/OperatorRootOperationalizationService.php`, `src/Imperium/Runtime/DecisionIntegrity/*` | Imperator / operator | `EXISTS_FRAGMENTED` | No canonical runtime-principal identifier or owner lifecycle record follows a mission end to end. |
| Office | Office is carried in occupancy, commission, and actor references; authority remains record-bound: `docs/officer-taxonomy.md`, `docs/delegate-mission-flow.md` | Constituting authority for the Office | `EXISTS_FRAGMENTED` | No shared versioned runtime-principal binding contract or universal lifecycle state. |
| Occupied Seat | `binding_id`, `binding_digest`, `seat`, `manifestation_id`, and `occupancy_generation` recur in canonical actor records and are live-revalidated by lifecycle services | Office-specific occupancy authority | `EXISTS_CANONICALLY` | Canonical only as the current actor-binding convention, not yet collected in one principal contract. |
| Persona | `persona_id`, version, digest, admission and custody state are separate from Profile and Manifestation: `contracts/persona-artifact.md`, Garrison custody Folia | Foundry authors; Garrison admits and holds | `EXISTS_CANONICALLY` | No governance tier or consequence class is attached. |
| Profile | `profile_id`, immutable version, digest, limitations, steward and target are canonical: `contracts/profile-artifact.md`, `contracts/profile-model-binding.md` | Laboratorium derives; Senate examines; Imperator approves; Conscription installs | `EXISTS_CANONICALLY` | No common runtime lifecycle state, governance tier, or consequence class. |
| Officer process | `Officer` plus explicit `LEGATE`/`DELEGATE` classification is canonical and grants no authority: `docs/officer-taxonomy.md`, `src/Imperium/Runtime/Identity/OfficerClass.php` | Conscription / appointing lifecycle | `EXISTS_CANONICALLY` | Runtime process identity is normally represented by Manifestation and occupancy rather than an independently versioned process-principal record. |
| Manifestation | `manifestation_id`, Officer class, assembly lineage, Seat binding, generation, activation and retirement appear in operational Folia | Conscription assembles/activates; Garrison controls custody/retirement | `EXISTS_CANONICALLY` | No shared principal schema spans Legate, Delegate, witness and future sortie forms. |
| Represented or delegating human | May appear as an authority act or Imperator decision, but has no separate canonical runtime representation | Imperator / future delegating authority | `ABSENT` | Independent identifier, representation scope, lifecycle, and authority basis are undefined. |
| Credential holder | Clavium retains custody; Locksmith is attributable; cognitive callers receive neither reference nor secret: `docs/credential-boundary-remediation.md`, `contracts/clavium-provider-access-assertions.md` | Clavium / Locksmith | `EXISTS_CANONICALLY` | Holder is canonical for provider cognition, but not yet a generalized external tool/destination principal binding. |
| Disposable sortie | `sortie_id` appears in the legacy sortie seam, but the continuous-governance perimeter lifecycle is expressly unopened | Future Iron Gate / La Cortine authority | `DEFERRED_BOUNDARY` | No campaign-authorized sortie-principal lifecycle or effect reconstruction may be defined here. |

The identities above are not interchangeable. Office, Seat, Persona, Profile, Officer,
Manifestation, accountable owner, credential holder, and future sortie must remain
separately attributable. Existing repetition of their fields is evidence of the separation;
it is not yet a canonical system-wide principal envelope.

## Authority and credential-lease inventory

| Family | Scope, holder, consumer and time controls | Consumption, supersession and revocation | Sources and executable proof | Classification |
| --- | --- | --- | --- | --- |
| Lifecycle authorities | Exact source ID/digest, recipient actor or Seat, bounded act, exercisable/single-use flags and negative adjacent-authority flags | Lifecycle services consume or close the exact next act; replay drift fails stopped; generalized revocation absent | `docs/delegate-mission-authority-consumption-matrix.md`; `src/Imperium/Runtime/Persistence/AuthorityConsumptionStore.php`; `tests/Imperium/Runtime/TransactionalPersistencePrimitivesTest.php` | `EXISTS_CANONICALLY` for consumption; `EXISTS_FRAGMENTED` as a common lease model |
| Delegate provider lease and claim | Exact activation, commission, Manifestation/Seat target, provider/model/runtime binding, input, expiry, opaque lease and one turn | Lease and turn are atomically consumed before I/O; expiry/mismatch/changed activation rejected; no revocation field | `contracts/provider-invocation-claim.md`; `tests/Imperium/Runtime/ProviderInvocationClaimServiceTest.php`; `tests/Imperium/Runtime/ProviderInvocationClaimReferenceMigrationTest.php` | `EXISTS_CANONICALLY` for one cognition turn |
| Operational cognition lease and claim | Exact decision/request, Manifestation target, provider/model/configuration, input digest, resource ceiling, issue/expiry (maximum five minutes), single use | Exact replay only; expired, consumed, substituted and partial/divergent consumption fail stopped; no revocation field | `src/Imperium/Runtime/Clavium/OperationalCognitionLeaseService.php`; `tests/Imperium/Runtime/OperationalCognitionLeaseServiceTest.php`; `tests/Imperium/Runtime/OperationalCognitionInvocationClaimServiceTest.php` | `EXISTS_CANONICALLY` for operational cognition |
| Governance cognition lease and claim | Exact native governance authority, cluster/type, occupied Seat target, purpose/input digest, provider/model/configuration, resource ceiling and expiry | Native authority plus lease are single-use and claim-bound; no omnibus grant; no revocation field | `src/Imperium/Runtime/Clavium/GovernanceCognitionLeaseService.php`; `tests/Imperium/Runtime/GovernanceCognitionAccessSubstrateTest.php`; governance boundary tests | `EXISTS_CANONICALLY` for migrated internal cognition |
| Provider access assertions | Provider/scope, Locksmith attribution, observation, restrictions, validity and revalidation triggers without secret disclosure | Assertion has no use authority; expiration is evidence freshness, not generalized revocation | `contracts/clavium-provider-access-assertions.md`; `contracts/profile-model-access-attestation.md` | `EXISTS_CANONICALLY` |
| External execution-time lease | Would require capability, tool, credential, target, data boundary, policy/evidence versions, stop conditions, revalidation, revocation and effect receipt | Not permitted until a separate execution/perimeter campaign | `todo/continuous-agent-governance-controls.md`; preparation constraints | `DEFERRED_BOUNDARY` |

Existing leases have issuance, expiry, freshness-by-reread, exact scope, holder/issuer,
consumer, single-use and consumption. Supersession is usually enforced as changed-source or
conflicting-replay refusal rather than a uniform lease field. No active cognition lease has a
canonical `revoked_at`, revoker, reason, affected scope, acknowledgement, or residual-exposure
record. That is a real gap; Batch 0 does not fill it.

## Governance-event equivalents

| Existing evidence form | What it proves | Sources/tests | Classification and gap |
| --- | --- | --- | --- |
| Immutable Folia | Attributable, digest-bound decisions, authorities, custody acts, assembly, activation, results and retirement are emitted during transitions | `src/Imperium/Runtime/Persistence/ImmutableRecordStore.php`; `tests/Imperium/Runtime/TransactionalPersistencePrimitivesTest.php` | `EXISTS_FRAGMENTED`: no common event schema or semantic event kind. |
| Codex Imperii | Generation-bound compilation of operational Folia | `src/Imperium/Runtime/Persistence/CodexImperiiStore.php`; `tests/Imperium/Runtime/DelegateMissionOperationalTransitionCoordinatorTest.php` | `EXISTS_FRAGMENTED`: not a complete mission event stream. |
| Mutable transition journals | Provider reservation/start/response identity/failure/unknown-outcome states with timestamps and claim digest | `src/Imperium/Runtime/Clavium/ProviderInvocationJournalService.php`; journal service/concurrency tests | `EXISTS_CANONICALLY` for provider I/O state only. |
| Authority consumptions | Authority, exact source/digest, consumer and consumption time | `AuthorityConsumptionStore.php`; `TransactionalPersistencePrimitivesTest.php` | `EXISTS_CANONICALLY` for migrated consumers. |
| Custody and retirement transitions | Exact prior/current custody, binding and terminal no-authority state; crash-safe forward convergence | deployment/terminal coordinator and crash-demonstration tests | `EXISTS_CANONICALLY` for the Delegate corridor. |
| Decision-integrity bundle | Option universe, presentation directive, surface, evidence, requests, prior decisions and sealed record | `DecisionIntegrityReconstructionService.php`; decision-integrity tests | `EXISTS_CANONICALLY` for the adopted personnel-use decision only. |

There is therefore no need to replace existing Folia with a new logging substrate. The missing
boundary is a versioned, authority-empty governance-event envelope that references existing
native records without mutating or duplicating their judgments.

## Restriction, interruption, invalidation, retirement and return inventory

| Mechanism | Current competent actor and effect | Classification | Gap |
| --- | --- | --- | --- |
| Refusal/return/defer branches | The actor competent for each bounded checkpoint opens no next authority | `EXISTS_CANONICALLY` | These are pre-act lifecycle dispositions, not runtime revocation propagation. |
| Material-change invalidation | Institutional Decision Integrity makes prior consent stale and requires fresh presentation | `EXISTS_CANONICALLY` | Adopted at personnel use only; not a generalized runtime lease invalidator. |
| Expiry/freshness refusal | Clavium leases, access attestations, decisions and claims fail stopped when expired or stale | `EXISTS_CANONICALLY` | No live revocation authority or propagation acknowledgement. |
| Provider interruption/unknown outcome | Pre-I/O failure and unknown provider outcome prohibit automatic replay; recovery requires sealed response and separate authority | `EXISTS_CANONICALLY` | Stops retry, but does not cancel provider work or revoke other scopes. |
| Custody restriction | Garrison availability/reservation/deployed custody mechanically prevents incompatible use | `EXISTS_CANONICALLY` | No instance/Profile/tool/destination-wide revocation command. |
| Witness retirement | Senate sterile witnesses retire under the completed proceeding boundary | `EXISTS_CANONICALLY` | Proceeding-specific only. |
| Delegate terminal return/retirement | Seneschal authorizes the predeclared return; Garrison restores Persona custody, unbinds Seat and retires Manifestation | `EXISTS_CANONICALLY` | Terminal closure, not immediate arbitrary interruption. |
| Runtime `RESTRICT`/`INTERRUPT`/`REAUTHORIZE`/`RETIRE` propagation | No opened competent cross-scope control path | `DEFERRED_BOUNDARY` | Must be separately contracted after internal identity/event foundations. |

## Reconstruction coverage

| Reconstruction | Coverage | Sources/tests | Classification |
| --- | --- | --- | --- |
| Institutional decision | Complete adopted personnel-use decision bundle | `DecisionIntegrityReconstructionService.php`; `DecisionIntegrityValidationAndPersistenceTest.php`; `DelegateMissionGuildhallResolutionFlowCase.php` | `EXISTS_CANONICALLY` |
| Terminal operational evidence | Fourteen records from deployment authorization through cognition and terminal retirement, including live binding/custody checks | `docs/delegate-mission-terminal-operational-evidence-audit.md`; `DelegateMissionOperationalEvidenceAuditService.php`; audit migration test | `EXISTS_CANONICALLY` for its explicit subchain |
| Crash recovery evidence | Operational construction, deployment custody, unknown provider outcome, and terminal retirement | four crash-demonstration docs/tests | `EXISTS_CANONICALLY` for those corridors |
| Provider attempt history | Claim-bound reservation/start/result identity/failure/unknown outcome with no automatic retry | provider journal service/concurrency tests | `EXISTS_CANONICALLY` |
| One complete mission | No single native view joins pre-deployment Steps 1–52, decision bundle, operational audit, provider journal and retirement | audit exclusion plus current services | `EXISTS_FRAGMENTED` |
| External effect and incident | No authorized Iron Gate receipt or incident lineage exists | preparation constraints | `DEFERRED_BOUNDARY` |

## Requirement-by-requirement classification

Every checkbox in `todo/continuous-agent-governance-controls.md` is classified below. A
classification does not mark the TODO complete; it states the current proof posture.

| ID | Requirement (abridged) | State | Evidence, actor and exact gap |
| --- | --- | --- | --- |
| CAG-01 | Consequence tiers | `ABSENT` | No versioned tier vocabulary or escalation matrix. First internal contract gap. |
| CAG-02 | Advisory provenance/disclosure/evidence/data/accountability | `EXISTS_FRAGMENTED` | Native Folia and claim-bound cognition provide provenance; no advisory-output contract or uniform disclosure/data obligations. Curia remains accountable for mission use. |
| CAG-03 | Controls increase with consequence/autonomy | `ABSENT` | Stronger controls exist by lifecycle, but no canonical tier-to-control rule proves proportionality. |
| CAG-04 | Capability possession is not authority | `EXISTS_CANONICALLY` | `docs/officer-taxonomy.md`, authority matrix, negative authority fields and broker-only credential path enforce this invariant. |
| CAG-05 | Tier/class on Profile, lease, sortie demand, receipt | `ABSENT` | Profiles and cognition leases lack the fields; sortie demand and execution receipt are unopened. |
| CAG-06 | Bind all distinct runtime principals | `EXISTS_FRAGMENTED` | Persona/Profile/Seat/Officer/Manifestation/Clavium are separate; owner, represented human and common principal bindings are missing; sortie deferred. |
| CAG-07 | Independent principal ID and lifecycle | `EXISTS_FRAGMENTED` | Domain IDs/states exist, but no uniform owner/Office/process principal lifecycle. |
| CAG-08 | No shared credentials/blended accounts | `EXISTS_FRAGMENTED` | Broker-issued single-use capabilities prevent cognition reuse; no generalized account contract across future tools/sorties. Clavium is competent. |
| CAG-09 | Minimum principal/tool/scope/destination/duration lease | `EXISTS_FRAGMENTED` | Cognition leases bind target, provider/model, input and time; tool, destination and data scope are not generalized. |
| CAG-10 | External effect reconstructs through all principals/owner | `DEFERRED_BOUNDARY` | Requires future sortie and execution receipt. |
| CAG-11 | Canonical governance-event envelope | `ABSENT` | Immutable native records exist, but no versioned common envelope. |
| CAG-12 | Attributable timestamped digest events emitted during work | `EXISTS_FRAGMENTED` | Folia, consumptions and journals do this inconsistently; timestamps/actor shape are not universal. |
| CAG-13 | Link full mission lifecycle | `EXISTS_FRAGMENTED` | Decision bundle and 14-record audit are separately reconstructable; Steps 1–52 and reassessment are not joined. |
| CAG-14 | Distinguish observation/inference/recommendation/decision/authorization/attempt/effect/evidence | `EXISTS_FRAGMENTED` | Artifact schemas imply these meanings, but no canonical event-kind vocabulary distinguishes them across the runtime. |
| CAG-15 | Preserve exact sortie tool/policy/lease/target/output/provider/failure/retry evidence | `DEFERRED_BOUNDARY` | Provider cognition journal covers a subset; sortie execution remains closed. |
| CAG-16 | Missing authority/scope/target/result evidence fails closed | `EXISTS_CANONICALLY` | Reference validator, immutable stores, decision reconstruction, claims and terminal audit fail stopped in their claimed corridors. |
| CAG-17 | Mechanical one-mission reconstruction | `EXISTS_FRAGMENTED` | Multiple native reconstructions exist; no one complete mission view. |
| CAG-18 | Execution-time authority lease full binding | `DEFERRED_BOUNDARY` | Cognition leases are not external execution leases; Iron Gate contract remains unopened. |
| CAG-19 | Iron Gate immediate revalidation | `DEFERRED_BOUNDARY` | Iron Gate execution expressly closed. |
| CAG-20 | Reject stale/superseded/expired/revoked/scope/target/evidence drift | `EXISTS_FRAGMENTED` | Current claims reject all listed forms except generalized revocation and external target changes. |
| CAG-21 | Consume/close single-use after bounded attempt | `EXISTS_CANONICALLY` | Claims atomically consume lease and authority before provider I/O; journal prohibits automatic replay. |
| CAG-22 | Receipt bound to lease and decision | `DEFERRED_BOUNDARY` | Provider response envelope is claim-bound, but external execution receipt is unopened. |
| CAG-23 | Immediate non-cooperative runtime revocation | `DEFERRED_BOUNDARY` | No authorized revocation lifecycle may be added in Batch 0. |
| CAG-24 | RESTRICT/INTERRUPT/REAUTHORIZE/RETIRE | `DEFERRED_BOUNDARY` | `RETIRE` exists terminally; the required unified runtime dispositions do not. |
| CAG-25 | Multi-level revocation scopes | `DEFERRED_BOUNDARY` | No propagation model exists. |
| CAG-26 | Revocation closes/prevents leases | `DEFERRED_BOUNDARY` | Expiry does; attributable revocation does not. |
| CAG-27 | In-flight external cancellation/quarantine/safe finish/escalation | `DEFERRED_BOUNDARY` | Requires external execution and containment policy. |
| CAG-28 | Revocation evidence and residual exposure | `DEFERRED_BOUNDARY` | Requires canonical revocation record. |
| CAG-29 | Kill-switch propagation independent of cognition | `DEFERRED_BOUNDARY` | Requires separate non-cognitive propagation proof. |
| CAG-30 | Control telemetry vocabulary | `DEFERRED_BOUNDARY` | Provider journals are authoritative transition state, not telemetry. |
| CAG-31 | Intended vs attempted vs actual effect | `DEFERRED_BOUNDARY` | No external effect corridor. |
| CAG-32 | Detect unauthorized tools/credentials/destinations/data/paths | `DEFERRED_BOUNDARY` | Credential bypass scan proves one static boundary; continuous detection remains unopened. |
| CAG-33 | Detect identity/capability/credential/sortie proliferation | `DEFERRED_BOUNDARY` | No telemetry plane. |
| CAG-34 | Detect lineage breaks/version drift | `EXISTS_FRAGMENTED` | Runtime validators fail individual transitions; no continuous detector or telemetry event. |
| CAG-35 | Feed telemetry to Curia without decision authority | `DEFERRED_BOUNDARY` | Reassessment intake is unopened. |
| CAG-36 | Escalation thresholds | `DEFERRED_BOUNDARY` | Depends on telemetry, revocation and incident lifecycles. |
| CAG-37 | Mechanical least privilege across tool/credential/data/destination/time | `EXISTS_FRAGMENTED` | Credential/model/time bounds are strong; tool/data/destination controls await perimeter work. |
| CAG-38 | Iron Gate/disposable sortie separation | `DEFERRED_BOUNDARY` | Doctrine exists; execution adoption remains closed. |
| CAG-39 | Lazaretto provenance/sanitization/authority-none | `DEFERRED_BOUNDARY` | Lazaretto admission remains closed. |
| CAG-40 | Egress controls/data masking | `DEFERRED_BOUNDARY` | Requires tool/data/destination enforcement. |
| CAG-41 | Prevent external contamination of identity/scope/authority | `DEFERRED_BOUNDARY` | Requires Lazaretto and memory/instruction policy. |
| CAG-42 | Prompt injection/memory poisoning/credential/provider compromise response | `DEFERRED_BOUNDARY` | Detection/response and incident control remain closed. |
| CAG-43 | Version lineage for policy/Profile/tool/schema/enforcement per effect | `DEFERRED_BOUNDARY` | Profile/schema/model lineage exists fragmentarily; no external effect record or enforcement-code lineage. |
| CAG-44 | Incident record and competent authority | `DEFERRED_BOUNDARY` | Incident lifecycle unopened; actor must be selected without granting telemetry decision authority. |
| CAG-45 | Incident evidence preservation | `DEFERRED_BOUNDARY` | Immutable records are preservable, but no incident hold/bundle exists. |
| CAG-46 | Containment preserves forensics/seals | `DEFERRED_BOUNDARY` | Immutable Folia supply a foundation; containment lifecycle unopened. |
| CAG-47 | Residual exposure and competent owner before closure | `DEFERRED_BOUNDARY` | Institutional decision residual-risk ownership is analogous, not an incident-closure contract. |
| CAG-48 | Remediation/reauthorization/resumption/retirement lineage | `DEFERRED_BOUNDARY` | Requires incident and revocation contracts. |
| CAG-49 | Advisory-only mission demonstration | `ABSENT` | Existing governance cognition tests prove bounded calls, not one complete advisory mission with owner/disclosure/data lineage. |
| CAG-50 | External mission through receipt and retirement | `DEFERRED_BOUNDARY` | Iron Gate/sortie execution unopened. |
| CAG-51 | Valid decision without execution lease cannot effect | `DEFERRED_BOUNDARY` | Credential bypass proves cognition access only, not external effect. |
| CAG-52 | Revoked lease cannot be exercised | `DEFERRED_BOUNDARY` | No revocation propagation exists. |
| CAG-53 | Credential non-reuse across Manifestation/instance/sortie | `EXISTS_FRAGMENTED` | Broker authenticity, exact target, expiry, single use and claim contention are proven; future sortie/account scope is not. |
| CAG-54 | Target/tool/data/evidence change forces refusal | `EXISTS_FRAGMENTED` | Target/input/model/evidence digest drift is rejected in current cognition corridors; tool/data/external target are deferred. |
| CAG-55 | Hostile inbound remains authority-none evidence | `DEFERRED_BOUNDARY` | Requires Lazaretto adoption. |
| CAG-56 | Complete incident reconstruction from native artifacts | `DEFERRED_BOUNDARY` | Incident lifecycle unopened. |

## Exact gaps and smallest safe campaign sequence

The provisional sequence in `docs/next-campaign-continuous-agent-governance.md` is too broad
at its first step: principal separation largely exists, while consequence classification does
not. The smallest safe sequence is therefore:

1. **Batch 1 — canonical consequence vocabulary and runtime-principal binding references.**
   Define versioned, authority-empty schemas for consequence tiers and references to the
   already separate owner, Office, Seat, Persona, Profile, Officer process, Manifestation and
   credential custodian. Do not create or merge principals. Adopt first on one existing
   advisory/internal-governance artifact only.
2. **Batch 2 — canonical governance-event envelope over existing Folia.** Define a sealed,
   authority-empty reference envelope with event kind, actor/principal references, source and
   result digests, occurrence/recording time, consequence class and explicit observation vs
   judgment semantics. Do not rewrite historical records or build telemetry.
3. **Batch 3 — bounded internal mission reconstruction.** Compose the existing decision
   bundle, native Folia, authority consumptions, cognition claims/provider journal, custody and
   retirement references into one read-only mission view. State its exclusions mechanically.
4. **Batch 4 — internal execution-time authority-lease normalization.** Extend only existing
   internal cognition lease contracts with common freshness, supersession, invalidation and
   revocation-authority metadata; preserve their exact issuers, consumers, scopes and negative
   authorities. This is not revocation propagation and grants no external execution.
5. **Batch 5 — revocation disposition and propagation design, then implementation in a
   separately authorized campaign.** Begin only after Batches 1–4 make principal scope and
   event evidence canonical. Keep this outside Batch 0.
6. **Later separate campaigns — telemetry/reassessment, incident/containment, then perimeter
   adoption.** Iron Gate and Lazaretto must remain last and separately authorized; sorties and
   execution receipts cannot be inferred from the internal cognition work.

The actual next implementation boundary is Batch 1 only. Its competent institutional design
owners are Curia for mission consequence classification, Imperator for acceptance of the
consequential commitment, and each existing Office for its native identity and authority
facts. The new contracts must remain descriptive and authority-empty: classification cannot
authorize a capability, occupy a Seat, activate a Manifestation, issue a credential, invoke a
provider, or cross a perimeter.

## Preparation completion claim

This inventory establishes what exists, what is fragmented, what is absent, and what remains
constitutionally deferred without modifying any completed actor, judgment, checkpoint,
authority, lease, Folium, custody state, provider path, or runtime behavior. The campaign may
advance only by separately authorizing the proposed Batch 1 boundary.
