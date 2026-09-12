# O3-B1 R2 CI runtime correction

The measured correction substantially reduces the O3-B1 fixture cost, but the complete local suite still hit the unchanged 1,800-second external ceiling (exit 124 after 1800.309 seconds). This is an unresolved local full-suite timing limitation, not a pass; no complete test/assertion total is claimed. Separately, all 883 selected campaign tests / 8615 assertions passed. This is fresh local evidence, not hosted CI acceptance. Independent source review and a fresh complete hosted run remain required. No push or merge was performed.

## Exact identities

- Preparation: `02ce09621c63822eaff9ffd23151d5d3aed593a4`, tree `2fc0e9075ce199312797533d237652fa15b82865`.
- R2 entry: `9c1e953b9d580010ed4bbf684b7fc215de770f27`, tree `7389ad3901e79ace26882e9c37e8eedbfc9f9155`. Tracked state was clean. All four existing untracked report/instruction copies were preserved.
- Tested source: `e913a83dc1073be8e7fa236acafb2d05fa993c27`, tree `4c830a0163a4378c3af903de01e151c2d6fe7fa4`. All 1853 tracked PHP byte streams match Git and the final checkout.
- Final documentation commit/tree: `identities.json`. No PHP changes follow the tested commit.

## Measured cause and correction

The unchanged authentic AugurCognitionFixture creates real temporary-root signed admissions, selected base, mapping, FRESH holder and mock cognition. An external autoload profiler instruments exact checkout method bodies in memory with hrtime entry/finally measurements. It does not inject authority or alter fixture behavior. Both profiling source-hash sets match their recorded entry/tested Git blobs. The transformer, complete logs, phase timings, frame counts and journal byte totals are included. Measurements are Windows PHP 8.4.14 ZTS with the same locked dependencies; they are not hosted timing predictions.

Before correction, readiness made 67,981 strict-decode calls and spent 53.379 of 78.128 seconds there. W1 made 194,448 strict-decode calls and spent 178.826 of 252.465 seconds there. Canonical encoding was the next cost (16.365 seconds in readiness, 50.740 seconds in W1). Readiness journal traversal took 1.497 seconds. These are measured attribution, not the review's static leads.

| Phase | Before seconds | Final seconds | Speedup | Frames before / after | Journal bytes before / after |
| --- | ---: | ---: | ---: | ---: | ---: |
| fixture | 0.309 | 0.268 | 1.15x | 4 / 4 | 2102607 / 2102607 |
| ready | 78.128 | 17.966 | 4.35x | 17 / 17 | 16189941 / 16189941 |
| W1 | 252.465 | 41.575 | 6.07x | 24 / 24 | 24755803 / 24755803 |

Measured authority and traversal call counts match before and after, including Act::verify, checkSource, StateValidation::run and journal calls. CanonicalJson::encode calls decrease because exact immutable record encodings are reused. Structural validation and journal traversal still execute. A parse-only implementation was also measured and tested: its complete local run timed out at 1,800 seconds (exit 124). That superseded commit, exact PHP snapshot and full failed output are preserved separately under evidence/development/final-round-01.

StrictJson now offers a synchronous validation-call scope containing at most 128 successful parses and 2 MiB of retained input. SHA-256 is only an index: complete input bytes must match before reuse. Failed parses are never retained. Cache overflow falls back to the unchanged parser. The original 1 MiB input/depth/encoding/duplicate-key checks remain; size is checked before hashing. Returned arrays have value semantics, and no caller can supply parsed results. The scope clears in finally, including exceptional exits; nested synchronous validation shares only this bounded pure computation.

Rules::hash and Rules::same can reuse canonical encodings only when the entire input strictly equals a privately retained parsed record (complete or without record_digest). Schema and ID only locate a candidate; they are never sufficient for reuse. Encoded strings have a separate 2 MiB cap. Changed fields, claimed digests, arbitrary objects and all misses use the unchanged CanonicalJson encoder. Invalid UTF-8 errors and JsonSerializable behavior remain visible. No validity or comparison result is retained.

