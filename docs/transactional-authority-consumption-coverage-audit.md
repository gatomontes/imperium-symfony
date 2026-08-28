# Transactional Authority Consumption Adoption — Batch 12 coverage audit

## Mechanical reconstruction

Batch 12 changes no runtime code. It freezes a reproducible source snapshot and reconciles that
snapshot with the semantic inventory established in Preparation Batch 0 and updated through Batch
11.

| Surface | Exact result at merged Batch 11 |
| --- | ---: |
| Runtime PHP files inspected | 482 |
| Files containing `authority` or `Authority` | 371 |
| Strong consumable-authority candidates | 231 |
| Exact `TRANSACTIONAL_CANONICAL` consumers | 26 |
| Exact `LOCKED_FRAGMENTED` consumers | 3 |
| Inventoried noncanonical candidates or issuers | 202 |
| Version-1 envelope builders | 9 |
| Generic `AuthorityConsumptionStore` consumers | 1 |
| La Cortine and Sortie perimeter files checked for adoption leakage | 39 |

The strong-candidate scan is the union of runtime PHP files containing `authority_single_use`,
`authority_consumed`, literal `'consumed' => true`, or `authority_exercisable`. The complete sorted
result is frozen in `docs/transactional-authority-consumption-runtime-coverage-snapshot.tsv` and is
reconstructed by `TransactionalAuthorityConsumptionBatch12CoverageTest`.

The scan deliberately over-selects issuers, resolvers, evidence services and negative adjacent-
authority declarations. It is a tripwire, not a substitute for semantic classification. The
canonical preparation inventory remains authoritative for whether a row is `EXISTS_CANONICALLY`,
`EXISTS_FRAGMENTED`, `ABSENT`, or `DEFERRED_BOUNDARY`, and for each actual consumer posture.

## Adopted coverage reconstructed

| Corridor | Exact consumers | Count | Mechanic |
| --- | --- | ---: | --- |
| Governance cognition claim | `GovernanceCognitionInvocationClaimService` | 1 | direct version-1 envelope; authority → lease locks |
| Operational cognition claim | `OperationalCognitionInvocationClaimService` | 1 | direct version-1 envelope; authority → lease locks |
| Delegate provider claim | `ProviderInvocationClaimService` | 1 | direct version-1 envelope; composite activation lock |
| Provider-free missing-turn recovery | `DelegateMissionTurnRecoveryService` | 1 | generic `AuthorityConsumptionStore` |
| Delegate operational construction | qualification, assembly and Seat-binding services | 3 | checkpoint coordinator plus Codex CAS |
| Delegate deployment custody | `DelegateMissionOperationalCustodyTransitionService` | 1 | forward-only checkpoint coordinator |
| Delegate terminal return | `DelegateMissionTerminalReturnService` | 1 | forward-only checkpoint coordinator |
| Operational adoption | reconciliation and disposition services | 2 | `OperationalAdoptionAuthorityTransition` |
| Deterministic Delegate Senate | eight previously enumerated Steps 19–42 consumers | 8 | `DelegateMissionSenateAuthorityTransition` |
| Deterministic model-bound Profile Senate | testimony, finding-authority and deliberation openings | 3 | `ProfileSenateAuthorityTransition` |
| Delegate model governance | criteria presentation and selection decision | 2 | `DelegateMissionModelGovernanceAuthorityTransition` |
| Delegate model binding | `DelegateMissionModelBindingSealingService` | 1 | `DelegateMissionModelBindingAuthorityTransition` |
| Oracle eligibility recovery | `ModelEligibilityFindingService` | 1 | case lock, native finding checkpoint, separate transition |

The three interruption enforcement consumers remain exactly `LOCKED_FRAGMENTED`. They have native
single-winner locks and lifecycle reconstruction but do not pretend to use the generic consumption
contract.

## Explicit exclusions reconstructed

The 202 remaining strong candidates are not silently promoted. They remain within the inventory's
explicit families:

- boolean, missing-ID, missing-instance or missing-time authority surfaces remain `RACE_EXPOSED`;
- multi-write construction, admission, assessment, issuance, legacy operational and Legate chains
  remain `RECOVERY_INCOMPLETE`;
- cognition after an unjournaled model call remains `RECOVERY_INCOMPLETE`;
- Oracle research and every La Cortine, Iron Gate, Lazaretto, Sortie, external-receipt and new
  credential-platform act remain `DEFERRED_EXTERNAL_BOUNDARY`; and
- issuers, resolvers, guards, reconstruction services and negative adjacent-authority declarations
  are not reclassified as consumers merely because the lexical tripwire sees them.

All 39 PHP files under `Runtime/LaCortine` and `Runtime/Sortie`, plus the two Oracle research
services, contain none of the six lifecycle transition helpers, the generic consumption store or
the version-1 envelope builder.

## Adversarial review

### Claim: transactional adoption is runtime-wide

**Weak point.** Only 26 exact consumers are canonical. The generic store has one consumer. Most of
the authority-bearing runtime is intentionally not adopted.

**Verdict.** Any runtime-wide claim would be fiction. The truthful claim is corridor-specific
transactional adoption with a mechanically frozen exclusion perimeter.

### Claim: immutable storage makes the remainder safe

**Weak point.** Directory locking prevents divergent records at one ID. It does not prevent two
competing decisions from deriving different IDs, nor repair a crash between dependent writes.

**Verdict.** The `RACE_EXPOSED` and `RECOVERY_INCOMPLETE` classifications remain necessary.

### Claim: the documented lock map proves global deadlock freedom

**Weak point.** Lock order is local to each corridor. There is no central registry or inverse-order
detector.

**Verdict.** Canonical lock-order enforcement remains `ABSENT`; Batch 12 does not launder local
proof into a system-wide guarantee.

### Claim: a lexical scan proves semantic completeness

**Weak point.** Lexical scans over-select issuers and can miss powers expressed without the selected
tokens.

**Verdict.** The snapshot is a change detector only. Completeness still rests on the manual issuer/
consumer inventory, flow and authority matrix. The mechanical and semantic records agree at this
commit; neither is allowed to impersonate the other.

### Claim: deferred external boundaries can inherit internal proof

**Weak point.** Internal lock/commit convergence says nothing about credential release, network
outcome or receipt binding.

**Verdict.** No perimeter adoption leaked into Batch 12. External boundaries remain closed.

## Result

The adversarial review found no hidden canonical consumer, false helper adoption, perimeter leak or
runtime defect that Batch 12 is authorized to repair. It did identify the essential limit that must
survive closeout: **26 canonical consumers are not a canonical runtime.** Batch 13 may close the
documentation, but it may not erase the 3 locked-fragmented and 202 noncanonical/issuer tripwire
entries or imply global lock-order, revocation, telemetry, containment, incident or external-effect
semantics.
