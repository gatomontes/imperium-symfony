# Atomic Transition Evidence Independent Verification Remediation Batch 2

## Result

`BATCH_2_SEPARATE_READ_ONLY_VERIFIER_COMPLETE_RETAINED_V1_MATRIX_INDETERMINATE`

A standalone verifier under `App\IndependentVerification` now hashes exact
artifact bytes, validates the private receipt and its nested seals, binds source,
origin, provenance, result and dependency graph, derives a bounded non-authority
perimeter, applies an independent sanitization scan and emits only a sanitized
domain report. It imports no producer runner, prior reconstructor or closure
service and accepts no producer disposition.

The retained v1 receipt contains the producer acceptance-matrix conclusions but
not the eight transient journal/winner/receipt case inputs. The verifier
therefore derives `INDETERMINATE` for that domain. It does not recreate the
producer's transient fixtures or treat copied values as observations.

Only Batch 3 synthetic counterfeit and substitution refusal proof may next be
considered. The controlling posture remains
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`.
