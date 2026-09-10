# O0 reviewed draft and single-file delivery

Disposition: **F1_F2_ACCEPTED_AT_DRAFT_CONTRACT_SCOPE;
O0_OWNER_DECISIONS_AND_EVIDENCE_PENDING**. This records the supplied independent
review, not a new independent assessment or approval of implementation.

Local entry: `aacb8b1da3006e2c669e50f32ab422c7dd4c7988`, tree
`f8d145eb1abe114e1ed0201f4285aed3f44f3a56`, branch
`codex/provider-onboarding-o0`, worktree
`E:/htdocs/imperium-provider-onboarding-o0`. Entry was clean. No applicable
AGENTS.md was found in the checkout or checked parent locations.

The user selected execution of the supplied `imperium-o0.zip`. Its README calls
for importing two packaging-instruction edits and preserving the latest review;
it does not supply owner policy decisions or select a new campaign. Archive paths,
duplicate names, CRC and sizes were checked before reading selected members.
Eleven unchanged document members match the committed candidate byte-for-byte.
The embedded freeze evidence ZIP matches the original local packet and SHA-256
`7193669f570c4167fc7b6ffa51079c366517c6c491332752db5ee650421e3fa7`.

The [independent freeze review](../reviews/provider-onboarding-o0-freeze-independent-review.md)
is preserved with its original bytes, including original character encoding:
9,421 bytes, SHA-256 `988e378344759beb40c475ce26c1d40868161d434cfb04b4278752c0ca7b48f3`.
It closes F1/F2 at draft-contract scope; no further F1/F2 correction is required.
Prior review packets, reports, current contract and policy proposals are unchanged.

`git apply --check` passed for the supplied patch; `git apply` then changed only
the [campaign](../next-campaign-provider-onboarding-o0.md) and
[launch handoff](provider-onboarding-o0-ready.md). Their resulting bytes match the
corresponding ZIP members. A root-level `*-all-deliverables.zip` ignore rule keeps
future convenience handoffs out of Git. No claimed foreign commit was imported
or cherry-picked: this is a new local commit of the verified patch and review.

The new delivery is one `provider-onboarding-o0-all-deliverables.zip` at the
isolated worktree root. It includes all O0 reports/reviews/contracts/policies/
decision sheets, campaign/run instructions, a README and a new delivery evidence
packet plus its checksum. The previous freeze packet and its checksum remain
inside that evidence packet; its embedded predecessors remain unchanged. The
convenience ZIP itself needs no separate checksum. Upload that one file for review.

Verification records in the delivery packet and convenience archive cover exact entry/final commit/tree,
ancestry, changed paths, cumulative and continuation diffs, source identities,
patch/result equality, review and prior packet hashes, changed-document local
links/fences, ignore behavior, `git diff --check`, clean committed status, payload
verification and complete convenience archive membership/content comparison.
Post-commit activity is packaging only. No PHP or application CLI ran; no provider
facts or reviewer test results were re-established by this documentation pass.

Next owner decisions remain on the [decision sheet](../provider-onboarding-owner-decisions.md):
D2 competence/application A or B, D1 provider/API-key scope, lawful fresh/existing
mode, D3 workload/limits/roles/alias policy, followed by actual public evidence and
exact policy/provider bindings. No choice is inferred from this upload. The
executor can prepare exact artifacts after those choices without asking the owner
to invent schemas or hashes. O0 acceptance, O1–O5 selection and live commissioning
remain separate gates.

CY/FC retain their accepted offline scope. DEFER_ENROLLMENT, B1 and all five false
operational flags remain. No runtime implementation, installed/private-state
access, credentials, enrollment, provider call, appointment, activation, push or
merge occurred. Carry the single-file delivery requirement into subsequent
Imperium campaigns and launch prompts.
