# Provider Activation-Consumption Remediation Preparation Batch 0 complete

## Result

PREPARATION_BATCH_0_COMPLETE_COMBINED_WINNER_SELECTED_NO_RUNTIME_CHANGE

The correction is a separately versioned v2 governed admission whose one immutable record is the
combined consumption winner for one exact activation and its exact durable execution authority.

The winner and lock are activation-keyed. Exact replay returns the same combined record. A different
authority referencing the same activation refuses under that lock before credential resolution.
A separate pre-admission activation-consumption write is rejected because a crash could spend the
activation without the combined winner. Existing v1 admissions remain immutable historical evidence.

## Next gate

Only remediation Batch 1 may next be considered: v2 combined-admission contract definition and exact
activation-revocation input definition. No producer or consumer change is authorized.

No runtime contract was defined and runtime behavior is unchanged. No activation, principal or
binding was activated or consumed; no authority was issued or consumed; no credential or capability
was handled; no provider was invoked; no external I/O occurred; and Iron Gate and Lazaretto remain
closed.

Estimated remediation countdown after this merge: four batches.
