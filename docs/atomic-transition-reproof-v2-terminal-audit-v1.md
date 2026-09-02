# Atomic Transition Reproof v2 terminal audit v1

`REPROOF_BATCH_8_TERMINAL_AUDIT_PASSED_BOUNDED_INDEPENDENT_REPROOF`

`CAMPAIGN_CLOSURE_ACCEPTED_AFTER_INDEPENDENTLY_ATTESTED_REPROOF`

## Authorization, subject and decision

The operator separately authorized this audit with `batch 8 approved`.
Review began from clean merged main
`7318ab23c9f14db06bcb2da6844225206e273f57`. The ancestry check for Batch 7
`0a33113` succeeds. This versioned documentary terminal decision follows the
actual public admission; it is not a new independent verification report or
detached attestation. Its authority to adjudicate this campaign comes from the
separate operator instruction and the reviewed evidence, not a self-seal.

**Accepted for the finite eight-case internal disposable proof.** The v2 chain
retains the operands missing from v1, independently derives their semantics,
and binds the authorized execution, separate verification/signing and public
admission. No material independent-verification defect was found within the
declared scope. No stages remain in this campaign.

The prior posture
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`
is superseded for this new v2 closure only. Its historical record remains.
V1 remains refused at
`CAMPAIGN_TERMINATED_INDEPENDENT_VERIFICATION_EVIDENCE_INSUFFICIENT`.
V2 does not reconstruct, replace, repair or rehabilitate the v1 receipt.

No runtime closure consumer is introduced or re-enabled. This is a repository
audit decision, not an execution capability or a generic closure API. The sealed
report and admission retain `qualification_removed=false` and
`campaign_closed=false`: those records correctly describe their earlier stages.
The terminal decision does not rewrite them into terminal evidence.

## Exact public evidence and provenance

The audited event is `reproof-v2-20260902-proof-2`. These are canonical record
digests unless expressly identified otherwise; none grants private intake.

| Binding | Value |
| --- | --- |
| Approved source commit | `2b5cb56c8ae60d80b628311377f929830401ca3e` |
| Source manifest root | `7867dede38bca0f4aace144868338c22d486d1e05467877407b9fa95bc9674d7` |
| Execution request canonical SHA-256 | `b3595f520434d4db6ce035910795fd20c96dded4456cb3a719bf166a624de4de` |
| Verification/signing request canonical SHA-256 | `11731fa32c45d2731f1a961d4be5d492d3b34b6573fd072dbb444dea80393f9b` |
| Signing controller raw SHA-256 | `dd15523c8515ae8ec3842dd7b470205310d98310ee9b893d801992b9a67a02b4` |
| Independent verifier implementation root | `ea2925e14c23c2bfe9346375597f446c7c28b3c1ff4ae9d492a999f1340d883d` |
| Private receipt commitment only | `36f84646ed977eaaa7bf45803ce4ae326f174f2d43405d7dbfba7b02f339cbfb` |
| Public candidate | `cc86f24082e3d254e6802e8d81f675e334f4577790f92997d088b4c0c64fb3ab` |
| Public identity | `20aa9c1971635894a685586026ffe0a4b4139939d3abc90dc8b93abc8f485efa` |
| Independent report | `c44946f3627cb0728b9208e19f961c0ec08930a4a79daa080d320e979f7f39b0` |
| Detached attestation | `8f9a934b3d74db8d9b7b826fbe1110684b03277aa1d187a3cf1c5491ef5161db` |
| Operator-provisioned trust anchor | `25248ea1624c3ba315e59ead45d1a88fb6ca80f3f4a2ef03995e52e7674addb0` |
| Repository admission | `d2048a13c5b01ebf8d20ae85a885976b1487343778bbe3b6ec17f00771622dc1` |

The six public files are
`docs/evidence/atomic-transition-reproof-v2-proof-2-{candidate,identity,report,attestation,trust-anchor,admission}.json`.
Their fields bind the same source, receipt and ordered input/expectation/result
roots. The separately approved requests remain immutable requests. Approval
records and completion handoffs establish which requested actions actually
occurred; request hashes by themselves prove neither authorization nor execution.

## Findings and adversarial assessment

| Finding | Evidence and attempted bypass | Verdict |
| --- | --- | --- |
| B8-01: retained acceptance operands | CaseProfile retains primary/comparison records, auxiliary payloads, plans and mutation replacements for all eight ordered cases. Runner owns observed results through the runtime classifier/validator. CaseEvaluator independently validates full transaction shapes, joins, cuts, false effect fields and replacement relations, then derives classification/directive/comparison/findings and all ordered roots. Batch 4 counterfeits reseal the changed chain and must reach semantic evaluation. Missing, duplicate, reordered or substituted cases refuse. | PASS for the finite profile; labels or matching seals alone are insufficient. |
| B8-02: computational independence | The verifier's four semantic/source/exclusion classes share only Records and CanonicalJson. Its explicit six-file loader has no producer evaluator, classifier, validator, profile, graph or exclusion dependency. The raw approved verifier root matches merged source. Independent source parsing checks commit/tree/blob membership; independent scanners exercise safe negative vectors. | PASS within the reviewed implementation closure; separate wrappers around producer conclusions are not accepted. |
| B8-03: provenance and source | Exact request pins, clean-source Batch 5 custody and finalized package commitments join the report and admission. Terminal tests independently recompute the 17-file manifest from local immutable Git blobs and the six-file verifier root, and compare approved and reviewed source bytes. Origin/source/result roots are derived in verifier source, not imported as unexamined success flags. | PASS with trusted local execution provenance; hashes alone do not prove an execution happened. |
| B8-04: execution and signing custody | Batch 5 ran once under its separate exact-source approval. Batch 6 used a separate controller/process and fresh operator-only custody established before receipt intake; identity/trust creation precedes verification, and all eight domains must pass before signing. The controller/request hashes match the prior approval. The runner neither selects a trusted key nor invokes signing. | PASS on recorded operator-controlled custody and pinned source. No claim of independent human institutions, hardware custody or hostile-host isolation. |
| B8-05: public admission and time | The actual detached Ed25519 signature covers the versioned purpose, NUL and exact report digest. The pinned anchor precedes Batch 7; caller replacement is refused. Public joins and historical admission reproduce exactly at `2026-09-02T19:00:04Z`, within identity validity `2026-09-02T18:52:54Z` to `2026-09-03T18:52:54Z`. Altered records, unsigned/synthetic/indeterminate reports, wrong key/purpose and invalid times refuse. | PASS for that archival admission. Later expiry does not invalidate its historical timing; a fresh admission at or after expiry refuses. |
| B8-06: publication and replay | Reservation precedes case execution; exclusive writes and finalization bind the complete pair. Seven synthetic interruption states refuse consumption and re-reservation. No automatic retry or reconstruction of lost execution is authorized. | PASS for bounded package publication and semantic replay. Physical power-loss durability and simultaneous-process lock exclusion were not proved. |
| B8-07: closure bypasses | All three historical boolean/self-recomputed/unsigned closure methods still throw before dependencies are read and remain container-excluded. V1 verifier acceptance remains INDETERMINATE; its reconstructor/admission cannot close, and its terminal auditor emits refusal only. V2 admission remains pending-terminal and container-excluded. Direct refusal tests pass. | PASS. Historical accepted vocabulary and commented code create no operational closure path. |

## Mandatory limits retained by acceptance

The graph scope remains `PINNED_EXPLICIT_LOADER_AND_SOURCE_IMPORTS`, with
`PHP_NATIVE_RUNTIME_TRUSTED`, `GIT_READ_ONLY_CAPTURE`,
`LOCAL_PACKAGE_WRITER_ONLY` and `NO_VENDOR_BOOTSTRAP`. It is the fixed loader
and source-import projection, not a universal runtime capability graph.
Composer lock bytes identify source inputs; no installed vendor-tree attestation
is claimed. Native PHP/Git, the local clock, operator custody and absence of
hostile concurrent source replacement are trusted infrastructure assumptions.

Exclusion combines exact finite payload schemas and independently validated
synthetic auxiliary records with bounded nested/split/hex/base64/percent checks
and safe refusal observations. Decoding is bounded to three layers; it is not
a universal detector for arbitrary secrets or encodings. Pinned public source
bytes form a separate reviewed domain. Operational receipt availability and
current private ACL state were not re-inspected during this audit; custody
findings rely on the previously authorized execution records and attestation.

The eight case names denote inert snapshot semantics. In particular,
same-root contention compares retained records; it does not establish a live
concurrent lock. Publication cuts are synthetic incomplete-file states, not
physical crash tests. Neither limit was introduced to excuse a failed proof:
both were declared before execution in the preparation and Batch 1–4 documents.

`BOUND_INACTIVE`, required v3 execution admission `NOT_IMPLEMENTED`, and
`UNKNOWN_REPLAY_PROHIBITED` remain binding. No provider, activation, credential,
live capability, runtime mutation, retry or continuing authority follows from
campaign closure. No private receipt or signing material was inspected during
Batch 8. No mission or operational verifier was rerun, no signature was created,
and no external I/O was performed.

## Reading ledger and validation

The Batch 8 ready handoff, campaign plan and Preparation Batch 0 inventory were
read, together with the v2 contracts, runner, verifier and counterfeit proof;
Batch 5/6 approvals and immutable requests; Batch 5/6/7 completion handoffs;
all six public evidence files; all `src/ReproofV2/` and v2 independent source,
both CLI files and Batch 1–7/readiness/synthetic-fixture tests. Review also
covered the v1 local refusal, terminal refusal auditor, disabled closure
consumers, independent reconstructor, v1 verifier/admission, service exclusions,
Delegate flow, handoff index and Blackquill ledger. No private locator in a
request was followed. The original missing `1ac9ede` ancestry was not recast as
success; Preparation Batch 0 retains the operator-approved `3c4f8b2` substitute.

Batch 8 adds public-chain/source-pin, direct legacy-refusal, v1 preservation and
documentary terminal-boundary tests. The cumulative campaign regression set
also exercises independent synthetic case derivation, counterfeit substitutions,
publication cuts and the actual public signature without operational private
intake or signing. PHP 8.4.14 / PHPUnit 13.3.0 passes **111 tests, 1090 assertions**
across Batches 1–8 and related campaign/classifier regressions. The Batch 8
checks contribute seven tests and 212 assertions. No implementation correction
was required by this audit.

Completion handoff: `docs/handoffs/atomic-transition-reproof-v2-campaign-complete.md`.
