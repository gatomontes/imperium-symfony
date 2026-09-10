# Provider Onboarding O0 — independent F1/F2 closure review

**Verdict: F1_F2_ACCEPTED_AT_DRAFT_CONTRACT_SCOPE. O0_OWNER_DECISIONS_AND_EVIDENCE_PENDING.**

The two targeted interface findings from the preceding authority review are closed at the documentation/specification level. No further F1/F2 correction pass is required by this review. This is not acceptance of an implementation, approval of the proposed competence allocation, or a declaration that the whole O0 interface is frozen. O0 completion still requires the outstanding owner decisions, exact evidence/policy bindings and final review.

CY/FC remain accepted and integrated within their original local/offline software scopes. O1–O5, live commissioning, activation and enrollment remain deferred. `DEFER_ENROLLMENT`, unresolved B1 and all five false operational flags remain unchanged. Nothing was pushed, merged, activated or enrolled during this review.

## Verified identity and scope

| Item | Verified identity |
| --- | --- |
| Reviewed entry | `bbdacecfa95f5e25ff8389fcec092511f2b8300f` |
| Entry tree | `825bca3f5a0e65f850b5b2d3cff7b870a8cefdd5` |
| Submitted candidate | `aacb8b1da3006e2c669e50f32ab422c7dd4c7988` |
| Candidate tree | `f8d145eb1abe114e1ed0201f4285aed3f44f3a56` |
| Campaign-selection ancestor | `6adc635ed9d89bbff7b472cc050de4c6e86d418c` |
| CY integration ancestor | `79282773b0c1ca93aa17303d46e784b5415cad7e` |
| Freeze ZIP SHA-256 | `7193669f570c4167fc7b6ffa51079c366517c6c491332752db5ee650421e3fa7` |
| Preserved authority-review SHA-256 | `10695f0fae76d698b3c47f315157904564ddc34d78b4c5f4c863c853b17316fe` |

The continuation changes exactly six Markdown paths: the new continuation contract, revised main contract and authority proposal, an owner-sheet status pointer, the new submission report, and the preserved previous independent review. The actual diff has 506 insertions and 67 deletions. All deletions are within the revised draft documents; the former ambiguous command/claim definitions are replaced rather than left as competing interfaces.

Cumulatively, eleven Markdown paths differ from campaign selection. Executable source, tests, configuration, dependency files and historical CY/FC acceptance remain unchanged.

## Checks rerun versus evidence inspected

I reran archive SHA-256 verification, safe ZIP-path inspection and the inspected payload verifier. In the disposable repository retained from the prior independent reviews, I verified the bundle prerequisite, imported its candidate locally, and checked actual entry/final commit bytes, tree identities and ancestry. Both continuation and cumulative diffs and the changed-path list match Git byte-for-byte; `git diff --check` passes.

All 82 source snapshots match candidate Git blobs. All 76 marked unchanged also match the reviewed entry. The three standalone documents match their committed counterparts exactly. The embedded authority-review ZIP matches its original upload, and the copied authority independent review matches the file delivered in the preceding review exactly. This preserves the preceding packet chain, including the earlier embedded O0 packet.

I independently checked the 35 recorded local links, two method references, changed-document links and fenced blocks, the twelve unique effect names and their correspondence with D2, and removal of the conflicting prior command/application fields. These checks passed.

I inspected the package-builder source and the supplied eight precommit and 264 final command records, all reporting exit 0. The final transcript records a clean branch. Actual candidate content and diff have no undeclared tracked change. The original Windows worktree's cleanliness and the claim that later work was packaging-only remain supplied observations, not a fresh inspection of that machine or unsubmitted changes.

The packet's `f1-f2-static-checks.json` correctly qualifies its checks as specification presence/consistency checks. My semantic assessment below is a review of the written transition rules, not an executed state-machine or concurrency test. No PHP service, application CLI, PHPUnit test, provider request, credential probe or installed/private-state read was performed. No new public provider facts were adopted in this pass; previous provider observations keep their prior attribution and limitations.

