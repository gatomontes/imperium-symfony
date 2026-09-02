# Atomic Transition Reproof v2: Batch 5 execution approval boundary

`REPROOF_BATCH_4_COMPLETE_AWAITING_EXACT_SOURCE_EXECUTION_APPROVAL`

Read the preparation inventory, v2 contracts, runner, verifier and counterfeit
proof documents, plus `docs/handoffs/atomic-transition-reproof-v2-implementation-progress.md`.
The operator requested uninterrupted completion where possible. Batches 1–4
are complete and tested. This handoff makes the remaining execution request
concrete; it does not assert that approval or execution already happened.

## Proposed single event

- Source: the exact full commit and manifest root reported with this handoff.
  Use detached `E:/htdocs/imperium-reproof-v2` with raw LF Git blob bytes.
- Runner: `tools/run-atomic-transition-reproof-v2.php`, invoked with `php -n`.
- Profile: `eight-retained-disposable-cases/v2`.
- Proof ID/disposable root: `reproof-v2-20260902-proof-2`.
- New private output parent: `E:/ai/imperium-reproof-v2-private`.
- New package child: `reproof-v2-20260902-proof-2`, exclusively reserved once.
- Outputs: `reservation.json`, `receipt.json`, `candidate.json`, `finalized.json`.
  Nothing from this directory is uploaded, committed or printed as private bytes.
- Authority: one internal disposable case evaluation and local evidence write;
  no provider, network, credential, signing or live runtime-state access.

The final approval request supplies an exact command and SHA-256 commitment to
this event scope with its actual source commit. That commitment identifies the
request. Only the operator's affirmative approval supplies authorization; the
hash alone does not. Record that approval separately and bind the approved
request digest into the origin. Do not substitute a synthetic authorization.

Before execution, verify clean exact HEAD, raw-source membership and no existing
event reservation. Create only the named output parent if absent. Any failure
after reservation is unknown/incomplete; do not rerun, erase or overwrite it.
No v1 receipt or output filename is reused. Retain the new private package for
the separate Batch 6 intake; retention availability is not yet established.

After successful Batch 5, run its focused PHPUnit checks, retain only safe
candidate commitments in the repository, and prepare the distinct Batch 6
request. Batch 6 must pin independently trusted verifier bytes/source/custody
and a purpose-bound public identity before verification/signing. No key path,
key generation, secret or signing capability has been selected or used here.
Unsigned or synthetic reports cannot be admitted. Batch 7 is admission only;
Batch 8 must start separately from merged Batch 7 main.

`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`
remains controlling. V1 remains refused and unchanged. `BOUND_INACTIVE`,
`NOT_IMPLEMENTED` and `UNKNOWN_REPLAY_PROHIBITED` remain binding. The campaign
is open with four stages remaining.
