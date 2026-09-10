# O2-B0 correction — source and evidence review

Verdict: PASS — R1–R4 are resolved, fresh full CI passed, and corrected O2-B0 is integrated through PR #788. No unresolved blocker remains for this offline batch. This review changes no author source. Its acceptance is confined to O2-B0's offline original-admission/current-resolution boundary, not live commissioning.

Reviewer provenance: this reviewer authored earlier specifications and the four regression requirements. This is an attributed source/evidence review; it does not claim independent authorship. The prior HOLD report and failed CI remain historical evidence, unchanged.

## Findings disposition

| Finding | Corrective mechanism and evidence | Disposition |
| --- | --- | --- |
| R1: expired signed-mode slots admitted fresh originals and resolved as current | Admission::signedTerms now requires one exact matching slot and checks its half-open lifetime using the trusted clock under the owning lock. Both admission and current resolution call it. Multiple matches refuse, so a live alternate cannot conceal expiry. Original reviewer cases are unchanged; new tests cover before/at/after expiry and historical recognition. | Resolved |
| R2: incoming copies bypassed retained-source revocation | Admission traversal checks already retained originals through checkSource before accepting resupplied bytes, preserving first provenance. The traversal also carries its path, depth and shared visit counter across the retained-source boundary. Positive resupply and both ordered revocation/admission outcomes have production-path tests. | Resolved |
| R3: expired policy resolved while its signing act remained valid | Act::verify independently checks a policy object's own creation/expiry interval. This applies to current policy originals and supporting sources whose first admission was that policy. Tests deliberately keep the act alive beyond policy expiry; original/current/retained-source paths refuse at and after expiry while historical replay remains unchanged. | Resolved |
| R4: two runtime candidates missing from coverage inventory | Two explicit rows classify Admission.php and Resolver.php through the existing additive inventory. Their descriptions retain dormant, non-consuming semantics. Existing rows, frozen snapshots, counts and reviewer/tripwire assertions are unchanged. | Resolved |

The ordered subprocess tests start both actual signed producers against one head, release them in each deterministic order, observe stale-head refusal for the second, then submit fresh authorization at the new head. Revoke-first blocks new dependent admission; admit-first becomes non-current after revocation. This is coordinated ordering evidence, not a claim of simultaneous probabilistic contention. The original admission-contention and interruption/restart tests remain in the suite. No power-loss durability claim is added.

## Rerun verification

The complete uploaded archive passed CRC, safe unique paths, inner SHA-256, exact inner/outer membership and all 45 manifest payload checks (48 outer entries). Eleven correction paths match canonical Git blobs, source copies and the exact binary patch. Twelve documentation links resolve. Entry, tested and final commits/trees and both ancestry relationships match.

All **1,773 exact tested PHP byte streams** were compared to both the tested and final Git blobs and supplied hashes: identical, with zero normalization differences. Five post-test paths are documentation only and match post-test-documents.patch exactly. All **3,270 other tracked blobs** are unchanged. Original reviewer tests and the inventory's complete pre-existing byte prefix are unchanged. This resolves the previous missing-tested-bytes attribution gap for the correction; it does not rewrite the older Windows evidence claim.

All **86 Python specification checks** were rerun successfully here. These remain static/abstract checks, not cryptographic, PHP or live-provider proof. Bundle verification, diff whitespace checks and publication/CI tree comparisons passed. The legacy inventory's original non-UTF-8 byte was preserved using binary-safe publication; its old bytes were not normalized.

## Inspected author checks

The uploaded local logs report PHP 8.4.14 / PHPUnit 13.3.0 / Composer 2.8.12. Initial reproduction shows all six earlier failures. Final selections report:

| Selection | Tests | Assertions |
| --- | ---: | ---: |
| O2 admission/process, original reviewer requirements and correction tests | 142 | 318 |
| O1/source-pin and relevant journal/trust regressions | 384 | 1,546 |
| Batch12 and frozen-coverage tripwire suites | 16 | 3,984 |
| Total across distinct final selections | 542 | 5,848 |

All final selections report exit 0 and no skips. Five changed/new PHP files lint and the test kernel boots. The development pass is excluded from totals. These are inspected author results, not local reviewer reruns. PHP/Composer are unavailable in this scratch environment. Fresh hosted full CI is separately recorded below.

