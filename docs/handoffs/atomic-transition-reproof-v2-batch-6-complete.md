# Atomic Transition Reproof Batch 6 complete

`REPROOF_BATCH_6_INDEPENDENTLY_VERIFIED_AND_ATTESTED_PENDING_ADMISSION`

The operator separately approved the reviewed Batch 6 request. The approval is
recorded in `docs/atomic-transition-reproof-v2-batch-6-operator-approval.md`.
The controller/request pins matched before execution. A fresh signing directory
was created with inheritance disabled and access limited to the operator and
SYSTEM; its ACL was checked before the controller ran. No existing key was read.

The exact controller ran once and exited 0 with
`REPROOF_V2_VERIFIED_AND_ATTESTED_PENDING_ADMISSION`. It created the fresh
purpose-bound identity and external trust record before private receipt intake,
independently verified the exact new v2 package, and signed only after all eight
domains passed. Detached verification also passed. No retry occurred.

Only allowlisted public records were projected for repository review:

- `docs/evidence/atomic-transition-reproof-v2-proof-2-identity.json`
- `docs/evidence/atomic-transition-reproof-v2-proof-2-report.json`
- `docs/evidence/atomic-transition-reproof-v2-proof-2-attestation.json`

The identity is valid from `2026-09-02T18:52:54Z` to `2026-09-03T18:52:54Z` for
`imperium.atomic-transition-reproof.independent-report/v2` only. Its record
digest is `20aa9c1971635894a685586026ffe0a4b4139939d3abc90dc8b93abc8f485efa`;
the independently provisioned public-key digest is
`b7d3b1ffeee65cbf527bad8030478c22f1035937ca4b8424f0c66ca10ce57b7a`.
The report digest is `c44946f3627cb0728b9208e19f961c0ec08930a4a79daa080d320e979f7f39b0`;
attestation digest is `8f9a934b3d74db8d9b7b826fbe1110684b03277aa1d187a3cf1c5491ef5161db`.

`docs/evidence/atomic-transition-reproof-v2-proof-2-trust-anchor.json` records
the separately approved provisioning/source/controller/candidate/report pins.
It is an operator provenance record, not a producer-supplied trust anchor. The
private key, private receipt and local trust file remain outside Git. No private
receipt or key bytes were printed. Native infrastructure trust and the limited
graph scope remain explicit; this is not a hostile-host or hardware-custody claim.

Two stages remain: strict public repository admission, followed by the separate
terminal audit from merged Batch 7 main. The report has qualification_removed=false
and campaign_closed=false. V1 remains refused and unchanged.
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`
remains controlling. `BOUND_INACTIVE`, `NOT_IMPLEMENTED` and
`UNKNOWN_REPLAY_PROHIBITED` remain binding. The campaign remains open.

PHPUnit after Batch 6: **80 tests, 827 assertions** across Batches 1–6,
readiness checks and related regressions. Public-record tests verify the actual
detached signature, all eight PASS domains, pinned joins, finite identity
validity, and rejection of report/purpose/signature substitution. They create
no new signatures and access no private keys. Batch 7 admission is next under
the operator's campaign-completion instruction; it cannot close the campaign.
