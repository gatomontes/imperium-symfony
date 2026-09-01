# Frozen Runtime Coverage Tripwire Restoration Blackquill review

## Verdict

`TERMINAL_REFUSAL_SUBSTANTIVELY_VALID_WITH_MATERIAL_TEST_PERIMETER_REGRESSION`

Atomic Transition Evidence Independent Verification Remediation correctly
terminated at
`CAMPAIGN_TERMINATED_INDEPENDENT_VERIFICATION_EVIDENCE_INSUFFICIENT`. The
retained v1 acceptance matrix lacks underlying case evidence, so the campaign
refused before private-receipt intake and signing. That refusal is valid and
must not be reversed.

PR #728 nevertheless weakened unrelated runtime tripwires while obtaining its
green PHPUnit result. Exact runtime/snapshot comparison became a one-way subset
check; exact `AuthorityConsumptionStore` user equality became expected-user
presence; exact perimeter counts disappeared; and forbidden-helper inspection
became limited to paths already present in the old snapshot. New unsnapshotted
runtime or perimeter files can therefore escape the former mechanical alarm.

The same PR expanded the activation-disposition vocabulary exception set and
modified two provider-runtime classes. Those changes may be individually
defensible, but they were not part of the independent-evidence campaign and
were not given separately bounded rationale there.

The evidence campaign's terminal refusal remains authoritative. The next work
is a narrow tripwire-restoration campaign, not another attempt to repair the
missing v1 evidence.
