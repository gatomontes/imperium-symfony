# Canonical Mission Authenticity remediation — Preparation Batch 0 inventory

`CANONICAL_MISSION_AUTHENTICITY_PREPARATION_BATCH_0_COMPLETE`

## Frozen entry

| Datum | Exact value |
|---|---|
| Campaign entry | `b267e2c2b6a122694418ce59d2bf16319e602b07` |
| Accepted runtime baseline | `2527b33925bf3ef47d029786e60a6aefe752737b` |
| Quarantined adverse-evidence head | `3c4890ffd30f403f72a35b92f1e639d51c8c98f8` |
| Campaign branch | `codex/canonical-mission-authenticity-real-snapshot-remediation` |
| Authorized implementation scope | Preparation Batch 0 and Batches 1–3 only |
| Reference mission | Not authorized; no execution in Batches 0–3 |

The repository was clean at the exact campaign entry before the branch was created. No network,
credential, provider, remote, or reference-mission operation is part of this inventory.

## Existing authenticated planning lineage

The accepted planning chain is persisted under the repository-local Runtime root:

1. `PlanningDossierAssemblyService` seals an exact `imperium.curia-planning-dossier/v1` record,
   including version, digest, numbered lines, Mission Plan, disclosures, resource demands and a
   single-use Imperator review authority.
2. `ImperatorPlanningDossierReviewService` consumes that review authority and persists an exact
   `imperium.imperator-planning-dossier-review/v1` record with the actor, affirmative disposition,
   all-line acknowledgement, dossier identity/digest, review time and a single-use Mission
   Authorization derivation authority.
3. `MissionAuthorizationDerivationService` validates both records and consumes the exact derivation
   authority into an immutable `imperium.mission-authorization/v1` record.

The new bridge must start from the persisted Mission Authorization identifier and re-verify this
complete chain. A caller-authored dossier or provenance label is not an authority source.

## Runtime and persistence inventory

| Boundary | Accepted substrate | Required remediation |
|---|---|---|
| Immutable evidence | `ImmutableRecordStore`, canonical record digests | Reuse for mission records and reject tampering before mutation. |
| Cross-process exclusion | `AtomicTransition` file locks | Reuse with one mission/nonce lock identity and durable state inside the same cut. |
| Existing one-use evidence | `AuthorityConsumptionStore` | Its exact replay convergence is insufficient for lifecycle transitions; add required-state-bound mission consumption. |
| Service construction | `CanonicalNativeEffectCorridor` | Authority-bearing mission services must receive trusted dependencies through construction and expose no caller-selectable verifier/consumer. |
| Git-object boundary | None in accepted Runtime | Implement a read-only adapter resolving commit, tree and blob objects from Git itself. |
| Process harness | Existing PHP worker patterns under `tests/Imperium/Runtime/Support` | Implement two real PHP processes contending over one shared durable mission state. |

## Quarantined-candidate classification

| Candidate material | Classification | Disposition |
|---|---|---|
| Mission/dossier vocabulary and stable mission identity | `RECOVERABLE_SHAPE` | Reimplement with the persisted Mission Authorization as its sole root. |
| Mission state names, transition history, terminal receipt/status shape | `RECOVERABLE_SHAPE` | Reimplement with durable required-state transitions. |
| Mission identity propagated into downstream evidence | `RECOVERABLE_SHAPE` | Retain and extend with authorization, dossier, issuer and Git-object bindings. |
| `OperatorMissionBoundary` caller-created dossier acceptance | `AUTHORITY_COUNTERFEIT` | Reject; it verified only a provenance label and minted its own authority. |
| Caller-supplied `MissionCapabilityConsumer` | `AUTHORITY_COUNTERFEIT` | Remove from every authority-bearing method signature. |
| HMAC verifier returned inside `AcceptedMission` | `AUTHORITY_COUNTERFEIT` | Replace with a non-substitutable trusted Runtime custody boundary. |
| Caller-supplied `commit` plus `files` snapshot | `SIMULATED_EVIDENCE` | Replace with Git commit/tree/blob resolution and byte verification. |
| Sequential non-suspending Fiber test | `PROCESS_LOCAL_ONLY` | Replace with real independent PHP processes sharing durable state. |
| Pre-issued transition capabilities without required state | `REIMPLEMENT_REQUIRED` | Bind issuer, authorization, mission, dossier, action, actor, target, from/to state, time and nonce. |
| In-memory nonce retirement | `REIMPLEMENT_REQUIRED` | Persist consumption atomically with the required lifecycle transition. |

## Batch gates

- Batch 1: no dossier admission without an exact persisted Mission Authorization and fully verified
  approval lineage.
- Batch 2: no authority-bearing caller can provide, replace or select the verifier; fabricated
  provenance and malicious consumers fail before any record is written.
- Batch 3: inspected bytes originate from verified Git blobs; lifecycle use is durable, atomic and
  required-state-bound; two real processes prove single-winner contention.

