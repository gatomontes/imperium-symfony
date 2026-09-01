# Frozen Runtime Coverage Tripwire Restoration Preparation Batch 0

## Result

`PREPARATION_BATCH_0_COMPLETE_FROZEN_RUNTIME_TRIPWIRE_REGRESSION_CLASSIFIED`

Preparation inventories and classifies only. It changes no runtime source,
coverage snapshot, assertion or exception set.

## Weakened tripwires

| Surface | Pre-PR #728 invariant | Current PR #728 behavior | Escape created | Classification |
| --- | --- | --- | --- | --- |
| Runtime candidate inventory | Frozen candidates exactly equalled snapshot paths. | Only snapshot paths missing from runtime fail. | A new candidate absent from the snapshot passes unnoticed. | `REGRESSED_ONE_WAY_SUBSET_CHECK` |
| Runtime inventory totals | Exact runtime, non-successor, authority-file and candidate totals changed only with explicit maintenance. | Four totals were removed. | Broad inventory drift loses a secondary alarm. | `REMOVED_COARSE_TRIPWIRE` |
| Authority store users | Discovered users exactly equalled the approved set. | Only missing expected users fail. | An additional unapproved `AuthorityConsumptionStore` user passes. | `REGRESSED_EXPECTED_SUBSET_ONLY` |
| Perimeter inventory | Exact perimeter and frozen-perimeter totals were asserted. | Only non-empty perimeter is required. | Arbitrary perimeter growth is invisible to this test. | `REMOVED_COARSE_TRIPWIRE` |
| Forbidden-helper scan | Every frozen perimeter file was scanned. | Only perimeter files already listed in the historical authority snapshot are scanned. | A new LaCortine or Sortie file can import forbidden transactional helpers without inspection. | `REGRESSED_SNAPSHOT_LIMITED_SCAN` |
| Approved successor presence | Every approved successor was required in the runtime. | The exact presence assertion was removed from the runtime inventory test, while one perimeter intersection check remains. | Missing non-perimeter approved successors lose their direct alarm. | `PARTIALLY_REGRESSED` |

Raw numeric counts are useful alarms but poor governing truth when left
unexplained. Batch 1 must restore completeness through explicit versioned sets
and bidirectional equality. Counts may remain as derived diagnostics; they may
not substitute for path-level classification.

## Required invariants

1. Every mechanically detected authority candidate is either present in the
   versioned snapshot or in a separately named approved-successor inventory.
2. Every snapshot and approved-successor path exists and still matches its
   governing detector.
3. Discovered `AuthorityConsumptionStore` users exactly equal a versioned
   approved set; both additions and removals fail.
4. Every current LaCortine and Sortie PHP file is inspected for the forbidden
   helper vocabulary unless individually classified in a narrow, named
   exception inventory.
5. Adding a runtime file cannot be made green merely by avoiding an old
   snapshot path.
6. Snapshot updates must state the path, classification, reason, authorizing
   campaign/batch and focused test. Silent regeneration is forbidden.

## PR #728 changes requiring separate adjudication

| Change | Observation | Batch 0 posture |
| --- | --- | --- |
| `GovernedProviderExecutionCombinedAdmissionService` | Adds compatibility revocation-store naming and checks a legacy revocation ID before admission. | `UNRELATED_POTENTIALLY_VALID_REQUIRES_FOCUSED_PROOF` |
| `ProviderExecutorPrincipalActivationCanonicalContractValidator` | Tightens binding comparison when the resolved principal is absent but the decision actor declares a binding. | `UNRELATED_POTENTIALLY_VALID_REQUIRES_FOCUSED_PROOF` |
| Activation-disposition vocabulary exceptions | Expands one source-scan allowlist from one contract to seven contracts/demonstrations/validators. | `BROADENED_EXCEPTION_REQUIRES_EXACT_ROLE_JUSTIFICATION` |
| Independent-verification files | New classes under `src/IndependentVerification` are outside `src/Imperium/Runtime` and do not justify weakening the runtime snapshot. | `NO_JUSTIFICATION_FOR_TRIPWIRE_RELAXATION` |

Batch 0 neither reverts nor blesses these changes. Batch 2 must test their exact
behavior and record an individual disposition for each.

## Smallest lawful restoration

- replace one-way subset checks with exact bidirectional path-set comparisons;
- maintain an explicit approved-successor inventory rather than permitting all
  post-snapshot growth;
- scan the complete live perimeter, then subtract only narrow named exceptions;
- make store-user discovery exact again;
- add mutation-style focused tests that create synthetic extra paths/usages and
  prove the detector rejects them without modifying the real runtime tree; and
- keep this campaign separate from the terminally refused evidence campaign.

## Closed perimeter

Preparation does not change tests or runtime behavior. It does not restore the
evidence closure, inspect private evidence, execute a mission, handle a live
credential or signing capability, invoke a provider, perform external I/O,
mutate runtime state or change provider binding.

The controlling evidence posture remains
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`.
