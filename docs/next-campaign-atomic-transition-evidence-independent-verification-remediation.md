# Next campaign: Atomic Transition Evidence Independent Verification Remediation

## Selection

`ATOMIC_TRANSITION_EVIDENCE_INDEPENDENT_VERIFICATION_REMEDIATION_SELECTED`

The prior authenticated-operational-evidence closure is requalified at
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`.
The retained package is pinned and tamper-evident, but its operational claims
are still accepted by comparing them with copied constants through the same
implementation family.

## Objective

Establish one genuinely separate, read-only verification boundary that derives
conclusions from the underlying repository artifacts and operator-local
receipt, emits only a sanitized report, binds that report to an explicit
detached trust anchor, and leaves a self-hashed counterfeit package
structurally inadmissible.

## Campaign sequence

1. **Preparation Batch 0 — independent-verification boundary inventory.** Requalify closure and inventory receipt availability/custody, producer/verifier code overlap, artifact recomputation, trust-anchor options, signature custody, sanitized-report requirements and every closure consumer.
2. **Batch 1 — verifier-input, report and detached-attestation contracts.** Define separately versioned authority-empty contracts for exact artifacts, private-receipt intake, derived observations, sanitized report, public verification identity and detached signature. Handle no receipt or signing key.
3. **Batch 2 — separate artifact-and-receipt verifier.** Implement a standalone read-only verifier that imports no producer reconstructor or terminal-closure service and independently recomputes commit/tree, lock, runner, mission, build, receipt structure, matrix, exclusion and non-authority conclusions.
4. **Batch 3 — counterfeit and substitution refusal proof.** Prove refusal of self-hashed counterfeit summaries, copied constants, altered or missing receipt sections, wrong source/build, substituted verifier, wrong public identity, malformed signature, secret leakage and producer-conclusion injection using synthetic fixtures only.
5. **Batch 4 — explicitly authorized local verification and detached signing.** The operator runs the verifier locally against the retained private receipt and exact source artifacts, using an operator-local signing capability. Only the sanitized verification report, detached signature and public verification identity may be retained.
6. **Batch 5 — repository-side independent verification consumer.** Verify the sanitized report and detached signature, re-derive its admissibility without producer booleans, bind the exact verifier implementation and refuse every legacy or unsigned closure path.
7. **Batch 6 — terminal Blackquill audit and conditional closure.** Permit closure only if the retained operational package survives the separate verifier and trust anchor, counterfeit packages remain inadmissible and no historical path can authorize closure.

## Local operator boundary

Batch 4 is the only planned batch that may inspect the retained private receipt
or use a live signing capability. It requires explicit authorization at that
batch. The private receipt and private signing material must remain local and
must never enter Git, ChatGPT, logs, exceptions or the sanitized report.

If the retained private receipt is unavailable or incomplete, the campaign must
stop and requalify the evidence. It may not silently rerun the mission or mint a
replacement receipt.

## Terminal acceptance

Only a successful Batch 6 may restore
`CAMPAIGN_CLOSURE_ACCEPTED_AFTER_INDEPENDENTLY_ATTESTED_OPERATIONAL_EVIDENCE`.

## Binding exclusions

Provider binding remains `BOUND_INACTIVE`. Required v3 execution admission
remains `NOT_IMPLEMENTED`. `UNKNOWN_REPLAY_PROHIBITED` remains binding. No
mission rerun, provider invocation, external I/O, live autonomy, runtime state
mutation, raw private evidence retention, private signing-material retention,
self-authorization or live command migration is authorized by campaign
selection.
