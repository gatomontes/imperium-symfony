# Atomic Transition Evidence Independent Verification Remediation Batch 3

## Result

`BATCH_3_SYNTHETIC_COUNTERFEIT_AND_DETACHED_ATTESTATION_REFUSAL_PROVED`

Synthetic tests prove that an internally self-hashed retained-v1-shaped package
cannot be attested while any required domain is indeterminate; producer
conclusion injection and secret-bearing receipt material refuse; substituted
artifact bytes refuse; and detached verification rejects wrong identities,
report bindings and malformed signatures.

The test signing material is deterministically synthetic, exists only in test
process memory and is zeroed after use. No retained receipt or live signing
capability is touched.

Only Batch 4 explicitly authorized local verification and detached signing may
next be considered. The retained v1 acceptance evidence gap already predicts an
indeterminate outcome and must not be repaired by a mission rerun.
