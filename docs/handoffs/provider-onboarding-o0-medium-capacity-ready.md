# O0 medium-capacity correction — review handoff

Review the exact candidate in identities.json. Read the
[report](provider-onboarding-o0-medium-capacity-report.md),
[owner decision](../provider-onboarding-medium-capacity-decision.md),
[selection contract](../../contracts/provider-onboarding-assignment-selection.md)
and retained [A1 self-review](../reviews/provider-onboarding-o0-assignment-self-review.md).

Check that medium means the middle of evidence-supported role-specific capacity
tiers among permitted fitting choices, lower middle for even counts and stable
identity tie-break within a tier. Rank validity and complete coverage precede
filtering. Unknown order cannot select between multiple fitting choices. The
selected pair must remain a permitted complete whole set, with no alternate-pair
search or partial application. Base-model least-cost selection stays unchanged.

Trace exact W2/W3 raw response shape through retained outcomes to local view,
selection_rule_ref, original rank evidence and one atomic application. Verify
no extra model call, no changing retry input, and original receipt recognition.
Check original approval bindings and clearly versioned workload changes. Rerun:

```text
python tools/check_provider_onboarding_spec.py .
```

Expected author result: 86 static/abstract checks. Distinguish author evidence from
checks independently rerun. No actual provider ranking, runtime or concurrency
proof follows. Recheck Git/bundle/manifests and report a scoped O0 verdict.
Do not reopen the medium-capacity or P1–P9 choices. Do not require fabricated live
acts to review offline contracts; keep real evidence requirements at their stages.

No O1–O5, provider/account request, credential, enrollment, appointment, activation,
push or merge. Preserve CY/FC acceptance, DEFER_ENROLLMENT, B1, empty safe-retry
allowlist and all false flags. Package every public deliverable in one convenience
ZIP with README and the evidence/checksum. No outer hash; also provide individual
files because the owner's ZIP downloads have repeatedly failed.
