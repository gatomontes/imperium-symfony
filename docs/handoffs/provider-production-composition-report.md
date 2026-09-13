# PPC0 local implementation report

Disposition: **PARTIAL_BLOCKED**. Dormant composition and bounded file custody are
implemented for source review. All five factual production evidence ports retain
their named refusals. Windows PHPUnit and exact aggregate pass, but complete CI
acceptance is blocked by one unchanged guard's symlink privilege error and the
absence of Linux CI. No publication/integration or commissioning is claimed.

## Source identities

| Item | Exact identity |
| --- | --- |
| Isolated worktree | `E:\htdocs\imperium-provider-production-composition` |
| Local branch | `codex/provider-production-composition` |
| Clean launch commit | `111844a2820888eb4baaddd4c4169a18ac7289a2` |
| Launch tree | `b09716f51643ae601606b42adf7ad1ca93be2768` |
| Tested executable commit | `fcf4c2fb02f1ee610bece7389949cbbdbf61beb0` |
| Tested tree | `9dd8b20609d661b8ffea6814e408622e8fd189a0` |
| Aggregate source digest | `57c24024af9b863c733b6e6d7a5d241883c02911071ff877b5881b3c1f02662c` |
| Final commit/tree | Exact values in the packet's `identities.json`; the commit containing this report is documentation-only after tested code. |

Fetched origin and created the worktree from the preparation branch. No earlier
worktree was removed/reset, and no installed application was a test root. No
applicable AGENTS.md was found in this checkout or checked ancestors. O0–O5 remain closed.

## Implementation and exact blockers

`Onboarding/Deployment/Composition` builds the existing owner graph lazily. It
shares one AuthorityStore and one FileKeySource throughout verification/delivery,
one BaseProjection across access/founding, and the same AssignmentEvidence and
AugurAdapter for application and PersistentSettings. Preview/status use the original
reader without constructing credential-bearing infrastructure. Resume uses the
existing evidence-only path. There is no service alias, enrollment or provisioning.

`FileKeySource` reads bounded public generation separately from immutable private
generation files and holds an existing shared lock through callback delivery.
Cooperative rotation requires exclusive custody. Missing, malformed or busy custody
refuses without repair; callback errors are redacted without exception chains.
The contract explicitly requires trusted directory ownership, immutable generations,
no rollback and a tested local filesystem. PHP does not guarantee secure memory
erasure or race-free path opens. No actual deployment filesystem policy was observed.

See the [component/evidence/blocker matrix](../provider-production-composition-evidence-matrix.md)
for candidate owners, supported checks, concrete counterexamples and smallest missing
producers, and the [custody contract/future operator runbook](../provider-production-custody.md).

| Port | Unsupported facts and retained refusal |
| --- | --- |
| Access | Current account/credential-bound entitlement and zero-fee access: `O3_ACCOUNT_ZERO_FEE_EVIDENCE_MISSING` |
| Base | Authenticated finite-universe eligibility, predicates, bounds and costs: `O3_AUTHENTIC_BASE_EVIDENCE_MISSING` |
| Constitution | Competent FRESH founding provenance and exact resident charter/persona/profile: `O3_AUTHENTIC_CONSTITUTION_EVIDENCE_MISSING` |
| Cognition | Exact-wire tokenizer/framing/context/output and model/account tariff plus substantive claim evidence: `O3_AUTHENTIC_COGNITION_EVIDENCE_MISSING` |
| Assignment | Substantive Profile fit and current exact binding/Profile generations: `O4_CURRENT_ASSIGNMENT_EVIDENCE_MISSING` |

No affirmative production verifier was fabricated. In addition to the matrix's
owners, Clavium/ClaimBoundCredentialBroker delegates to CredentialBroker without a
public generation source. GovernedStationaryCredentialResolutionV2Service maps only
AgentMail's credential family and writes native proof records under a different
admission/activation chain. Neither supplies this DeepSeek custody/evidence port.

DeepSeek/API key, FRESH, D2-A, approved P1–P9, medium capacity, least-cost eligible
initial base and persistent operator-controlled settings remain unchanged. Frozen
contracts, AtomicTransition.php, config/services.yaml, gate/workflow and historical
pins are unchanged. TokenEvidence/Tariff remain projections. Unknown dispatch keeps
maximum exposure; no retry, reset or refund shortcut was added.

