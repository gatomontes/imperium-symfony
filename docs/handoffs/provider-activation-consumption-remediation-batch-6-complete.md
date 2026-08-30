# Provider Activation-Consumption Remediation Batch 6 complete

## Result

BATCH_6_STATIONARY_RESOLUTION_REQUIRES_COMBINED_V2_WINNER

Stationary credential resolution now has a v2 route that accepts only the exact combined activation
and authority consumption winner. V1 admission IDs are rejected as historical evidence.

The fixed callback-local proof remains secret-free, capability-free, provider-free and no-I/O.
Exact proof replay does not reread the credential.

## Next gate

Only remediation Batch 7 may next be considered: adversarial proof and repeated terminal audit.

Batch 7 may not invoke a provider, perform external I/O, send an outbound byte, authorize retry,
migrate a live command, open Iron Gate or Lazaretto, or claim provider outcome.

Estimated remediation countdown after this merge: one batch.
