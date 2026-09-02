# Atomic Transition Reproof Batch 7 complete

`REPROOF_BATCH_7_PUBLIC_EVIDENCE_ADMITTED_PENDING_SEPARATE_TERMINAL_AUDIT`

The operator's campaign-completion instruction authorizes this repository
admission after the separately approved Batch 5 and Batch 6 events. Admission
uses only public evidence, with no mission retry, receipt intake or signing.

`ReproofV2AdmissionConsumer` pins the operator-provisioned trust anchor at
`25248ea1624c3ba315e59ead45d1a88fb6ca80f3f4a2ef03995e52e7674addb0`. It accepts
only this exact event's candidate, identity, all-PASS report and detached
attestation. It checks schemas/seals, every source/case/receipt/verifier/identity
binding, the public-key fingerprint, purpose, signature and the identity's
validity window at the trusted local admission time. Changing the anchor is
not a caller option. This is a narrow admission for the approved proof event,
not a general certificate registry or a producer-authenticated trust service.

The actual admission occurred at `2026-09-02T19:00:04Z`, within the identity's
24-hour window. Its record is
`docs/evidence/atomic-transition-reproof-v2-proof-2-admission.json`, digest
`d2048a13c5b01ebf8d20ae85a885976b1487343778bbe3b6ec17f00771622dc1`.
Disposition: `INDEPENDENTLY_ATTESTED_REPROOF_ADMITTED_PENDING_TERMINAL_AUDIT`.
It retains qualification_removed=false and campaign_closed=false, with
`BOUND_INACTIVE`, `NOT_IMPLEMENTED`, `UNKNOWN_REPLAY_PROHIBITED` and no continuing
authority. The consumer remains excluded from runtime service discovery.

The operator trust anchor was committed with Batch 6 before this consumer and
admission. The actual report/signature are checked in tests without private-key
access or new signatures. Resealed substitutions, unsigned/v1/producer/synthetic/
indeterminate routes, changed identity/purpose/source/receipt/verifier/case roots,
unknown payload fields and invalid admission times are refused. Expiry later
does not rewrite the fact that this admission was made within the valid window;
any new admission still needs a currently valid identity.

One stage remains: the separately authorized terminal Blackquill audit starting
from clean merged Batch 7 main. No terminal audit or closure decision was made
in Batch 7. V1 remains refused and unchanged. The controlling qualification is
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`.
The campaign remains open until that final gate is adjudicated.

PHPUnit after Batch 7: **104 tests, 878 assertions**, including the actual
detached evidence, 17 resealed admission substitutions, untrusted-anchor and
time-window refusals, retained admission replay at its historical timestamp,
earlier batch tests and related regressions. No test signs again or reads a
private key. Active next handoff:
`docs/handoffs/atomic-transition-reproof-v2-batch-8-terminal-audit-ready.md`.
