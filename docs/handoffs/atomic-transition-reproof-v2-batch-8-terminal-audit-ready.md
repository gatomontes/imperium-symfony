# Atomic Transition Reproof v2: separate terminal audit ready

`REPROOF_BATCH_7_COMPLETE_AWAITING_SEPARATE_TERMINAL_AUDIT`

Batch 7 has admitted the exact independently attested public evidence while
leaving qualification removal and campaign closure false. Start Batch 8 only
under its separate operator authorization, from clean merged Batch 7 main.
The merge commit is supplied with the final continuation request after merging.
No terminal audit result is asserted by this readiness handoff.

Required sources:

- The campaign plan and Preparation Batch 0 inventory.
- `docs/atomic-transition-reproof-v2-contracts.md`.
- `docs/atomic-transition-reproof-v2-runner.md`.
- `docs/atomic-transition-reproof-v2-verifier.md`.
- `docs/atomic-transition-reproof-v2-counterfeit-proof.md`.
- Batch 5 and Batch 6 operator approvals and their immutable request JSONs.
- Batch 5, Batch 6 and Batch 7 completion handoffs.
- `docs/evidence/atomic-transition-reproof-v2-proof-2-candidate.json`.
- `docs/evidence/atomic-transition-reproof-v2-proof-2-identity.json`.
- `docs/evidence/atomic-transition-reproof-v2-proof-2-report.json`.
- `docs/evidence/atomic-transition-reproof-v2-proof-2-attestation.json`.
- `docs/evidence/atomic-transition-reproof-v2-proof-2-trust-anchor.json`.
- `docs/evidence/atomic-transition-reproof-v2-proof-2-admission.json`.
- All v2 runner, verifier, admission and CLI source plus Batch 1–7 tests.
- The historical v1 refusal and disabled closure consumers, Delegate flow,
  handoff index, campaign steps and Blackquill ledger.

The audit must test the actual admitted chain, independent derivation and
custody joins, source/graph/exclusion limits, historical admission time, and
all bypass refusals. A signature or all-PASS label alone is insufficient.
Preserve v1 as refused; a new accepted v2 closure must not rehabilitate it.
Do not reopen private-key custody, sign again, rerun the mission, invoke a
provider, access live credentials, perform external I/O or mutate runtime state.
Use public admitted evidence and pinned source for this terminal review.

If a material defect remains, retain the qualification and record a refusal or
corrective boundary. Only a passing separately sequenced terminal audit may
consider `CAMPAIGN_CLOSURE_ACCEPTED_AFTER_INDEPENDENTLY_ATTESTED_REPROOF`.
Until then, the campaign remains open and
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`
remains controlling. No current readiness document grants closure authority.