## Source identities

| Role | Commit | Tree |
| --- | --- | --- |
| Correction entry / original review branch | 3170f6fb2e11f1fae8158ae751aac84ac45c9e62 | 48b2cbb324285d46068eb50c6fd62b9079f91ddc |
| Author tested source | 60de43155b42bbe6bd2315765b45428f83b1da96 | d149b7875bdf58472741dcd275e0f7391e1bb7eb |
| Author final documentation | ae7efc693f58afd0d06bf0957e200dbe04d300df | e2070c7a2184c7481882f27297c976d248b2c2be |
| Published corrected PR #788 head | 4001f08d343d289cecb913633961bd90b7797fcf | e2070c7a2184c7481882f27297c976d248b2c2be |
| Fresh CI temporary merge | ce8b41bed35dbc3ee097f3a9d010314ff6fac784 | e2070c7a2184c7481882f27297c976d248b2c2be |

Publication preserves the exact final tree on the existing review branch without a force push. The delivered bundle preserves original review and author correction history and requires integrated preparation bf8e60d7b6bfc28cbb781e8615c2e3a90a48f36a. No reviewer source edits were added to the correction.

## Authority and commissioning scope

The actual graph remains separate deployment-admin trust enrollment → exact signed Operator originals/policy → retained, currently valid source chain → bounded static resolution. O2-B1 must re-resolve and atomically consume S/P authority, steps/slots, claims and budgets before progressing effects. B0 receipts do not complete F2 effects. O1 proposals and foreign native/formation competence do not confer bootstrap authority; unsupported institutional and existing-installation paths refuse.

DeepSeek/API key, FRESH, D2-A and P1–P9 remain fixed. Target roles use medium capacity; initial Augur base remains least-cost eligible. Three retries means four attempts per assessment and twelve total within the approved $0.10/$1.20 limits, but the actual safe-retry allowlist remains empty. Persistent operator-controlled assignments, DEFER_ENROLLMENT, unresolved B1 remote guarantees and all five false authority flags remain unchanged.

CY/FC accepted offline scope and O1 acceptance are preserved. No provider facts, authentication, credentials, installed state, real enrollment, appointment, assignment or activation were exercised. Genuine deployment custody and later dynamic source/producer prerequisites remain commissioning conditions, not manufactured by synthetic tests.

## Next executable action

After successful integration, prepare O2-B1's shared sequence/command/step ledger, atomic S/P consumption, bounded claims, replay and unknown-outcome/custody fences against the actual merged B0 interfaces. This is the next of five remaining implementation batches across O2–O5. O2 remains open until B1 is completed and reviewed. Use the attached preparation prompt; it requires a bounded contract and implementation handoff before runtime changes. Historical HOLD/local-pending reports retain their original provenance and should be linked from the next current-status update, not rewritten as past acceptance.

## Final hosted gate and integration

[PR #788](https://github.com/gatomontes/imperium-symfony/pull/788) merged as `cafa93f9665f0e5734f26491a092b28995e1cf14`, tree `e2070c7a2184c7481882f27297c976d248b2c2be`. Its parents are the integrated preparation `bf8e60d7b6bfc28cbb781e8615c2e3a90a48f36a` and corrected published head `4001f08d343d289cecb913633961bd90b7797fcf`. A fresh fetch verified main and exact tree equality to the author correction and actual CI checkout. No source changed after CI.

Fresh [CI run 34522234611](https://github.com/gatomontes/imperium-symfony/actions/runs/34522234611), job 103022324183, passed on PHP 8.4.25 / PHPUnit 13.3.0: **3,463 tests, 55,572 assertions, 4 skips**, elapsed 07:03.371. The log identifies temporary merge `ce8b41bed35dbc3ee097f3a9d010314ff6fac784`. This fresh run includes the unchanged reviewer requirements and both previously failing inventory checks. No local reviewer PHP run is claimed.

O2-B0 is accepted within its offline scope. O2-B1 remains to complete O2; five implementation batches remain across O2–O5. No live commissioning or operational authority is enabled. integration-identities.json and ci-run.log preserve the exact outcome and log hash.
