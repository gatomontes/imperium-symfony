# Canonical Native Effect Process Custody and Formal Closure Remediation — Batch 2 process-bound custody v1

`BATCH_2_PROCESS_BOUND_CUSTODY_AND_TRANSFER_REFUSAL_COMPLETE`
`BATCH_3_NOT_STARTED`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

Continuation custody now binds the actual runtime PID, an issuer-owned random
nonce, the exact issuer registry and the exact capability object. The
authority-supplied execution-boundary ID remains provenance only.

Issuer, capability, admission outcome and process-incarnation source explicitly
throw on serialization, unserialization and cloning. A Unix fork inherits
memory but changes PID, causing recognition to fail. A fresh process or issuer
has a different nonce/registry, and PID reuse in a new interpreter cannot
restore the old nonce. Missing or changed PID fails closed.

This batch does not change `execute()` recovery branches, introduce a
reconciliation claim, invoke a provider double, or alter service wiring.
