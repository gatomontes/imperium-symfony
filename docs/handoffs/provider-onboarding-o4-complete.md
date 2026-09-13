# O4-B0 accepted integration and closure

O4 is accepted, integrated and closed within its offline engineering scope as of 2026-09-13. The accepted change provides original-backed W1/W2/W3 assessment resolution, atomic persistent whole-set Courtthane/formation Locksmith settings, explicit operator replacement/revalidation, exact replay and the corrected actual formation-consumer binding.

## Operator decision

The operator answered **“approved”** to: “Do you approve replacing that requirement with the verified complete parallel gate so I can merge and close O4?” This authorizes replacing the prior 1,800-second serial prerequisite with the complete parallel acceptance gate for O4, and integration/closure. The older serial-only instruction and proposal's approval-pending disposition are superseded by this decision.

The parallel gate executes every enumerated test once across eight disjoint file partitions, each with a 30-minute timeout, then verifies complete coverage and source consistency. This decision does not establish a serial speed improvement or test shared-process ordering across partitions. Earlier local serial timeouts remain failed/incomplete runs.

## Exact accepted evidence

| Item | Identity or result |
| --- | --- |
| Integration | [PR #804](https://github.com/gatomontes/imperium-symfony/pull/804) |
| Accepted head | `8d3512a3badc9485a142bf3c844122f34d303c43` |
| Merge commit | `64bceee351fe8cabdd1847ed4cb938a53c2874fa` |
| Verified merge and tested tree | `4fa24be49f638f34fd5111b421239f0c645b81a8` |
| CI checkout | `a0a7d705120e9d495faadb283ccf60be51b2a309` |
| Fresh accepted CI | [Run 34768429392](https://github.com/gatomontes/imperium-symfony/actions/runs/34768429392), success |
| Aggregate job | `103755731856`, success |
| Complete suite | 3,698 tests; 63,727 assertions; four explicit skips; no failures/errors |
| Exact coverage | All 607 files and every enumerated test identity exactly once |
| Guard checks | 11 passing Python tests, including source and coverage fault injection |
| Runtime | PHP 8.4.25 / PHPUnit 13.3.0 |
| Longest partition | 958.156704 seconds |
| Whole workflow | 16:23:26–16:40:04 UTC (16 minutes 38 seconds) |
| Aggregate source digest | `6110641bfce6cf650527576ca81cef8c9a0fb78f397adda248b0a1cbfd44d2f0` |

The merge was pinned to the accepted head, and its resulting tree was independently fetched and compared with the tested tree. The proposal preserved all 1,938 checked protected inputs from reviewed V2, adding the historical-journal corruption regression and the CI guard. Prior runtime reviews and correction evidence remain in the repository history. Gate review in this continuation was by its implementing agent, supported by fault tests and fresh hosted execution; no separate human or agent review is invented.

All tracked files must match committed Git bytes. For the exact generated `config/reference.php` path, Symfony-generated comments and whitespace may vary: the guard records raw hashes and requires identical executable PHP tokens to Git without executing that file. Executable mutations still fail. The first proposal run stopped on that generated-output difference before suite execution; the accepted corrected run above passed.

## Disposition and next step

PR #798's older candidate is superseded by #804 and is closed without a separate merge. Diagnostic #803 is closed unmerged. Historical HOLD and incomplete reports describe their earlier snapshots; this closure and [integration record](../provider-onboarding/o4-b0-reviewed-integration.json) control current status.

O5-B0 is the single remaining planned implementation batch. Its existing preparation branch must be reconciled against merged O4 before execution. This closure grants no live commissioning, enrollment, activation, deployment or execution authority. All five operational flags remain false; the actual retry allowlist is empty. No installed-user state, real credentials or provider calls were used by this closure.

These closure documents are post-test documentation. They do not belong to the previously frozen tested tree and introduce no executable or test changes.
