# Frozen Runtime Coverage Tripwire Restoration Batch 2 adjudication

## Result

`BATCH_2_PR_728_PROVIDER_RUNTIME_AND_VOCABULARY_CHANGES_ADJUDICATED`

## Individual dispositions

| PR #728 change | Disposition | Governing reason | Focused proof |
| --- | --- | --- | --- |
| Combined-admission legacy revocation compatibility | `REVERTED_CONTRADICTED_ATOMIC_WINNER_CONTRACT` | The separate v1 revocation fact is historical design evidence with `DO_NOT_PRODUCE_SEPARATELY`. Aliasing its legacy ID into the authorized revocation-winner store bypassed the exact authority-consumption winner shape and did not inspect the historical store it purported to support. Only `ProviderBindingActivationRevocationWinnerContract` may block combined admission. | `ProviderActivationConsumptionRemediationBatch2Test::testHistoricalSeparateRevocationFactDoesNotReplaceAuthorizedWinner` plus the authorized-winner tests in Remediation Batches 5–7. |
| Absent-attestation-principal binding comparison | `RETAINED_FAIL_CLOSED_ACTOR_BINDING_FALLBACK` | When the resolved attestation omits a nested principal, the decision actor still declares the required exact binding. The activation target must match it; otherwise substituted binding evidence could pass. | `FrozenRuntimeCoverageTripwireRestorationBatch2Test::testAbsentAttestationPrincipalStillRequiresExactDecisionActorBinding`. |
| Expanded activation-disposition vocabulary exceptions | `CORRECTED_TO_EXACT_SIX_ROLE_CLASSIFIED_INVENTORY` | Six paths legitimately contain the vocabulary as contracts, a validator or an offline demonstration. `CorridorDispositionPrincipalAuthorityRemediationInterruptionDemonstration` contains neither token and is removed from the exception set. The versioned v1 inventory names each remaining path and role, and exact source discovery fails on additions or removals. | `ProviderBindingActivationIntegrityRemediationBatch6Test::testActivationDispositionVocabularyIsLimitedToExactClassifiedRoles`. |

## Preserved boundary

This batch changes no provider binding, activation-disposition vocabulary,
credential or capability custody, provider invocation, external I/O, evidence
closure or private evidence. It runs no mission and mutates no runtime state.
The controlling evidence posture remains
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`.

Only Frozen Runtime Coverage Tripwire Restoration Batch 3 terminal adversarial
audit may next be considered.