## Fresh validation: Windows, not Ubuntu

PHP 8.4.14 ZTS Visual C++ 2022 x64; PHPUnit 13.3.0; Python 3.12. Composer used
locked `install --no-interaction --no-progress --prefer-dist --no-scripts`, exit 0:
82 installs, zero updates/removals. No dependency definitions or services changed.

| Validation | Actual result |
| --- | --- |
| Initial new boundary tests | 7 tests / 38 assertions, exit 0; precommit diagnostic |
| Committed focused credential/O5/composition regression | 15 tests / 233 assertions, exit 0, 394.280 seconds |
| Eight complete partitions | All final exits 0; 3,712 tests / 63,995 assertions; zero failures/errors/skips |
| Exact case/file/source aggregate | Exit 0; 609 files and every enumerated case exactly once, one committed source digest |
| Longest partition | 1,517.940045 seconds JUnit; 1,518.922 seconds runner wall time |
| Warnings | Four existing crash-demonstration tests assume `.git/HEAD` is a directory path; worktrees use a `.git` file. Separate warning diagnostics retained. |
| Unchanged 11 guards | 10 pass, one error: test_changed_file_type_fails cannot create a symlink, WinError 1314; exit 1 |
| Linux / remote CI | Not run. Only docker-desktop WSL was listed; Docker Linux engine pipe unavailable. No service/daemon started. |

The native partition command failed on Windows at its Unix executable invocation
(WinError 2). The external windows-gate-launcher.py inserts `php` for that exact
invocation and strips Windows extended-path prefixes from PHP arguments. Repository
gate bytes, enumeration, hashing, partitions and guards are unchanged. Initial path
failures for partitions 0 and 6 remain in diagnostic logs; both final reruns pass.
The parent launcher's exit 1 reflects those initial failures, not the final eight
selections. This host adaptation is not represented as Linux CI acceptance.

The launcher UTC interval was 2026-09-13 18:20:44.191503 through 18:46:08.844802.
Evidence retains commands, per-partition UTC/native exits, JUnit, selections,
enumeration and source-before/after digests. For the two path-corrected reruns,
PHP process starts and log/metadata publication UTC times are retained; exact wrapper
starts were not instrumented. Initial focused commands retain elapsed time but not
exact UTC starts; those gaps are not filled with guesses.

New tests cover synthetic access delivery, refused admitted synthetic facts, rotation,
fresh-process custody, locking, malformed bytes, foreign root and pure observation.
The focused O5 regression includes full synthetic journey, unknown/resume and process
recovery. The complete suite reruns existing source-identity, stale/conflicting
provenance, unsupported tokenizer/tariff, Profile and persistent-settings checks.
There is no new affirmative production journey: those producers remain missing.

Symfony regenerated reference PHPDoc during tests. The unchanged source gate verified
executable-token equivalence; the generated reference was restored afterward. A
post-diagnostic source check matched the tested digest before this report was written.
This report and the source-assessment cross-link are post-test documentation only.

## Packet and stop boundary

The public review ZIP contains final committed source, source file/byte/SHA-256
manifest, implementation/documentation/complete diffs, bounded Git bundle, bundle
verification, identities and public validation evidence. Its prerequisite is the
launch commit above. VERIFY.md supplies import and hash checks; the outer ZIP has
a separate SHA-256 file. Vendor, private runtime roots, generated fixture credentials
and untracked environment files are excluded. Review is the implementing agent's;
no independent or human review is claimed.

Before integration, run the unchanged complete CI on a suitable host with all 11
guards passing. Resolve the five factual producers before genuine dispatch is
proposed. Provisioning, deployment, commissioning and installation cutover require
separate decisions. **DEFER_ENROLLMENT** remains; actual retry allowlist is empty.

`deployment_approved=false`, `enrollment_authorized=false`, `live_ready=false`,
`activation=false`, `execution_authority=false`. Work stops at local commits and
this review packet. No push, PR, deployment, real credential use, real provider
request, native appointment or mission was performed.
