# O1-B2 local implementation report

Disposition: implemented and locally validated; source review, full repository CI
and integration pending. O1 remains open. No later batch was executed.

Entry commit: `32ae2e0634066f8f0b772bbcb24eef788abd2065`.
Entry tree: `ba531cb03e3ff5bf212a757f7884ed956bb78e2d`.
Worktree: `E:/htdocs/imperium-onboarding-o1-b2`.
Branch: `codex/provider-onboarding-o1-base-selection`.
The reviewed preparation was verified against its manifest, inner checksum,
integration identities and retained CI log hash before implementation. Its CI
belongs to preparation and is not B2 runtime evidence.

## Change

Six excluded, dependency-free BaseSelection classes implement strict immutable
internal input, scoped frozen observation checking, bounded exact integer cost,
complete exclusions and deterministic least-cost proposal/refusal. The existing
target-role medium-capacity selector and B1 parser are unchanged. See the
[internal shape](../provider-onboarding-base-selection-input.md) and
[contract](../../contracts/provider-onboarding-base-selection.md).

The proposed binding minimizes eligible three-call baseline cost and retains all
equal-cost identities in the mandated bytewise order. Unknown, contradictory,
stale or unsupported evidence excludes candidates. Unsupported/incomplete billing
does not present partial totals as complete USD comparison. Overflow in any row,
including an excluded row, refuses the entire proposal. No operational consumer,
admission conversion, service registration or new dependency was added.

All PASS, OFFICIAL, capability and access observations in tests are synthetic.
Historical tariff numbers remain conditional: Flash 12,616 / 37,848 / 151,392 and
Pro 37,848 / 113,544 / 454,176 micro-USD for attempt/baseline/twelve-attempt maximum.
Unknown historical eligibility yields no base. Supplied references, officialness
and normalized bodies still require genuine later source resolution/admission.

## Validation

PHP 8.4.14; Composer 2.8.12; locked PHPUnit 13.3.0. Composer installation used
`--no-scripts --no-plugins --no-interaction --prefer-dist`; lock is unchanged.

| Check | Result |
| --- | --- |
| New B2 focused suite | 159 tests, 445 assertions, PASS |
| B0/B1 and three required source-pin regression suites | 221 tests, 1,074 assertions, PASS |
| Python specification checks | All 86 PASS |
| PHP lint | All six source classes and new test PASS |
| Symfony test kernel boot | KERNEL_BOOT_OK |

Totals are 380 PHP tests / 1,519 assertions, with no final run skips or issues.
The initial focused run had a duplicate data-provider name and reported 107 tests /
178 assertions plus one PHPUnit error. It was corrected; that failed log is retained.
Intermediate passing focused runs had 154/413 and 156/436 before additional cases;
the retained final log is 159/445. Review also corrected partial unsupported-billing
totals, duplicate full identities/context refs, and scalar coercion at the exact
arithmetic helper. Final required checks passed after all PHP changes.

Full local repository suite and new remote B2 CI were not run. Full CI remains the
integration gate; prior B1/preparation CI is historical evidence only. After final
tests only documentation, audit/packaging artifacts and the local commit were added.
The package's `source-identities.json` records exact tested PHP file hashes,
final commit/tree and changed-file hashes without a self-referential report hash.
Commands, logs, source audit, patch and incremental bundle accompany the report.

## Preserved boundary

No provider call or fact refresh, credential access, live setting change, authority
admission, persistence, appointment, assignment or activation occurred. No push or
merge occurred. Original policy/workloads/approval, empty retry allowlist,
DEFER_ENROLLMENT, live flags, B0/B1 source/test bytes, source pins, dependency lock
and CY/FC acceptance remain unchanged. Only the actual roadmap B2 disposition is
updated; earlier preparation prose is retained as historical context. Seven batches
still lack completed integration, including B2 pending review. No subagents used.

Next action is source/integration review of this exact local commit, including full
repository CI. Fix concrete defects within B2 if found; do not infer authorization
for publication, merging, later-stage work or live commissioning from this report.
