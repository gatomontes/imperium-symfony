# Provider Onboarding O0 — independent authority-design review

**Verdict: D2_DESIGN_REVIEWED_WITH_TARGETED_FREEZE_BLOCKERS.**

The submitted evidence and source identities verify. The authority proposal materially addresses the previous request: it identifies proposed issuers, records, stores, consumers, source fences and owner choices. Its direction is suitable for owner consideration. It is not yet a frozen implementable contract, and O0 remains incomplete. Two bounded interface clarifications below remain in addition to the declared owner decisions and genuine evidence dependencies.

CY/FC acceptance and integration remain unchanged within their original offline software scope. No corrective CY/FC campaign is required. O1–O5, live commissioning, enrollment and activation remain deferred; B1 is unchanged. This review performs no source edit, push, merge, authority issuance or runtime activation.

## Verified source and evidence

| Item | Verified identity |
| --- | --- |
| Prior reviewed entry | `b8021914bbcfdcd990949ea332b308f5c042cbbe` |
| Entry tree | `ec25528267f1b18bbe3b13356835d098e11cf63d` |
| New candidate | `bbdacecfa95f5e25ff8389fcec092511f2b8300f` |
| Candidate tree | `825bca3f5a0e65f850b5b2d3cff7b870a8cefdd5` |
| Campaign-selection ancestor | `6adc635ed9d89bbff7b472cc050de4c6e86d418c` |
| CY integration ancestor | `79282773b0c1ca93aa17303d46e784b5415cad7e` |
| New ZIP SHA-256 | `937279b0d6acb3c865d1a70989fca586ae182104de22d2b4dbaca406f283cd30` |
| Preserved prior review SHA-256 | `094e9b05416789c13180680583adfb9abdcf2e0bb7624a62ff1a72d3634d958a` |

I inspected the verifier before executing it. I reran the outer checksum and complete payload checks, verified/imported the bundle in the disposable repository retained from the preceding independent review, and checked actual commit/tree bytes and ancestry. The continuation diff, cumulative diff and changed-path list match Git exactly. `git diff --check` passes.

The continuation is seven Markdown-only paths, with 586 inserted lines and no deletions. Cumulatively, eight Markdown paths differ from campaign selection. Executable source, tests, configuration, Composer files and CY/FC acceptance evidence are unchanged.

All 79 source snapshots match the candidate; all 72 marked unchanged match the prior entry. All four standalone attachments match their committed counterparts byte-for-byte. The embedded previous ZIP matches the original upload exactly, and the copied independent review matches the file delivered in the preceding review exactly. All 76 recorded local links and 25 method references pass; changed-document fences balance.

I inspected the supplied eight precommit and 254 final command records, each reporting exit 0, and the packaging script. The final recorded branch status is clean. Independent Git comparisons establish no undeclared tracked change in this submitted candidate. Original Windows worktree cleanliness and subsequent packaging-only activity remain supplied observations, not a fresh inspection of that machine or proof about unsubmitted activity.

No PHP application code, CLI or PHPUnit test ran in this review. No credential, account, provider inference endpoint or installed/private state was accessed. Historical test results retain their original attribution. Package hashes establish content identity, not sovereign approval or installed competence.

## What the new design resolves

The proposal now distinguishes operator policy from Oracle assessment, infrastructure access from Locksmith authority, fresh founding from governed existing cutover, and recommendation from application. These are substantive improvements over a list of missing classes.

Source inspection supports its constraints:

- `FormationSignatures` accepts the existing formation competence and exact formation envelope; it does not authorize the new bootstrap domain. Separate scoped public trust is therefore a proposed prerequisite, not a capability obtained by reusing a key.
- `FormationJournal` supplies one frame publication and lock boundary. It does not supply institutional competence or automatically make old and new reservation consumers share accounting.
- The inspected model-bound Profile approval uses `development-local-cli` and leaves activation and Seat binding authority false. The proposal correctly avoids promoting it into a trusted standing approval.
- Root provenance and permanent operationalization closure remain distinct from legacy Augur occupancy. The proposed joint writer fence and exclusion of uncoordinated writers explicitly address future founding races. These remain implementation/deployment obligations.
- The separate catalogue mapping and versioned candidate-view consumer address the actual missing runtime fields without modifying old snapshot hashes. A future real producer-to-consumer test is correctly required.
- Atomic application consumes one exact application effect and publishes the whole role set in one frame. It neither appoints personnel nor permits Augur to approve its own replacement.

These points make the design reviewable. None establishes that the new producers, trusted actors, mapping, budget coordinator or application consumer exists today.

## Two interface clarifications required before freeze

### F1 — define progression separately from request replay

In `contracts/provider-onboarding.md`, the onboard envelope contains both `operation_id` and `expected_head`. Identical semantic requests replay the retained result; changed semantics under the same ID refuse `OPERATION_CONFLICT`. The new v1.1 paragraph explicitly prohibits `resume` from advancing unstarted dispatch or applying an uncommitted assignment, yet permits a later authorized `onboard` advance. The exact identity and continuation rules for that later command are not specified.

For example, an operation can report ASSESSMENT_AUTHORIZED, or stop after a known completed call while later authorized calls remain. Repeating its unchanged request is documented as replay. Updating its head may change semantic identity. Starting a new ID is not explicitly bound to the retained parent/stage and remaining effect slots. The draft therefore leaves implementers to decide how progress differs from recognition.