The scopes cover BaseProjection, Augur context reconstruction, source traversal and LedgerState validation. Every boundary still observes its actual state, current originals, clock, revocations, root and KeySource generation. No signature result, source authority, ledger validity, prepared operation, holder, commission or outcome is cached. Retained-byte comparison, signature verification, graph visit/depth/cycle bounds and all state/migration/history checks still execute. The journal, its serialized bytes and locks were not changed. No scope extends the existing lock over credential or HTTP I/O.

## Compatibility and final proof

R1 MappingLimits and its five exact meter constraints are unchanged. All original tests/fixtures, reviewer additions, full workflow/command/30-minute allowance, approved originals, frozen inventories, dependencies and service configuration remain byte-identical. Only six existing onboarding PHP files change, plus one new parser-scope regression file. Both migrations and original producers/consumers remain intact.

OnboardingParseScopeTest checks exact-byte identity, nested and exception lifetime, bounded retention, continued validation when full, returned-value and PHP-reference mutation isolation, invalid duplicates/depth/size, changed retained bytes and claimed digests, encoding errors and object side effects, bounded canonical storage, fresh time/revocation checks within warm parsing, and unchanged graph traversal limits. Original complete FRESH/W1/W2/W3, mapping/reviewer/currentness, no-fit/semantic/usage, KeySource, admission, crash/restart, owner, FC, source-pin and inventory cases were rerun unchanged.

The full command was `php` with the recorded offline transport restrictions followed by `vendor/bin/phpunit tests`. Its external ceiling remained 1,800 seconds. Any skips in a completed result are reported above; an incomplete run has no final totals. The complete command retained every original test and fixture without new skips or filtering. Fresh full hosted CI remains pending. Selected runs are separate and are not added to the full-suite count.

| Selected campaign | Tests | Assertions | Exit |
| --- | ---: | ---: | ---: |
| parse-scope | 6 | 338 | 0 |
| correction-reviewer | 1 | 2 | 0 |
| new-o3-b1-bridge | 1 | 31 | 0 |
| correction-mapping | 16 | 130 | 0 |
| new-o3-b1-owner | 7 | 106 | 0 |
| new-o3-b1-base | 11 | 53 | 0 |
| credential-correction | 8 | 60 | 0 |
| reviewer | 4 | 10 | 0 |
| native-root-legacy | 4 | 291 | 0 |
| o3-b0 | 68 | 304 | 0 |
| new-o3-b1-refusals | 10 | 57 | 0 |
| o2-b1-b0 | 211 | 952 | 0 |
| o1-pins | 380 | 1519 | 0 |
| inventory | 16 | 4002 | 0 |
| fc | 140 | 760 | 0 |

Changed PHP lint, non-debug kernel smoke, locked dependencies and all 86 specification checks passed. Exact commands, timing, exits and full public output are in final-commands.json and evidence/final. The source snapshot/hashes cover every tracked PHP file. The full suite regenerated config/reference.php PHPDoc; its exact generated bytes and comment-only diff were retained before restoring the committed original, following the existing tools/verify-courtyard.py convention. full-post-test-source.json records that reconciliation before the selected runs; post-test-php-comparison.json confirms the final byte match. Failed initial development expectations (canonical array ordering and which expiry check fires first) and superseded measurements are separately labelled under evidence/development and evidence/profiling; none is final or hosted proof. Supplied hosted cancellations and R1 acceptance are review input only.

## Review boundary and deliverables

All five operational flags remain false and the retry allowlist remains empty. Only synthetic temporary roots and mock transport were used. No real provider, credential, installed-state, live commissioning, O4 or O5 operation was performed.

The package preserves preparation-to-final and R2-entry-to-final patches/bundles, independently reconstructed trees, prerequisites, canonical changed files, both protected-original comparisons, unchanged reviewer tests, complete PHP snapshot/hashes, public profiling and validation evidence, proof matrix and manifests. The inner review ZIP has SHA-256; the outer ZIP intentionally has no checksum. Historical delivery archives, private evidence and stored runtime state are excluded.

The complete local result is stated at the start of this report and recorded separately in full-suite-result.json. Acceptance still requires the fresh complete hosted run on the returned source: local timing does not certify that host or authorize integration. Stop for independent source review and that full hosted gate before push/merge.
