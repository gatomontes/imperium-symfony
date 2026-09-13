# O4-B0 CI completion V2 independent review

Disposition: source review and bounded independent regression checks pass; O4 acceptance remains HOLD. No blocking correctness defect was identified in the reviewed delta. This is not proof of full-suite completion or permission to merge.

## Source and integrity

All 382 payload sizes and SHA-256 hashes verified. The complete correction patch independently reconstructed all 3,464 tested files. Their reconstructed Git tree is `c7f9f5b66698f7450ddcb1661fd8a0595da7b598`, matching the uploaded tested identity `c2dc4cb647877e4057214355f6c4d33d9f365c5f`. Entry archive bytes match the existing GitHub candidate tree `892afbd68ad62bdc2eba9bc14927dff2e72827d2`.

Four existing PHP files changed: FormationJournal, Rules, StrictJson and CustodyCoordinator. Eight PHP files were added, including JournalCanonicalHash, five test classes and two entry-comparison helpers. All 3,452 other existing files are unchanged, including all original tests, fixtures, configuration, frozen documentation and dependency locks. All-file comparison rows were checked against actual archive bytes.

All 1,893 PHP files in each of the latest full-gate, pure, credential-consumption, foreign-instance and original-reviewer before/after manifests match the tested source. The source is now preserved separately at GitHub commit `dc9c815ae3e903c016809a604ff538c86aafbc22`, branch `codex/provider-onboarding-o4-b0-ci-v2-checkpoint`, with the exact tested tree. Integration PR #798 remains at its earlier candidate `730674d642e250edf0c489158de0c793559ecb3f`; no known-timeout candidate was promoted into that PR.

## Runtime review

Rules keeps validation before specialized reference encoding and preserves the canonical field order and JSON flags. Scalar comparison shortcuts exclude floats, arrays, objects and invalid UTF-8. Source-order comparison operates after refs validation and falls back for referenced child arrays, preserving their observable sorting behavior.

StrictJson indexes complete raw bytes and still compares the retained raw string before reuse. Numeric key conversion cannot authorize another raw value. The encoding shortcut still requires strict complete-value equality; count checks only reject impossible candidates. Unsigned representations remain private to the existing bounded scope. Neither identity nor digest is treated as proof of validity.

CustodyCoordinator opens pure-reuse scopes inside its existing locked callbacks. It retains currentness and authority observations and the existing custody stages. The selected scope test checks closure at external ports and after refusal; no persistence wrapper or lock held over provider activity was introduced by this delta.

JournalCanonicalHash is allocated for each traversal. Every frame file is freshly read, and every complete frame digest is freshly computed before the unchanged chain checks. Encoding candidates exclude floats, avoiding signed-zero equality mistakes. Matching native field fragments may reuse parsed arrays, with marker-conflict, byte, match-count, token and depth guards. The parser falls back to the original native decoder where it cannot establish safe substitution or shortened decoding fails. Depth limits account for cached subtree depth, including overwritten duplicate members. Retained values are private, bounded and cleared on exceptions.

These mechanisms add parser complexity, so the differential error/depth and adversarial journal checks are material evidence. They are not an exhaustive proof over every malformed input or environment.

## Independent hosted validation

[Run 34755946867](https://github.com/gatomontes/imperium-symfony/actions/runs/34755946867) passed **45 tests / 6,415 assertions** on PHP 8.4.25 and PHPUnit 13.3.0 in 20.404 seconds. This comprises the uploaded 44-test / 6,405-assertion pure/custody cohort plus an independent 1-test / 10-assertion journal freshness probe.

The new probe first reads a three-frame synthetic chain with repeated large fields, then changes historical bytes, recomputes a tampered frame's digest, changes predecessor linkage, and changes generation. Each corruption is refused; restoring the original file makes the next read succeed. This checks that earlier successful reads and reuse do not replace fresh chain validation.

Actual checkout `06c673a56ce27f9ea0a8dadccff16714e1060920` has verified tree `072332e9d98c24cc65e548d0283dad17e0383844`. That diagnostic tree differs from the tested production tree only by the selected workflow and added reviewer test. Diagnostic PR #802 is closed unmerged. Its workflow is not an acceptance-workflow change.

## Recorded performance and unresolved gate

| Recorded quiet largest-workflow source | External seconds |
| --- | ---: |
| Entry | 455.997 |
| Canonical-field correction | 397.467, 395.807 |
| Retained-parser correction | 344.679, 339.943 |

The two retained-parser runs are about 24–25% below the one quiet entry baseline, with the same selected test and 45 assertions. This is useful local selected-workflow evidence; it is not an independently replicated performance result or a full-suite speed prediction.

The latest frozen complete command still timed out at **1,800.219 seconds / exit 124**, without a final PHPUnit summary. The earlier corrected full run also timed out. Recorded production selections total 47 tests / 6,422 assertions, but overlap the independent selected validation and must not be added into a unique acceptance count. No new unchanged full run was launched in this review.

The supplied corrected-workflow profile attributes 9,543,137 CanonicalJson::encode calls and 65.326 exclusive seconds to its `other` bucket, outside the instrumented checkpoint group. It also attributes 45.553 seconds to journal decoding and 38.151 seconds to StrictJson canonical processing in that bucket. These are instrumented observations; inclusive times overlap and must not be summed. Immediate callers, bounded shapes and reuse misses remain the next useful attribution.

The packet honestly identifies two unavailable historical prototype file versions from the earlier pre-signed-zero-fix diagnostic. That limits reproduction of that intermediate experiment. Final retained source and the relevant frozen production manifests are reconstructible; no claim is made that every historical prototype was recovered.

Follow NEXT-INSTRUCTIONS.md. Full local completion and fresh independent full hosted CI remain mandatory before O4 acceptance. No merge or O5 transition occurred.
