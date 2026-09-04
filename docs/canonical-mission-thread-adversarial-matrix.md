# Canonical mission thread adversarial matrix

| Attack | Mechanical refusal evidence |
|---|---|
| Missing mission | Reconciliation writer has seven non-optional parameters; first two require `MissionCapability` and `MissionCapabilityConsumer`. |
| Forged mission identifier | `MIS203_CAPABILITY_MISSION_MISMATCH` |
| Cross-mission capability substitution | `MIS203_CAPABILITY_MISSION_MISMATCH` |
| Capability replay | `MIS209_CAPABILITY_CONSUMED` |
| Expired capability | `MIS207_CAPABILITY_EXPIRED` |
| Revoked capability | `MIS208_CAPABILITY_REVOKED` |
| Actor substitution | `MIS205_CAPABILITY_ACTOR_MISMATCH` |
| Target substitution | `MIS206_CAPABILITY_TARGET_MISMATCH` |
| Action escalation | `MIS204_CAPABILITY_ACTION_MISMATCH` |
| Self-issued authorization | Signature failure `MIS202_CAPABILITY_FORGED`; consumer interface exposes no issuance method. |
| Lineage treated as authorization | Reconciliation writer consumes mission custody before source resolution or authority construction. |
| Mission mutation after authorization | Dossier identity changes, then `MIS203_CAPABILITY_MISSION_MISMATCH`. |
| Execution after terminal disposition | `MIS304_MISSION_TERMINAL` before capability consumption. |
| Two contenders for one capability | One `CONSUMED`; one `MIS209_CAPABILITY_CONSUMED`. |

## Bounded writer coverage

The campaign claims exercised coverage only for these production writers and their tested paths:

| Writer | Covered write path |
|---|---|
| `CanonicalRepositoryInspectionMission` | status projection, evidence record, completed/refused/aborted/expired terminal receipts |
| `NativeEffectReconciliationIssuanceAuthorizationService` | mission consumption, decision and issuance-authority publication |
| `NativeEffectReconciliationAuthorityIssuanceService` | issuance-capability consumption, reconciliation authority and issuance publication |
| `NativeEffectReconciliationAuthorityClaimDerivationService` | reconciliation-capability consumption and recovery-claim publication |

No coverage is generalized to other Imperium writers or authority corridors.

