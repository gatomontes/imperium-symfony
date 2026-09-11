# Provider onboarding O2-B1 correction report

R1–R3 are corrected locally. Source review, fresh full hosted CI and integration remain pending; O2 remains open. No push, merge, provider operation, real credential access, installed-state access or live commissioning was performed. Original worktrees, author reports and the four reviewer regression requirements are preserved.

The clean correction worktree is `E:/htdocs/imperium-onboarding-o2-b1-correction`, branch `codex/provider-onboarding-o2-b1-correction`, from reviewed commit `441fc9bb7003b62cb77bf61d82cc78485d55bf38`, tree `f56c019688777745ae0b5a2e5d29f18dbcee432d`. The input packet's manifest, CRC, path/membership/duplicate checks, checksummed inner archive, source equality and entry identities were verified. The [attributed review](../reviews/provider-onboarding-o2-b1-source-evidence-review.md) and [review identities](../provider-onboarding/o2-b1-reviewed-hold.json) are copied byte-for-byte.

## Corrections

- **R1:** ResponseEvidence closes metadata/envelope fields, binds both operation digests to exact prepared bytes, binds the original claim, response identity/provenance, response bytes and typed bounded usage, and compares recovery with committed original metadata. Normal retention, recovery publication, decoding and settlement use the same validation. Invalid evidence leaves maximum exposure unsettled; recovery never issues credentials or redispatches. Checkpoint operation_digest consistently denotes the prepared operation, replacing the ambiguous wrapper digest in the held implementation.
- **R2:** StateValidation closes all v2 map/entry/record/tuple variants and checks enrolled H identities, original source/policy/command/sequence/step/slot/authority/lease/operation links, monotone custody, settled usage, fences, budget members, frozen groups and outcomes. It checks counts, list/map form and reserved-empty maps. Historical migration digests are reconstructed from original first-admission provenance before migration, excluding later admissions/revocations; historical reads do not require current authority. All B1 publications and B0 post-admission v2 publication validate before commit. No journal or nested-lock change is introduced. See the [bounded design note](../provider-onboarding-o2-b1-correction-design.md).
- **R3:** Fresh and duplicate resume presentations take sequence_head from the actual retained advancing sequence inside the owning lock. The resume result retains its own result_ref and fingerprint. The next legal advancing command can use the returned predecessor; duplicate resume after later advancement returns that later advancing head without changing the original recognition result.

## Validation

Final runtime tests use the exact PHP source captured from commit `c0765666775df8db659ff6a64bbf5d01ce6624e3`, tree `06db82939e359fc533fb4a25478461ebe87c6098`, on PHP 8.4.14 / PHPUnit 13.3.0 with Composer 2.8.12 locked dependencies. All 1,800 tracked PHP byte streams match canonical Git blobs exactly, with no normalization differences. The reviewer selection completed first. The other selections then ran independently against unchanged PHP bytes.

| Final selection | Tests | Assertions | Result |
| --- | ---: | ---: | --- |
| Four unchanged reviewer requirements | 4 | 10 | PASS, exit 0 |
| B1, new correction tests and all four B0 suites | 211 | 952 | PASS, exit 0 |
| FC custody/process, mission/correction and native protocol | 140 | 760 | PASS, exit 0 |
| O1 selection/response/base and three source-pin suites | 380 | 1519 | PASS, exit 0 |
| Transactional coverage and all frozen inventory tripwires | 16 | 3996 | PASS, exit 0 |

Total: **751 tests / 7,237 assertions**, no skips or warnings in the final selections. Nine changed/new PHP files linted, default kernel boot passed and all 86 Python specification checks passed (exit 0). Final commands, genuine logs, exit sidecars and exact-source identities are included. Network functions were disabled for final PHPUnit and the process workers.

The first inventory run detected the new StateValidation candidate and failed two requirements. An additive documentation row was committed as `280aa4d3d2243f4abb2a1a2ff47ebafe42ea2833`, tree `02b84580c116b1a53d34aeb4a68a7965204b1285`; the inventory gate then passed on that state. No PHP bytes changed during or after the final tests. The final identity records this distinction, and post-tested-documentation.patch contains the inventory row and final report changes. The original inventory prefix remains byte-exact; no baseline or requirement was regenerated.

The protected audit verifies 3,303 unchanged original files, including all original tests, fixtures, reviewer cases, author reports, policies/workloads, source pins, FormationJournal, SessionExposure, service configuration and locks. Six existing runtime files changed within the correction boundary; the design note records the B0 post-admission validation invariant. All changed Markdown links resolve and both attributed review copies are byte-exact.

New adversarial cases cover missing/extra/wrong envelope and metadata fields, mismatched claim/operation/response/provenance/usage, intact recovery and idempotent settlement; populated v2 entry/body/tuple/key and foreign/resealed mutations; original migration digests after later revocation; malformed v2 refusal through migration/status/B1/B0 entries; pending/completed resume, duplicate recognition after later advancement/expiry, invalid references and the legal next advancing command. They use original retained fixtures. Development preview incompatibility and malformed-input warnings were corrected before final source capture; the final runs retain the unchanged original preview test and produce no warnings.


The baseline reproduced all four unchanged reviewer failures. Development logs, including the initial malformed-test-helper error and an early syntax error, remain separate from final evidence. The prior author/reviewer hosted CI logs are retained solely as attributed historical evidence. The author's green full CI did not cover the reviewer requirements; the reviewed candidate's CI failed those four requirements. No local targeted selection is labeled full CI.

## Scope and review boundary

The new mutation cases are adversarial decoder inputs over actual retained fixtures, not invented original authority producers. Existing signed B0/FC producers and fixed counting/fault ports exercise custody and recovery in temporary roots. Positive founding/assessment/assignment execution is not claimed: compatible later producers remain absent and default refusal persists. No hypothetical retry allowlist is enabled. Local process-loss tests do not prove power-loss durability or remote cancellation.

CY/FC offline acceptance, O1 and O2-B0 integration remain intact. FormationJournal, SessionExposure, service wiring, dependency locks, original fixtures/tests, approved workload/policy bytes and historical source pins are unchanged. No O3 adapter/founding, O4 assignment or O5 CLI is implemented. DeepSeek/API key, FRESH, D2-A, P1–P9, medium target capacity, least-cost eligible initial Augur base, persistent operator-controlled assignment, DEFER_ENROLLMENT and unresolved remote guarantees remain. All five operational flags stay false.

The delivery includes canonical changed files, exact tested PHP bytes and comparisons, public commands/logs/exits, protected hashes, binary patches, an incremental Git bundle and payload manifest. The inner review ZIP has a SHA-256; the outer all-deliverables ZIP intentionally has no checksum. Stop for source/integration review within O2-B1.
