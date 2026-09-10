# O0 — medium-capacity target selection

Status: OWNER_SELECTION_RULE_RECORDED; CONTRACT_CORRECTION_PREPARED.
Entry: caba3201aca8191ed403ddd75d5e50461c2e42c1.

Owner instruction: “If many options fit... choose the medium capacity. There...
no more abiguity”. This replaces the review's recommendation to refuse solely
because several permitted candidates fit.

The operational interpretation is role-specific capacity as assessed by Augur
against the exact Profile and frozen evidence. The engineering conventions,
communicated when applying the instruction, are the lower middle of an even
number of capacity tiers and a stable identity tie-break within the selected tier.
These conventions are explicit implementation choices, not additional verbatim
owner statements. No cheaper-or-larger-model preference is inferred. The initial
Augur base remains least-cost eligible under its existing policy.

[The selection contract](../contracts/provider-onboarding-assignment-selection.md)
fixes the algorithm, expanded W2/W3 result shape, local evidence mapping, refusal
behavior and atomic application boundary. [The rule JSON](provider-onboarding/o0-assignment-selection-rule.json)
and revised [workloads](provider-onboarding/o0-workloads.json) are non-runnable
preparation. The correction report records their exact content digests and the
final review packet binds the source commit/tree. Earlier approval JSON continues
to identify the original approved source bytes; it is not rewritten retroactively.

The owner has resolved the selection preference. No further P1–P9 decision is
requested. An actual model ranking remains an assessment output, not a fact this
preparation invents. Unsupported or missing comparative evidence still reports
UNKNOWN; a middle choice cannot be derived from an unknown order.

All other policy/custody/consent boundaries remain: DeepSeek/API key, FRESH, D2-A,
three retries per call, four attempts per group, twelve total attempts, $0.10 per
attempt/$1.20 total, and persistent settings until explicit operator change.
The safe-retry allowlist remains empty. CY/FC retain offline acceptance; O1–O5,
enrollment, commissioning and live operation remain deferred. No push or merge.

See the [correction report](handoffs/provider-onboarding-o0-medium-capacity-report.md)
for validation and source identity boundaries.