This is a specification ambiguity, not a reproduced runtime deadlock. Before freeze, define one explicit continuation protocol: stable sequence identity, per-command retry identity, exact predecessor/current head, authorized next effect and one-use stage identity. State whether the head is part of semantic request identity and how a duplicate command recognizes its original result after the aggregate advances. A new command must not create a fresh budget or duplicate a completed call. Known incomplete work within current original policy should advance without another human approval; unknown outcomes must remain fenced. Keep resume evidence-only if that is the chosen interface.

A short command/state table covering initial advance, duplicate replay, next authorized step, stale head, expiry and unknown outcome will close this gap without implementing O1.

### F2 — complete the authority variants and effect registry

The D2 document calls its transition matrix the complete allowed-effect set, but defines `REVOKE_BOOTSTRAP` separately outside that matrix. Its application receipt requires `application_act_ref`, while conditional application intentionally has no new standalone APPLY act. Its access claim reuses a body containing `commission_ref` and `holder_ref`, although pre-Augur access has an access grant and infrastructure custody rather than an Augur commission/holder. The text mentions source-schema discrimination but does not finish the exact fields/nullability for these cases.

Before freeze, publish one authoritative effect registry including revocation, and define tagged authority variants for explicit versus conditional application and for access versus assessment claims. Bind the conditional case to the exact admitted policy and effect slot; do not fabricate an Operator act. Define the real infrastructure holder/source in the access case; do not populate it with a fictional Augur reference. Specify how each remaining Operator effect is supplied: separately signed original, a batch of originals, or an explicitly permitted policy-derived effect. Institutional acts retain their independent competence requirements.

This will make the promised bounded initial consent concrete without silently introducing a signer or requiring repeated approvals for work already covered. The executor can propose these schema details; the owner need not invent JSON fields or protocols.

## Provider, base selection and consent assessment

The owner sheet now gives concrete proposed workloads and ceilings. Its arithmetic is consistent: three cognition calls at $0.10 each produce a $0.30 maximum; one access request plus three cognition calls produces four external requests and 190,000 ms of summed local exposure. Input, output and reserve sum to 24,576 tokens, below the proposed 32,768-token capability minimum. These are proposed policy bounds, not measured cost, capability or remote guarantees.

The least-cost eligible base algorithm, explicit UNKNOWN refusals, exact role/configuration scope, persistent settings, alias limitation disclosure, no fallback and no self-replacement remain appropriate. D2-A's conditional bounded application best matches the previously stated desired experience; it is a recommendation, not an owner decision. D2-B remains a valid narrower alternative.

I independently reopened public documentation. DeepSeek's current first-call guide supports the named API-key route and both proposed model IDs, and explicitly describes those IDs as accessing updated revisions. Thus alias acceptance is a real provider-appendix issue, not something settled by a stable API model string. This does not establish immutable revision IDs or account entitlement. [DeepSeek first-call guide](https://api-docs.deepseek.com/)

OpenAI's authentication reference supports the alternative API-key bearer route. It does not complete the missing OpenAI model/destination/usage appendix. [OpenAI API overview](https://developers.openai.com/api/reference/overview)

My attempt to reopen the cited DeepSeek model-list reference timed out; an official-domain search returned no result. I therefore did not independently reproduce that page's contents. This is a verification limit, not evidence that the endpoint is unsupported. No price, zero-fee access observation, tokenizer, invoke eligibility or enforceable remote cancellation was verified. Current web observations are separate from the runner's recorded retrieval time; they are not substituted into a signed policy.

The access design correctly refuses to equate model listing with invoke permission. Under package A, if sufficient invoke-eligibility evidence or a zero-fee access observation cannot be established, onboarding remains blocked. A provider choice or an approved dollar cap does not remove that condition.

## Remaining decisions and next executable action

The owner can now consider a concrete package:

| Decision | What remains |
| --- | --- |
| D2 allocation/application | Accept or amend the proposed competence allocation; choose conditional bounded application A or separate exact application B |
| D1 | Select DeepSeek API key for the proposed first scope, or OpenAI with a separate exact appendix |
| Installation mode | Identify the intended fresh/existing route using separately lawful public evidence; never infer mode from an absent file or reopen a closed window |
| D3 | Accept or amend the workload, capability thresholds, evidence ages, budgets, role scope and alias policy |
| Evidence closure | Resolve exact instance/trust/incumbent/Profile/candidate/configuration references and retained provider facts; prepare actual policy/workload bytes |
| D4/B1 | Preserve the separate live acceptance gate; no new live permission follows from offline decisions |

**Next executable work is a narrow documentation closure of F1/F2 in the current O0 continuation, alongside the owner decision sheet.** No broad remediation campaign or CY/FC rerun is needed. Owner consideration of the proposed direction can proceed now; final interface freeze must wait for the two clarifications and the declared decisions/evidence.

Suggested local continuation after selection: start from `bbdacecfa95f5e25ff8389fcec092511f2b8300f`; specify exact progression/replay identities and tagged authority records; reconcile the full effect registry; preserve existing acceptance and historical records; return the amended documents and review packet. Keep every owner choice unapproved unless explicitly supplied. Do not implement runtime code, enroll trust, handle credentials, invoke a provider, push or merge.

After closure, review O0 for completion and only then separately select implementation. This review grants no implementation or live authority.
