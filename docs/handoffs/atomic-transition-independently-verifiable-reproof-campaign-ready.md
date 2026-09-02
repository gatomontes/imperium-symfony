# Atomic Transition Independently Verifiable Reproof campaign ready

## Status

`ATOMIC_TRANSITION_INDEPENDENTLY_VERIFIABLE_REPROOF_CAMPAIGN_READY`

The v1 package remains refused at
`CAMPAIGN_TERMINATED_INDEPENDENT_VERIFICATION_EVIDENCE_INSUFFICIENT` because its
acceptance-case inputs were not retained. The controlling posture remains
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`.

This campaign prepares a distinct v2 disposable proof. It does not repair or
replace the v1 receipt. Only Preparation Batch 0 may next be considered.

## Required sources

Read before Preparation Batch 0:

1. `docs/next-campaign-atomic-transition-independently-verifiable-reproof.md`
2. `docs/handoffs/atomic-transition-evidence-independent-verification-remediation-complete.md`
3. `docs/atomic-transition-evidence-independent-verification-remediation-batch-4-local-refusal.md`
4. `docs/atomic-transition-evidence-independent-verification-remediation-preparation-inventory.md`
5. `docs/atomic-transition-evidence-provenance-operational-proof-remediation-batch-5-integrated-disposable-mission.md`
6. `docs/evidence/atomic-transition-integrated-disposable-proof-1-sanitized.json`
7. `src/Imperium/Runtime/Imperator/AtomicTransitionEvidenceAdversarialCaseContract.php`
8. `src/Imperium/Runtime/Imperator/AtomicTransitionTrustedCaseExecutionCorridor.php`
9. `src/IndependentVerification/AtomicTransitionArtifactAndReceiptVerifier.php`
10. `src/IndependentVerification/AtomicTransitionIndependentVerificationAdmissionConsumer.php`
11. `tests/Imperium/Runtime/AtomicTransitionEvidenceProvenanceOperationalProofRemediationBatch5Test.php`
12. `tests/Imperium/Runtime/AtomicTransitionEvidenceIndependentVerificationRemediationBatch2Test.php`
13. `tests/Imperium/Runtime/AtomicTransitionEvidenceIndependentVerificationRemediationBatch5Test.php`
14. `docs/delegate-mission-flow.md`
15. `todo/blackquill-todos.md`

## Preparation Batch 0 deliverables

Inventory every missing case input, expectation and observation; current
runner/receipt/summary/verifier/admission schemas; producer/verifier sharing;
public, operator-local and forbidden fields; provenance derivation; secret
exclusion; interruption and replay states; execution/receipt/signing custody;
closure consumers; and the smallest ordered v2 sequence.

Produce a versioned preparation inventory, completion handoff, boundary tests,
and flow and Blackquill-ledger updates. Do not implement the proposed contracts.

## Hard boundary

Do not inspect private operator-local material, rerun or execute a mission,
execute a verifier, create or use signing material, invoke a provider, perform external I/O,
handle live credentials or capabilities, mutate runtime state,
change provider binding, implement v3 execution admission, retain private
evidence, repair or replace v1, admit v2, remove the qualification or close the
campaign.

Batch 5 execution, Batch 6 verification/signing and Batch 8 terminal audit each
remain separately authorized and separately sequenced boundaries.
