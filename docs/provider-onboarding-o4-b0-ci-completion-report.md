# O4-B0 CI completion correction report

The complete unchanged local suite still timed out at its 30-minute allowance. Full CI completion remains unresolved; this is not a full-suite pass. The accepted R1 correction and unchanged reviewer test remain intact. Fresh source review and full hosted CI on this correction remain required. No push, merge, activation or O5 work was performed.

## Source identity and scope

- Entry: `eba7efd6a97c2c2e05f88056b8d1d56c1a86661e`, tree `adb7562369490a754aa01943254c0da12ebf7fe0`.
- Tested executable source: `ac8c80c97161a95e6a9f84de6012149dbcc8a544`, tree `62273699d6277264fdc226e2f7031ddb5e3dc512`.
- The final documentation commit/tree is in package identities.json. All 1,885 project PHP files match the tested/final Git source and full-gate before/after manifests.
- Only two existing runtime files change: StrictJson's ordinary-string-byte scanning and CanonicalJson's scalar traversal. Three PHP test/oracle files are additive. No original test/fixture, workflow, dependency lock, service configuration, approved artifact or consumer/authority/journal implementation changes.

## Measured diagnosis

The supplied review accepts R1 source and four independent hosted consumer cases but records full hosted cancellation at the 30-minute limit. That supplied evidence applies to the entry source, not this correction.

Four serial isolated runs used the unchanged AssignmentAssessmentResolverTest with diagnostic method timers: entry 171.214 seconds; scanner-only 157.421; rejected reference cache 164.832; final scanner/traversal 145.461. The final measured elapsed reduction against entry is approximately 15%. Extra fine-grained timers were added after the first run; these are diagnostic comparisons, not precise hosted speed guarantees. The final run also included incidental source-manifest file reads; no other test cohort overlapped.

Entry/final canonical encode counts both equal 10,019,483; strict parse counts both equal 104,865; journal latest counts both equal 113. Canonical encode time decreased from 45.728 to 35.949 seconds; strict parse from 21.695 to 11.886; journal latest from 42.606 to 36.547. These are inclusive, overlapping measurements and must not be summed. The discarded reference cache reduced encode calls but worsened elapsed time, so it is absent from the candidate.

The separate opening-test selection passed 40 tests in 42.896 seconds of PHPUnit time. Its slowest test was the lexical mutation matrix at 10.728 seconds. Per-test JUnit and serial-per-test-timings.json distinguish that work from producer-heavy assignment cases. No claim is made that R1 alone caused the gate timeout.

A further serial diagnostic distinguishes native journal operations and lock acquisition. It passed in 137.844 seconds, with 135.219 user CPU seconds and 2.000 kernel CPU seconds. Wall-minus-CPU also includes startup, scheduling and instrumentation effects; it is not purely I/O wait.

| Native diagnostic operation | Calls | Inclusive seconds |
| --- | ---: | ---: |
| atomic.fopen | 113 | 0.016833 |
| atomic.flock.2 | 113 | 0.001019 |
| journal.glob | 113 | 0.098963 |
| atomic.flock.3 | 113 | 0.002200 |
| journal.file_get_contents | 2808 | 1.062210 |
| journal.json_decode | 2808 | 14.001411 |

This measured run spent almost all elapsed time on CPU work. Lock acquisition and file reads account for little of its duration; native journal JSON decoding remains a measurable computation cost.

## Correction and safety properties

strcspn scans ordinary bytes until a quote/backslash; the entry escape/offset/json_decode checks remain. Canonical sorting recurses only for arrays because the old function returned non-arrays unchanged. Differential tests compare valid and malformed bytes, escapes, controls, Unicode, long strings, size/depth limits, nested/scalar types, numeric keys, JsonSerializable side effects, references and encoding failures. Original R2 scope tests pass. No new cache remains.

All signature/currentness/revocation, original-evidence reconstruction, aggregate/instance/Profile/configuration binding, generation, resource/custody and irreversible-fence checks remain. Journal chain validation and fault/restart behavior are unchanged. No nested journal lock, second journal or lock spanning external I/O was added. Positive authority histories remain real synthetic producers.

## Frozen-source validation

Cohorts below ran serially. They are selections, not substitutes for the full gate, and historical overlapping cohorts are not added to them.

| Cohort | Tests | Assertions | External seconds | Exit |
| --- | ---: | ---: | ---: | ---: |
| pure-final | 9 | 2095 | 1.320 | 0 |
| initial-prefix-final | 40 | 6919 | 44.270 | 0 |
| binding-final | 4 | 62 | 980.194 | 0 |
| compatibility-final | 221 | 668 | 661.406 | 0 |
| static-final | â€” | â€” | 0.561 | 0 |
| lint-final | â€” | â€” | 4.682 | 0 |
| kernel-final | â€” | â€” | 0.589 | 0 |

The unchanged reviewer probe reports `O2_SETTINGS_FOREIGN_AGGREGATE` and zero transport calls. Both ordinary/custodied positive execution and recovery, wrong aggregate/instance/Profile/configuration, stale replacement and credential-boundary currentness loss remain covered by the unchanged accepted consumer tests.

Full command: `C:/php/php-8.4/php.exe vendor/bin/phpunit tests`.
Started `2026-09-13T02:41:56.496990+00:00`, finished `2026-09-13T03:11:57.218917+00:00`; exit 124, external duration 1800.722 seconds, unchanged 1,800-second ceiling. Complete log and source manifests are included. A timeout has no complete PHPUnit total and is not relabeled as success. The final visible progress was 45 completed tests, with no complete summary. Further measured performance work is required to meet the unchanged allowance. No fresh hosted run was dispatched.

## Exact packaging and disposition

The comparison generator now reads exact Git blobs for both entry and final. It does not use historical/current worktree text or line-ending conversion. The repaired 3,440-row historical R1 comparison exactly equals the supplied independent comparison, including the four unchanged older O4 documents. The current comparison additionally establishes preservation from the accepted R1 entry to this correction.

The packet includes all final PHP, complete source archive, four independently reconstructed patches, incremental bundle, exact identities, inner review ZIP/checksum, complete manifests, current/earlier logs and the measured rejected prototype. Earlier delivery ZIPs and runtime roots are excluded. Operational flags remain false; actual retry allowlist is empty. Source review and fresh full hosted CI remain pending, and O4 is not declared closed.
