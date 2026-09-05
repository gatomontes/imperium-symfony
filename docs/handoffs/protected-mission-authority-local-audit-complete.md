# Protected Mission Authority — local audit complete

`PROTECTED_MISSION_AUTHORITY_LOCAL_CANDIDATE_COMPLETE_PENDING_REVIEW_AND_OPERATOR_SETUP`
`DEPLOYMENT_ISOLATION_UNPROVED_OPERATOR_SETUP_REQUIRED`

STOP after local Batches 0–5. No publication, PR, merge, main change, real trust enrollment,
real Operator signing, previous blocked mission, real mission or provider/live trial is authorized.

## Exact revisions

Baseline: `0099c5f7e4008fe06cafc9c5dfa3458dc68f4db9`.
Audited implementation: `f12524f2b25942b8149e8c455a39da5d26217a9b`.
Audited tree: `de1f4f79c70b5106408729eafcaca4aa0d9ffb38`.
Branch: `codex/protected-mission-authority-operator-ceremony`.

| Batch | Commit |
|---|---|
| 0 | `a52c74a2ec6128af74140c6648b1deaf389a1563` |
| 1 | `d00529f6c1ea60e884e032f82e6015b12b7be39a` |
| 2 | `d7901c8ae768353fc1cdeac1c57ab207667d3969` |
| 3 | `eff6034ca96dca80c2cceadf7c9c557794b428fb` |
| 4 | `3cdc89b05e4faa6b353487755e409a58baac9bd3` |
| 5 implementation audit | `f12524f2b25942b8149e8c455a39da5d26217a9b` |

This handoff, flow update and evidence ledger form a later documentation-only commit. Full tests
ran on the audited implementation above, not on that later documentation commit. The final response
reports its exact HEAD/tree. The implementation comparison remained unchanged throughout testing.

Local main remains `b267e2c2b6a122694418ce59d2bf16319e602b07`.
Preserved remediation branch remains `8df34679beab0ba8699a68fdd458570bf658c4c8`.
Preserved older mission-thread branch remains `3c4890ffd30f403f72a35b92f1e639d51c8c98f8`.
No fetch was needed; no push, PR, merge or branch deletion occurred.

## Tests and evidence

PHP 8.4.14; PHPUnit 13.3.0; Windows; Git 2.51.2.windows.1; PowerShell 7.6.5.

| Run | Exact tested commit | Result |
|---|---|---|
| Batch 2 full | `d7901c8ae768353fc1cdeac1c57ab207667d3969` | 2656 tests / 52392 assertions |
| Batch 4 full | `3cdc89b05e4faa6b353487755e409a58baac9bd3` | 2662 tests / 52496 assertions |
| Audit focused | `f12524f2b25942b8149e8c455a39da5d26217a9b` | 16 tests / 221 assertions |
| Audit full | `f12524f2b25942b8149e8c455a39da5d26217a9b` | 2665 tests / 52515 assertions |

Every listed PHPUnit run passed with zero reported skips. Full command: `php vendor/bin/phpunit tests`.
Focused command supplies `tests/Imperium/Runtime/ProtectedMissionAuthorityBatch1Test.php` through
`ProtectedMissionAuthorityBatch4Test.php` plus `ProtectedMissionAuthorityBatch5AuditTest.php`.
Earlier per-batch focused results and initial failure/adaptation history are in the batch notes.
The earlier exploratory run overlapped source edits and is explicitly not exact-head proof.

The baseline contains 2649 tests. Batch 2 adds seven, Batches 3/4 add six, and audit adds three:
2665 total. Assertion changes arise from those new proofs, the added inspection transition,
stronger per-case target-hash checks and deterministic contention-outcome assertions. Historical
tests were not weakened. Windows platform-conditional coverage is not other-platform proof.

PowerShell ceremony: passed again on the audit commit. Installer: dry-run only, no accounts or ACLs
changed. Installation checker: wrong-path refusal exercised; real-account success remains unproved.
Five separate-process protocol probes confirmed serialization/root/key requests refuse, allowed
reads expose no issuer secret, and all five leave the journal unchanged.

Evidence ledger: `docs/protected-mission-authority-evidence-ledger.json`, containing commit/blob and
local SHA-256 bindings, exact test transcripts, sanitized artifact paths and historical disposition.
Local transcripts live under `var/protected-mission-evidence/` and are intentionally ignored.
Full audit transcript SHA-256:
`7b973f48fa9442a7ae6b0369b24b9ca1bde44e9a4fe3098bd3c1568294cb1564`.

The real CLI test used a newly created disposable Git repository and identity. It observed
ADMITTED → INSPECTING → COMPLETED, verified exact commit/tree/blob/committed bytes, preserved the
entire target file-hash inventory and produced a commission/trust-bound test receipt. No old
dossier, key, capability or runtime record was reused. The previous real mission was not resumed.

## Previous evidence and limitations

The old **2661 tests / 52390 assertions** remains historical local evidence. The old branch and
all its test source are intact. The method-by-method finalized disposition is in
`docs/protected-mission-authority-batch-5-audit.md`; the original full transcript/private handoff
was not located in the documented bounded search. No absent transcript or remote result is invented.

The executable protocol is protected by an installed separate Runtime account and OS ACLs; local
tests used the same user with disposable roots. No resistance to arbitrary Runtime-account code,
administrator control or compromised PHP is claimed. The trusted owner contains both issuer and
lifecycle implementation; protocol consumers receive only public verification material. This is
not separate issuer-versus-consumer account isolation. Real deployment access probes are still a
human gate. Legacy project-local records cannot enter this protected mission protocol; general
provider workflows were not migrated or activated by this campaign.

Supported inspection is local loose SHA-1 Git objects only, with no Git subprocess/config/hooks/
lazy fetch. Packs, worktree indirection, symlink blobs and network mounts are outside the supported
deployment. Deadlines depend on OS scheduling; hardware power-loss durability and exhaustive
instruction-level crash injection remain unproved. Journal capacity is bounded and no automatic
compaction is implemented. Raw receipt bytes are not admission to internal cognition.

Tested PowerShell-friendly ceremony commands and explicit unexecuted installation/account probes:
`docs/protected-mission-operator-runbook.md`. No real private key should be supplied to this agent.
Independent review of the exact candidate and genuine human deployment/Operator setup are the
next gates. No further mission execution is authorized here.

*Imperium Maximus.*
