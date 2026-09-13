# PPC0 source and evidence review

Review date: 2026-09-13. Source verdict: **NO BLOCKING SOURCE FINDINGS within the documented custody contract**. Integration verdict: **ACCEPTED for dormant composition and bounded file custody**, merged through PR #809. Production disposition remains **PARTIAL_BLOCKED** because all five factual evidence producers are unavailable. [Machine-readable integration record](../provider-production-composition-reviewed-integration.json).

The review was performed by the receiving assistant independently of the supplied implementation report. No human or separate-agent review is claimed. The accepted scope is dormant composition and bounded file custody, not a complete production onboarding implementation.

## Verified package and source

| Item | Verified identity or result |
| --- | --- |
| Uploaded packet | `imperium-ppc0-public-review.zip` |
| Packet SHA-256 | `6360ca7237ddaf7f3985da19f5543371d8f0492fd09b189eaa4ec8d4460f3896` |
| Outer manifest | All 11 listed payload sizes/hashes match; no extra payloads |
| Source archive | All 3,497 files match the complete source manifest, including Git file modes/tree |
| Published launch | `111844a2820888eb4baaddd4c4169a18ac7289a2`, tree `b09716f51643ae601606b42adf7ad1ca93be2768` |
| Producer tested source | `fcf4c2fb02f1ee610bece7389949cbbdbf61beb0`, tree `9dd8b20609d661b8ffea6814e408622e8fd189a0` |
| Producer final source | `c3f339b36649c8a77ed6d1516f353f3738f634b2`, tree `fcc17865be3eefbca0c43eb3c88a3c5acc075651` |
| Review candidate | `c58daf1e5d6e1d67f02928597f35b10e80a07fa6`, exactly the producer final tree |
| Review PR | [PR #809](https://github.com/gatomontes/imperium-symfony/pull/809) |

The source archive independently reproduces the final Git tree. The launch object was matched to its published GitHub identity; the bounded bundle verifies against that prerequisite. Tested/final commit trees and the complete, implementation and post-test diff bytes match the imported Git objects (using the producer's eight-character diff abbreviations). The final producer worktree was clean after import.

The eight changed paths add two runtime classes, one seven-test class, one fresh-process test worker and documentation. Existing executable owners, frozen contracts, dependency definitions, service configuration, workflow and coverage guard are unchanged. The producer's post-test delta is exactly the report and source-assessment cross-link, both Markdown.

## Source conclusions

`Composition` is excluded from service discovery and adds no alias. Advance/resume/settings construct one store-bound graph lazily, with the same key-source instance for verification/delivery, shared base projection and shared assignment evidence for publication and persistent resolution. The ordinary default gateway remains unchanged. Preview/status use the existing reader without constructing credential, envelope, adapter or runtime infrastructure. Resume delegates to the existing evidence-only owner; no delivery API is added.

`FileKeySource` separates public generation reads from private immutable-generation bytes, bounds both reads and rejects malformed generation/key content. It uses an existing nonblocking shared custody lock and holds it through delivery. The existing runtime checks generation between capability checkpoints. Delivery exceptions are replaced without retaining their messages or exception chains. Missing or busy custody refuses without provisioning, repair or retry.

These conclusions rely on the declared trusted provisioner and local-filesystem contract: immutable generation files, no generation reuse/rollback, exclusive cooperative publication, stable lock/ancestor ownership and enforced OS access controls. The code does not enforce those administrative facts, provide race-free path opening, secure memory erasure or HSM isolation. Those are disclosed limits, not properties inferred from a passing test. Response storage retains its existing disclosed power-loss durability limits.

The five missing ports remain refusing: account/access, base eligibility, constitution/resident artifacts, cognition resources/substantive claims and current assignment/Profile facts. The candidate owners identified in the [evidence matrix](../provider-production-composition-evidence-matrix.md) do not supply the complete required scope. No successful fixture oracle was promoted into production. Signatures, hashes and structural admission remain distinct from the factual predicates these ports require.

## Validation attribution

The reviewer reran the unchanged aggregate verifier over the supplied Windows evidence: **3,712 tests / 63,995 assertions / zero skips**, 609 files, every enumerated case exactly once, source digest `57c24024af9b863c733b6e6d7a5d241883c02911071ff877b5881b3c1f02662c`. All seven new tests are present and passing in that evidence, with 38 assertions. This verifies retained evidence consistency; it is not an independent Windows execution.

The producer reports PHP 8.4.14, PHPUnit 13.3.0, four worktree-related warnings and one Windows symlink-privilege guard error. These raw results remain historical and are not relabelled green. Reviewer-local Python guards passed nine checks and skipped two because PHP is absent from this review container; no reviewer-local PHP run is claimed.

Fresh hosted Linux [run 34776082266](https://github.com/gatomontes/imperium-symfony/actions/runs/34776082266) passed **3,712 tests / 63,983 assertions / four explicit skips** across all 609 files and every enumerated case exactly once. All eight workers, the aggregate job `103776490252` and all 11 guards passed without guard skips. PHP 8.4.25 / PHPUnit 13.3.0; longest partition 916.889285 seconds. The complete workflow ran 18:54:54–19:10:41 UTC (15 minutes 47 seconds).

The CI checkout `909ad9874e2c79e699b9caad5138d328b87e3fac` and accepted merge `5e3c076dba3a147f1a1272641b40db740d7f393c` were fetched independently and both reproduce tree `fcc17865be3eefbca0c43eb3c88a3c5acc075651`. Merge was pinned to the accepted head. Aggregate source digest: `2c1a98f6078e1d27280cadea65ebf19c6d408001e82b9e9301da7230763ab8cc`. This fresh hosted gate resolves the integration-validation requirement without changing or relabelling the Windows evidence. It proves complete partitioned execution, not serial shared-process ordering.

## Disposition and remaining work

Accepted and integrated engineering subset: dormant composition plus file custody under the documented contract. PPC0 as a production capability remains **PARTIAL_BLOCKED**. Resolve the five competent evidence sources and deployment-specific custody/authority inputs before proposing genuine dispatch. The [future operator runbook](../provider-production-custody.md) remains a proposal. No further implementation campaign is silently selected by this acceptance.

O0–O5 remain closed within their accepted offline scopes. `DEFER_ENROLLMENT`, existing-installation cutover deferral and the empty actual retry allowlist remain. `deployment_approved`, `enrollment_authorized`, `live_ready`, `activation` and `execution_authority` all remain false. No installation, real credential, live provider, enrollment, appointment or mission execution was used in this review.

This review is post-test documentation and is not part of the candidate's frozen runtime tree.
