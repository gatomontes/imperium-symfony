# Principal Activation Decision Authority Provenance Remediation Batch 4 complete

## Result

BATCH_4_READ_ONLY_AGGREGATE_RECONSTRUCTION_COMPLETE

The exact offline provenance chain now reconstructs read only as ELIGIBLE,
INCOMPLETE, CONFLICTED or REFUSED. Reconstruction persists and repairs nothing.

## Next gate

Only remediation Batch 5 may next be considered: a separately authorized scope
remediation producer that consumes one exact Operator Root scope grant into one
pending successor generation, requires a separate lifecycle activation, and
then consumes one decision-issuance authorization into the exact activation
decision and its single-use activation authority.

The production boundary must preserve atomic winners, exact replay,
changed-evidence conflict, expiry and revocation refusal, secret exclusion and
read-only reconstruction. Scope-grant consumption, successor commit, lifecycle
activation and decision/authority issuance remain separate ordered transitions.

## Preserved perimeter

Batch 5 may not activate a provider binding, handle a credential or capability,
invoke a provider, perform external I/O, authorize retry, migrate a live
consumer, or open Iron Gate or Lazaretto. It may not create an activation
decision except through the one exact decision-issuance authorization, and that
decision may not itself activate the provider executor principal.

Provider Effect Principal and Binding Activation remains paused.
UNKNOWN_REPLAY_PROHIBITED remains binding.

Estimated remediation countdown: approximately three batches.
