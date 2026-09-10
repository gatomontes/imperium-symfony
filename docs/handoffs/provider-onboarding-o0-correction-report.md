# O0 V1 correction report

Disposition: V1_CORRECTED_WITH_OFFLINE_CHECKS; READY_FOR_REVIEW;
FINAL_O0_FREEZE_PENDING_POLICY_AND_EVIDENCE.
This is the correction author's report. The preceding independent review is
retained byte-for-byte; this pass does not represent an independent review of its
own edits or an operational acceptance.

Actual entry: `2212281ea33ea19c8c70b048b8e482bbe23dfa12`, tree
`22666b9e8380f2ca0035663da341ffd12f56ef98`, in a new isolated worktree at
`/workspace/scratch/ee307405f2b6/o0-correction`, branch
`codex/o0-review-correction`. No intervening entry changes. Campaign-selection
ancestor remains `6adc635ed9d89bbff7b472cc050de4c6e86d418c`.
Exact final commit/tree and raw commit bytes are recorded outside this committed
report in the evidence packet's identities.json and final-commit.txt, avoiding a
self-referential identity. No push or merge was performed.

## What changed

The previously packet-only checker is now tracked at
[tools/check_provider_onboarding_spec.py](../../tools/check_provider_onboarding_spec.py).
It checks input selector object/list shape, exact variant fields, declared group
references, unresolved-descriptor membership and public-ref shape. Unknown tags,
mixed placeholder/public forms and invalid refs refuse. Non-assessment actions
require exactly after_success; assessment conditions must have their exact fields
and remain bound to the group's ordered attempt and predecessor. Group-result
inputs cannot be moved to deterministic configuration; application requires the
ordered W1/W2/W3 inputs. Optimized Python refuses because this offline checker uses
assertions. Default root resolution now matches its tracked tools/ location.

Both V1 review probes now refuse: unknown input selector and unknown
non-assessment condition. The corrected checker reports **62 passing checks**:
all original 43 named checks retained, plus 18 negative cases and one public-ref
shape positive case. The positive ref is an in-memory fixture and confers no
provenance. The original checker and its 43-check output remain in the unchanged
prior provider evidence packet; they have not been retroactively rewritten.

The [current decision/evidence card](../provider-onboarding-current-decision-evidence-card.md)
separates settled choices, nine remaining proposed policy rows, and genuine inputs
with their competent producer and required stage. It copies the current proposal
values, preserving the exact workload and policy template bytes. Historical
three-call/$0.30/no-retry rows remain historical. Approval of this correction does
not approve P1–P9 or fabricate any missing Profile, root, window or account evidence.

Current campaign, entry handoff, flow/lifecycle and decision-sheet pointers lead
to this correction and card. The preceding provider independent review is retained
as a new review file. Existing reviews, CY/FC acceptance, policy/contract bytes,
source snapshots and public-source evidence remain unchanged.

## Validation and limits

Rerun here: ordinary Python checker (explicit root and default root), preservation
of every original named check, both exact review regressions, and optimized-mode
refusal. These are offline specification examples only; they do not establish
runtime concurrency, crash recovery, source authenticity, safe retries or a
complete runtime policy validator. Unchecked portions of the preparation wrapper
are not promoted to validated runtime schema by this result.

Final validation includes changed-document relative link existence and Markdown
fences, JSON readability, original policy/workload/source/review preservation,
diff whitespace, exact staged-tree-to-commit equality, source/blob identities,
patch equality, bundle reconstruction and archive member/manifest integrity.
Results and commands are retained in the evidence packet. Source is frozen before
packaging; the packet records any later change rather than claiming earlier checks
cover it. Packaging files and logs are external to the committed source.

Inspected/carried forward: prior independent review, its two checker coverage
probes, author provider packet, retained official snapshots and earlier verification
records. No public-source retrieval was needed in this correction; the card reports
previously retained proposals and limitations, not refreshed provider guarantees.
No PHP/CI/runtime suite or provider/account call was run. No installed/private
state was read and no credential was handled.

## Continuing boundary and next decision

DeepSeek/API key, FRESH, D2-A, three retries per assessment call, four attempts per
group, twelve cognition attempts, $0.10 per attempt and $1.20 total remain settled.
The safe-retry allowlist remains EMPTY; the count ceiling creates no admissible
failure reason. Unknown outcomes retain exposure. Assignments remain persistent
until explicit operator change; no fallback or Augur self-replacement.

The code correction is complete for V1, subject to review of this exact candidate.
The next owner decision is approval/amendment of P1–P9 in the card. Genuine public
inputs can be supplied at their identified stage; unavailable inputs stay explicit.
Do not manufacture completed enrollment/founding/appointment receipts to complete
an offline template. Missing producers/consumers require separately selected
implementation. Live B1 evidence is a live qualification gate, not a requirement
to write separately authorized offline refusal logic.

CY/FC remain accepted and integrated within their offline software scope.
O1–O5 implementation, deployment, enrollment and live commissioning remain deferred.
DEFER_ENROLLMENT and unresolved B1 remain; deployment_approved,
enrollment_authorized, live_ready, activation and execution_authority remain false.

The [review handoff](provider-onboarding-o0-correction-ready.md) supplies the check
command and review scope. The convenience ZIP includes all public deliverables and
the evidence packet plus its checksum; no convenience-ZIP checksum is required.
Individual report/card/checker/instruction files are also supplied for download.
