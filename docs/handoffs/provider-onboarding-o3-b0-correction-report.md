# O3-B0 credential custody correction report

Implemented R1 in isolated worktree `E:\htdocs\imperium-onboarding-o3-b0-correction`. Ad Imperium.

The actual delivery KeySource must now be the same object validated by AccessAdapter against the admitted credential original. Runtime refuses a different object before constructing transport/coordinator infrastructure, including a source advertising the same public generation string. The same source is readonly-bound for both roles, so the unchanged owning coordinator's checks at issue, consumption, callback and dispatch now validate actual delivery currentness. No O2 seam change was necessary.

The unchanged supplied reviewer test first reproduced unauthorized synthetic dispatch and settlement on the entry source (1 test, 1 assertion, 1 failure). Final corrected tests use actual synthetic B0/O2 originals and an exact MockHttpClient. New cases prove construction refusal, source rotation at all four boundaries, no transport/retention/settlement, retained maxima/fences and no evidence-only recovery dispatch. Original positive access/replay and rotation coverage remains intact. See the correction design note for boundary details and the trusted KeySource contract.

## Exact identities

- Clean entry commit: `ef30a8fa76afe2a2fbc2983e4ffae7c6b42d3862`; tree `887b138ca20e513721d3271742f17d5b683fc5ea`.
- Tested implementation commit: `74b815535767aad6b18f8cef40c57cec339a4ff0`; tree `b925dbb0fd25cdd6130a7f9c3c5629964029f33c`.
- Final commit/tree: recorded after the documentation commit in the packet's `identities.json`. The post-test change is three new Markdown documents only; no tested PHP bytes change.
- `entry.json` captures actual empty entry status. All 1818 tracked PHP files were compared to Git blobs, snapshotted before final runs, and hashed again afterward. Locked third-party dependencies are identified separately; project snapshots exclude vendor files.

## Fresh local validation

PHP 8.4.14, PHPUnit 13.3.0, Composer 2.8.12. Locked dependency dry-run passed without changes. These are selected local runs, not full hosted CI.

| Selection | Tests | Assertions |
| --- | ---: | ---: |
| credential-correction | 8 | 60 |
| reviewer | 4 | 10 |
| o3-b0 | 68 | 304 |
| o2-b1-b0 | 211 | 952 |
| fc | 140 | 760 |
| o1-pins | 380 | 1519 |
| inventory | 16 | 3996 |
| Total | 827 | 7601 |

All 17 recorded final commands exited zero. All 86 static specification checks, four changed-PHP lints, non-debug kernel smoke and diff check passed. Complete unabridged logs and numeric exits are under `evidence/final`; `final-commands.json` records argument vectors, cwd and elapsed times. Network-capable PHP calls were disabled for the selected tests. No provider was contacted. Failed pre-fix and superseded focused development evidence remains separate under `evidence/development` and never counts as final proof.

## Preservation and CI budget

Only two existing DeepSeek source files and the CI workflow change. All 3,345 other entry-tracked originals match byte-for-byte, including O2, all original tests/fixtures, original design/report, FormationJournal, SessionExposure, approvals, source snapshots, service configuration and dependency locks. The comparison lists the three explicit changes separately with entry/final hashes. The supplied reviewer test is added unchanged, verified against the input ZIP manifest. No public authority schema, operational flag or retry allowlist changes.

The final local O3 selection took 420.0 seconds, O2 583.4 seconds and FC 589.0 seconds. O3/O2/FC ran with at most two selections overlapping, so these are selection durations, not an estimate of hosted full-suite wall time.

The received hosted log starts the full-suite command at 00:23:22 UTC, reaches 3,172/3,604 at 00:37:45, and records cancellation at 00:38:23. Progress is nonuniform: 854 to 1,098 alone takes nearly nine minutes. Existing process tests use bounded synchronization waits; this correction does not shorten waits, remove tests or infer a completed run. The workflow budget rises from 15 to 30 minutes to allow additional headroom, and adds the supplied focused reviewer step before the unchanged `vendor/bin/phpunit tests`. Dependency installation remains unchanged. The 30-minute allowance still needs hosted verification and is not a guarantee of completion.

Supplied review logs and metadata are retained under `evidence/supplied-review` as historical input, with their verified manifest. They were not fetched anew and are not validation of corrected source. This handoff does not claim a fresh full-suite pass.

## Review gate and deliverables

Source review and fresh complete hosted CI on the corrected source remain outstanding. Publishing the correction was excluded by the user's local-handoff instruction, so no corrected hosted run was triggered. Do not merge or enter O3-B1 until those gates pass. No push, merge, real credential use, existing-installation state access or later-batch implementation occurred. Production account evidence still refuses by default; public/synthetic evidence grants no account authority. All five operational flags remain false and retries remain empty.

The packet contains canonical changed files, exact patch, incremental bundle with the entry prerequisite, distinct entry/tested/final identities, complete tested PHP bytes/hashes, protected-original comparison, commands/logs/exits, documentation and manifests. The inner review ZIP has a SHA-256 file; there is no outer ZIP checksum or recursively embedded historical archive.
