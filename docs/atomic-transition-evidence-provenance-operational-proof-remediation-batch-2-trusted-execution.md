# Atomic Transition Evidence Provenance and Operational Proof Remediation Batch 2

## Result

`BATCH_2_TRUSTED_INTERNAL_CASE_EXECUTION_AND_PROVENANCE_BOUND_RESULTS_COMPLETE`

Batch 2 implements a pure internal case-execution corridor. The corridor accepts
the exact Batch 1 origin and provenance, typed case inputs and a recovery plan.
It accepts no caller-supplied case result and no caller execution, finding or
match boolean.

## Trusted execution boundary

`AtomicTransitionTrustedCaseExecutionCorridor::executeCase()`:

1. fail-closed validates the exact Batch 1 origin and provenance;
2. requires the case and recovery plan to share the origin's exact root;
3. resolves the supplied plan to the origin's exact sealed recovery-plan
   reference;
4. invokes the existing deterministic case executor internally;
5. independently validates the returned internal result seal and required
   execution state;
6. refuses an observed result that does not match the sealed expectation; and
7. emits one new provenance-bound result whose identity is derived from the
   provenance, case and internal-result digests.

The method has no result, `case_executed`, `finding_derived` or
`expectation_matched` input parameter. Those fields are produced only after
internal deterministic execution.

## Provenance-bound result

`imperium.imperator.atomic-transition-provenance-bound-case-result/v1` binds:

- the exact execution-provenance and evidence-origin references;
- experiment, disposable mission and replay/contention root;
- source commit/tree, build artifact/dependency lock and executor identity;
- the origin's complete case-set root;
- exact case, plan, fixture, mutation and expected-result references;
- the internally derived result digest and all observed findings; and
- explicit `caller_result_accepted=false` plus the complete read-only,
  non-authority perimeter.

The result is not an operational receipt, aggregate disposition, complete
case-set proof or campaign-closure input. Later consumers must accept only this
provenance-bound schema and must reconstruct its exact predecessors.

## Current proof limit

The Batch 1 origin and provenance remain caller-constructible contract records.
Batch 2 makes caller results structurally absent from this corridor, but it does
not yet derive the executor's actual recursive dependency-capability graph or
authenticate a disposable real-mission run. The material provenance defect
therefore remains open.

## Closed perimeter

Execution is pure in-memory deterministic case evaluation only. Batch 2 writes
no runtime state, persists no journal, acquires no live lock, issues or consumes
no authority, admits no live transition, adopts no successor and changes no
binding state. It creates no durable winner or operational receipt.

It derives no complete dependency-capability graph, claims no complete-chain
secret exclusion, runs no disposable real mission, repairs no historical audit
and removes no closure qualification. It handles no live credential or
capability, invokes no provider, performs no external I/O and opens neither Iron
Gate nor Lazaretto.

The controlling posture remains
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_EVIDENCE_PROVENANCE_DEFECT`.
