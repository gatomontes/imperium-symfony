# O0 medium-capacity selection correction

Disposition: A1_SELECTION_RULE_DEFINED; OFFLINE_CHECKS_PASS; READY_FOR_REVIEW.
This is the author's correction report, not independent acceptance or runtime proof.

Entry commit: caba3201aca8191ed403ddd75d5e50461c2e42c1.
Entry tree: 9174323715a8dc2d2fbedc8a9f5f94349acb8ad8.
Worktree: /workspace/scratch/ee307405f2b6/o0-correction.
Branch: codex/o0-review-correction. Exact final commit/tree are external in the
review packet's identities.json; no self-referential source identity is embedded.

The owner supplied the missing selection preference: when several options fit,
choose medium capacity. The [decision](../provider-onboarding-medium-capacity-decision.md)
records that instruction and the explicitly stated engineering conventions:
role-specific Augur capacity tiers, lower middle for an even tier count, stable
identity tie-break within the chosen tier. No provider ranking is fabricated.

The [v1.4 assignment contract](../../contracts/provider-onboarding-assignment-selection.md)
now defines both selection and the closed response-to-assessment-view mapping.
W2/W3 return capacity_order as evidence-supported ordered tiers or unknown, using
a distinct raw response schema. Fitting and permitted candidates are filtered
before the tier median. Complete coverage is mandatory; unknown order cannot
resolve multiple options. A sole option needs no comparative ranking. The result
must form a complete permitted Courtthane/Locksmith pair; otherwise it refuses
without choosing an alternate pair or applying a partial assignment.

Selection runs locally over the retained results and adds no model call. The
existing assessment-view and assessed_assignment_set terms now bind the exact
selection rule and evidence. Historical receipts use their original rule; retry
inputs remain frozen. This does not modify the initial least-cost-eligible Augur
base algorithm, tariff calculations, owner limits, custody or authority allocation.

The updated public preparation and W2/W3 workload are explicitly versioned.
The old P1–P9 approval record remains bound to the original bytes at its source
commit. This amendment is recorded separately; unchanged P1–P9 choices are not
reopened. Actual prompt/input/evidence and Profile references remain non-runnable
until genuinely resolved. The preceding A1 self-review is retained byte-for-byte.

Validation rerun: **86 passing offline checks**, retaining all original 62 named
examples and adding 24 rule/response/selection checks. Cases include zero/one/
multiple options, odd/even middle tiers, equal-capacity ties, permission filtering,
unknown/incomplete/duplicate/extra rankings, permutation invariance and whole-set
refusal. These are abstract specification examples; no PHP, production runtime,
concurrency, provider-capacity ranking or safe-retry proof is claimed.

Final checks also cover changed-document links/fences, exact prior-approval source
bindings, unchanged W1 workload body, unchanged numerical and configuration values,
protected runtime/CY/FC/base-policy/history bytes, diff whitespace, final tree and
bundle/source identities, and evidence manifests/ZIP integrity. Results and any
post-check edits are recorded externally. No installed/private state was read,
provider/account request made or new provider fact adopted.

A1's missing selection rule is resolved at contract level by the owner's policy
choice and these exact conventions. The corrected candidate remains available for
review; no independent reviewer verdict is asserted. Final O0 disposition and
selection of O1–O5 remain separate. No further selection-policy approval is asked
for here. Actual public authority, Profile, account/wire/usage and B1 evidence are
still needed at their proper later stages.

CY/FC acceptance remains integrated within offline scope. DEFER_ENROLLMENT and
unresolved B1 remain. The safe-retry allowlist is EMPTY; twelve attempts and $1.20
are ceilings, not permission to retry unknown outcomes. Settings persist until
explicit operator change. All five operational flags remain false. No activation,
push or merge was performed.

The [handoff](provider-onboarding-o0-medium-capacity-ready.md) describes review of
this candidate. All produced files are in one convenience ZIP with an evidence
packet and its checksum; individual report/contract/instruction downloads are also
provided. No outer convenience-ZIP checksum is required.