## F1 disposition — closed at draft-contract scope

The continuation contract now defines a stable sequence, separate per-command retry identity and globally one-use step/slot identity. It explicitly includes expected head and predecessor in the command fingerprint.

An exact duplicate recognizes the original immutable admission result before fresh head/expiry checks and never re-enters the action. A changed command under the same ID conflicts. Forward progress uses a new command ID, the current aggregate head and exact logical predecessor, while retaining the same admitted policy and budget. The immutable predecessor ref does not change when later completion evidence arrives.

The written rules now account for the cases raised by F1:

| Case | Specified outcome |
| --- | --- |
| Known completed call, more authorized work remains | Next ready step advances under existing consent with a new command ID |
| Same ID with changed head or step | Conflict; original result remains intact |
| Stale new command | Refusal without step/budget consumption; obtain current status before a new command |
| Duplicate after head advancement or policy expiry | Historical recognition only; no renewed authority |
| Reserved, pending or unknown predecessor | No overtaking or fresh-ID bypass; preserve exposure |
| Resume updates recognition metadata | Aggregate head may advance, but logical predecessor does not; no new dispatch or uncommitted assignment |

This resolves the previous ambiguity between replay, continuation and evidence-only resume. Runtime implementation must still prove those properties, including crash behavior and shared accounting.

## F2 disposition — closed at draft-contract scope

The authoritative registry now contains all twelve proposed effects, including `REVOKE_BOOTSTRAP`, with explicit permitted supply: signed originals, batches of independently signed originals, or exact policy-derived effects. A batch supplies no extra competence. Policy-derived effects invoke no runtime signer and are permitted only where the registry and the admitted owner policy both allow them.

Conditional application now records tagged policy authority and its one-use slot instead of a fabricated APPLY signature. Explicit application records the genuine signed act. Access claims bind an actual infrastructure executor, custody and access grant; assessment claims bind an actual Augur holder and commission. Lease variants follow the same separation, with mixed fields rejected.

The revised D2 fields agree with the new variants. Recruiter qualification and Operator cutover remain distinct signed steps; completed prerequisite evidence is recognized without reconsuming its original authority. Each external call has its own predeclared one-use slot/grant, closing the risk of splitting grant admission and dispatch into competing consumers of the same right.

These are accepted design clarifications, not an owner delegation or evidence that any new issuer, trust store, verifier or consumer is operational.

## Remaining gate and next executable action

The owner-sheet change adds a status pointer only. No provider, installation mode, budget, role scope or competence choice has been approved by these uploads or this review.

The next action is to record the actual owner decisions, then prepare the corresponding exact public artifacts and final policy/provider appendix:

| Decision or prerequisite | Required next input |
| --- | --- |
| D2 | Accept or amend the proposed competence allocation and select conditional application A or separate exact application B. Conditional A remains my recommendation for the stated persistent-consent experience |
| D1 | Choose the first provider/API-key scope; complete that provider's exact adapter/model/configuration/destination/usage appendix |
| Installation mode | Establish the intended fresh or existing route using separately lawful public evidence. An absent seal or a chosen label does not prove fresh founding authority |
| D3 | Accept or amend workload, capability thresholds, evidence freshness, budgets/time/calls, target roles and revision-alias treatment |
| Evidence binding | Resolve actual instance, trust, incumbent, Profile, candidate/configuration and source references; instantiate the policy's finite steps/slots and workload with exact retained bindings |
| D4/B1 | Keep the separate live cost/time/cancellation gate unresolved until its own evidence or explicit amendment is reviewed |

Do not ask the owner to design schemas or invent hashes. Once the choices are supplied, the executor should prepare concrete policy and artifact bindings, disclose any remaining evidence gap, and return them for final O0 review. That preparation does not authorize private-state access, trust enrollment or provider calls.

No further corrective campaign follows from this F1/F2 review. Final O0 acceptance, implementation selection and live commissioning remain separate gates. Preserve this report alongside the exact candidate and prior evidence; do not automatically publish or merge it.
