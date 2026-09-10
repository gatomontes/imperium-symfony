# O2-B0 local implementation report

Disposition: implemented and locally validated; source review, full repository CI
and integration pending. O2 remains open. This completes only the selected local
admission batch; no later batch, publication or live commissioning was executed.

Entry commit `bf8e60d7b6bfc28cbb781e8615c2e3a90a48f36a`, tree
`23e9f6c3a0d5c1e7639f03c241dc032ce16aecf8`, contains reviewed preparation PR #787.
Worktree: E:/htdocs/imperium-onboarding-o2-b0. Branch:
codex/provider-onboarding-o2-authority-admission. Entry ancestry, merge/tree/parents,
packet manifest/CRC/inner checksum/equality and supplied CI-log hash were verified.
Earlier worktrees and the installed checkout were preserved.

## Implemented boundary

Nine excluded AuthorityAdmission classes implement bounded strict decoding, the
fixed selected policy graph, separate administrative enrollment, signed original
retention, exact reference resolution, currentness and append-only revocation.
All keys are separately enrolled; no incoming act supplies its own verification key.
Real Ed25519 verification uses canonical payloads and retains raw bytes separately.
Only enrollment initializes missing trust. Legacy formation/native competence,
development records, O1 projections and source labels cannot become bootstrap trust.

FormationJournal gains the compatible changeAtHead callback, observing predecessor
head and mutable state under its original owning lock. Existing change delegates
without changing its state-only contract, frame format or publication mechanics.
No read/change race or nested lock was introduced. This mechanical change is the
only modification to existing runtime source. Services and dependencies are unchanged.

Enrollment, original/receipt retention and revocation use the same frame transition.
Exact duplicate recognition is historical and does not grant current authority;
changed nonce content conflicts. Current resolution rechecks originals, trust,
policy and revocation at the observed head. Static observations explicitly have
execution_authority, authority_consumed and effect_completed false. Retained
evidence is not a completed ADMIT_BOOTSTRAP_EVIDENCE/progression effect.

The [implemented shapes](../provider-onboarding-authority-admission-input.md)
document the bounded v1 choices, named schemas, compiled graph, source wrappers,
exact signed terms, intermediate reference checks and future-prerequisite refusals.
The [reviewed contract](../../contracts/provider-onboarding-authority-admission.md)
continues to govern. The amended medium-capacity workload and existing O1 rule are
bound explicitly; original P1–P9 approval bytes and historical hashes are preserved.

## Validation and attribution

PHP 8.4.14, PHPUnit 13.3.0, locked Composer installation without scripts/plugins.
All new fixtures use synthetic keys and public originals through the actual new
producers in temporary roots. No maps are directly seeded to make admission succeed.
Tests that deliberately corrupt storage are negative integrity cases only.

| Check | Result |
| --- | --- |
| New admission and subprocess suites | 128 tests / 218 assertions, PASS |
| All three O1 suites and three required source-pin regressions | 380 tests / 1,519 assertions, PASS |
| Targeted existing formation/native-trust compatibility | 4 tests / 27 assertions, PASS |
| Existing Python specifications | All 86 PASS |
| PHP lint | All 14 changed/new PHP files PASS |
| Symfony test kernel boot | KERNEL_BOOT_OK |

Successful PHP totals: 512 tests / 1,764 assertions, no skips or issues in the final
runs. Process tests run on this Windows host: two real admissions at one head produce
one whole commit and one stale refusal; abrupt process termination inside the locked
pre-publication transition publishes nothing; exit immediately after actual admission
retains the complete original/receipt and restart recognizes it without another frame.
The pre-publication process case exercises journal transaction rollback, not a
claimed disk-power-loss/fsync proof or every operating-system crash point.

Adversarial cases include genuine signature failures, foreign domains/identities,
enrollment competence, exact policy/graph/group/slot constraints, every F2 S/P registry
combination, unsupported issuer sources, nonce conflict, source substitution/depth/
duplicates, byte/frame tampering, time-unit/range and half-open currentness, revocation,
future dynamic prerequisites and refusal without mutation. Targeted old compatibility
tests cover sibling intake publication, separate enrollment, foreign-domain/effect
refusal, conflicting replay and broken journals. Their exact commands accompany logs.

The initial focused run had 120 tests / 139 assertions and six errors: the fixture
incorrectly paired the historical P1–P9 workload hash with the later amended bytes.
The implementation now checks the actual reviewed amendment hash and raw content;
the older approval was not changed. A subsequent run had one array-key-order test
comparison failure, fixed by comparing canonical forms. Final logs reflect the fixes.
An optional broader formation/native regression run was stopped after 15 progress
dots and has no final result; its incomplete log is retained. It is not counted as
passing. Full repository CI remains the integration gate; no new remote CI or full
local suite is claimed. Preparation/B2 CI is historical, not proof of O2 runtime.

After final source tests, only documentation and ignored evidence/package work
followed. Source-identities.json binds final commit/tree, tested PHP bytes and all
changed canonical source hashes without a self-referential report hash.

## Limits and continuation

This library's deployment constructor/admin API assumes protected fixed-root custody
and independently established human competence/fingerprint confirmation. Tests do
not establish genuine installed competence. A signed source wrapper retains the
Operator's supplied material, not an institutional Profile approval, invoke entitlement,
credential capability, current budget or actual provider guarantee. Unsupported
institution and existing-installation paths refuse. No automatic enrollment or
production service/CLI wiring is supplied.

O2-B1 must re-resolve under atomic execution consumption and implement the shared
step/slot/budget/custody boundary. Policy-derived rights with missing progression
results and founding/access/assessment/application dynamic prerequisites refuse here.
O3/O4/O5 retain their later boundaries. O1 implementations/tests, original approval/
workload, CY/FC acceptance, pinned services, DEFER_ENROLLMENT, unresolved B1 remote
guarantees, empty safe-retry allowlist, persistent operator settings and all five
false live flags remain unchanged. No provider request, fact refresh, credential,
installed-state access, appointment, assignment or activation occurred. No subagents.

The next action is review of this exact local commit and matching full CI before
separately authorized integration. Stop within O2-B0; report concrete corrections
if found instead of starting later work. The complete public packet contains source,
tests, report/instructions, logs, identities, patch/bundle and checksummed inner ZIP.
