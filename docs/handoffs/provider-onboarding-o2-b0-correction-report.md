# Provider Onboarding O2-B0 correction report

Disposition: R1-R4 corrected locally; source review, full CI and integration remain pending. Draft PR #788 is not accepted or merged by this work. All six O2-O5 batches remain unaccepted; live commissioning stays deferred. This correction stops at O2-B0.

## Source identity

Entry commit: 3170f6fb2e11f1fae8158ae751aac84ac45c9e62; tree: 48b2cbb324285d46068eb50c6fd62b9079f91ddc.
Tested implementation commit: 60de43155b42bbe6bd2315765b45428f83b1da96; tree: d149b7875bdf58472741dcd275e0f7391e1bb7eb.
The delivery source-identities.json records the final documentation commit/tree and exact post-test diff. All 1,773 tracked PHP checkout byte streams were captured before final tests and match their committed canonical blobs exactly (zero normalization differences). Delivery includes those exact copies, both hashes per file and a post-test comparison. Final changes after testing are documentation only.

## Corrections

R1: Admission and current Resolver use the trusted clock under the owning lock to enforce half-open validity on the unique exact matching signed-act slot. Expired slots refuse. Ambiguous matching slots refuse explicitly because the envelope has no selector; selecting another live slot cannot hide expiry. Historical recognition remains immutable and carries no current authority.

R2: Resupplied exact records already retained must pass currentness and revocation checks against their original provenance under the same lock before dependent admission. Existing provenance is never replaced. Refusal publishes no frame. Positive resupply preserves first provenance. Coordinated subprocess tests start both real signed producers against one head, release them in both deterministic orders, prove stale loser refusal and then submit a fresh signed act at the new head: revoke-first blocks admission; admit-first becomes non-current after revocation. This proves order-dependent outcomes, not a probabilistic simultaneous race.

R3: Act verification checks signed policy body validity as well as signing-act validity. This covers current policy originals and source-origin policy admission checks. Tests intentionally use earlier policy expiry than signing-act expiry and cover before/at/after boundaries. Historical replay remains a no-write recognition.

R4: Two explicit additive inventory rows cover Admission.php and Resolver.php through the existing inventory mechanism. Old rows, frozen snapshots, counts and tripwire assertions are unchanged. The existing APPROVED_POST_BATCH12_SUCCESSOR classification is a mechanical inventory classification; it does not accept O2-B0 or authorize deployment. Descriptions explicitly retain dormant, non-consuming behavior.

## Evidence and validation

Local runtime: PHP 8.4.14, PHPUnit 13.3.0, Composer 2.8.12; locked dependencies installed without scripts/plugins. Initial reproduction: 14 tests / 3,931 assertions / 6 failures, exit 1. The four reviewer requirements and two inventory failures were reproduced before correction; reviewer tests remain unchanged.

Final independent runs on the tested commit:

| Selection | Tests | Assertions | Exit |
| --- | ---: | ---: | ---: |
| Original O2 admission/process, unchanged reviewer cases, new correction cases | 142 | 318 | 0 |
| Three O1 suites, exact source-pin, native snapshot and relevant journal/trust regressions | 384 | 1546 | 0 |
| Batch12 coverage and all FrozenRuntimeCoverageTripwireRestoration suites | 16 | 3984 | 0 |

Total: 542 tests / 5,848 assertions, no failures or skips in these runs. All 86 Python specification checks pass. Five changed/new PHP files lint successfully. Test kernel boots successfully. Commands and public raw logs are included. The earlier development-focused pass is retained as development evidence only, not added to totals. No interrupted run is counted. No new full-suite or hosted CI pass is claimed.

Inherited reviewed CI remains a failing baseline: run 34512596315, job 102990145524, PHP 8.4.25, PHPUnit 13.3.0, 3,453 tests / 55,451 assertions / 6 failures / 4 skips. Its original log and identity metadata are retained and checksum-verified. It is not correction validation.

## Preserved boundaries

FormationJournal, service wiring, source pins, original approved workload/policy bytes, prior CY/FC/O1 acceptance and reviewer tests remain unchanged. DeepSeek/API-key, FRESH, D2-A, P1-P9, medium target capacity, least-cost eligible initial base, empty actual retry allowlist, persistent operator control, DEFER_ENROLLMENT and unresolved remote guarantees remain. All five authority flags remain false. No credentials, provider requests, installed-state access, real enrollment, appointment, assignment, activation, operational wiring, later batch, push or merge occurred.

## Review handoff

Inspect the exact entry-to-final patch and Git bundle, source/protected identities and tests. Run full CI on the reviewed integration candidate and obtain renewed source/integration disposition before any merge. The package contains the original reviewed packet for historical context, all changed canonical files, exact tested PHP copies, commands/logs and complete manifest. The inner review ZIP has a SHA-256 sidecar; the outer all-deliverables ZIP intentionally has none.
