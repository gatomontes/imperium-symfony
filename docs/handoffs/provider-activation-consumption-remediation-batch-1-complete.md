# Provider Activation-Consumption Remediation Batch 1 complete

## Result

BATCH_1_V2_COMBINED_ADMISSION_AND_REVOCATION_CONTRACTS_DEFINED

A separately versioned v2 combined admission now requires exact activation consumption and exact
durable-authority consumption in one immutable pre-resolution, pre-I/O winner.

An append-only activation-revocation contract supplies the missing revocation input without mutating
the immutable activation. Admission and revocation use the same activation-keyed lock vocabulary.

The v1 admission contract and existing evidence remain unchanged.

## Next gate

Only Batch 2 may next be considered: v2 activation-keyed combined-admission production plus the
minimum append-only activation-revocation producer under the shared lock.

Batch 2 may not migrate stationary credential resolution, handle a credential or capability, invoke
a provider, perform external I/O, send an outbound byte, authorize retry, migrate a live command,
open Iron Gate or Lazaretto, or claim provider outcome.

No producer or consumer changed in Batch 1. No activation, principal or binding was activated or
consumed; no authority was issued or consumed; and runtime effects remain unchanged.

Estimated remediation countdown after this merge: three batches.
