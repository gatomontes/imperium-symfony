# Citadel session refusal and handoff recovery correction

Status: `LOCAL_CORRECTION_COMPLETE_PENDING_INDEPENDENT_REVIEW`.
CF01 and CF02 are closed locally with fresh evidence; independent review is pending.

The clean starting checkout was `codex/citadel-session-handoff-correction` at
instruction commit `b5ef401596209599c38439023c1f0615425e19c2`, directly above
reviewed candidate `e0386e75ce7619fbbeaac450af078d2d606df012`, tree
`54b32f189130d421ce7cd74a54b8e11e200ace60`. No applicable AGENTS.md was present.
Origin was verified as `https://github.com/gatomontes/imperium-symfony.git`.
The fetched instruction publication is `fa888b6764f4edf8c666087569bff7184f4b22d4`.
Its two instruction files match the local instruction commit exactly. Remote
steps/flow changes from `f49f38cf` were integrated without replacing the candidate
implementation guide; their pending-closure history remains, followed by this
run's actual status. No executable remote-main replacement or merge occurred.

## C0: source-traced reproductions

The original executable code was unchanged from e0386e75 when the two new
regressions ran. `proof/start-identity.json` and `proof/c0-attribution.json`
pin the commits, tree, executable comparison and test source identities.
Reproduction commit: `2251da37` (tests and reconciled remote steps/flow only).

Command: `php vendor/bin/phpunit tests/Imperium/Runtime/CitadelFormationCorrectionTest.php --log-junit var/citadel-correction-proof/c0-original.xml`.
The retained output reports 2 tests, 6 assertions, one failure and one error.
CF01 observed OPEN instead of REFUSED after authentic controls. CF02 executed
the real MasterMason/ImmutableRecordStore child write, interrupted immediately
after successful native rename, and then raised CMF094 on expiry recovery before
parent publication. The test-only filesystem wrapper never substitutes a receipt
or production service. C0 test/hook snapshots and original outputs are preserved.

## C1: terminal refusal

`FormationCognition` rejects every control after a recorded refusal. Invocation
and sealed-response admission also inspect retained control history, fencing old
indirectly reopened projections. No history or aggregate exposure is erased.
Legitimate never-refused defer/resume and separately authorized new sessions work.
Local C1 commit: `4dce054e`. Initial C1 proof: 3 tests, 22 assertions, passing.

## C2: completed-effect recognition

`ChildCuriaFormationService` serializes current authority and bounded local child
publication with the journal's revocation lock. The receipt retains exact frame,
reservation, prepared-content and native institutional witnesses. The read-only
recognizer verifies the retained chain, original signed approval and appointment
authority, institutional signatures, identities and exact prepared content.

`CuriaFormationService` recognizes a completed effect before deciding whether
current permission allows a new effect. Recovery records the original authority
boundary and present recognition time with no new authority. Concurrent recovery
publishes one parent transition. Missing or unverifiable evidence stays fenced;
an expired approval never goes through the new-effect producer. Older receipts
without publication provenance are not silently upgraded.

## C3: verification and packet

The exact committed-code results and dispositions follow. No executable or
contract changes follow the tested commit; the final status update is documentation only. The fresh allowlisted packet contains relevant
source/tests/contracts, required reading, changed-test map, both failing and passing
proof, public synthetic command/DI demonstrations, and SHA-256 manifests.
The full repository suite must be reproduced from the exact Git commit with its
locked dependencies; the ZIP does not include every historical documentary fixture.

## Preserved evidence and limitations

The original candidate branch remains at e0386e75. Its ZIP SHA-256 remains
`9f2f8196246edb9c11721ac7f6f890c7503ddd06ef695e14ccd4a9be372773da`.
The original 2,709-test/52,902-assertion result is historical, not correction proof.
Earlier archives, manifests and transcripts are checked byte-for-byte separately.
Worktrees, accepted policy and completed Delegate Steps 1–69 are preserved.

Proof uses new isolated roots, ephemeral synthetic signing keys, production
commands/consumers and fake or refusing transport. Only public evidence is exported.
No live calls, installed-state changes, real keys, owner ceremony, actual
commissioning, activation, deployment, execution, push or merge occurred.

Historical time and incumbency are trusted publisher observations in local
custody, not an independent timestamp or protection against an administrator
replacing all stores. Hashes are not signatures. Original authority and
institutional evidence are independently signed. Process interruption around
rename is covered; power-loss durability and live operational readiness are not.
Recovery cannot manufacture Seneschal acceptance or revive any expired authority.

## Final tested identities and results

Tested executable commit: `1a978ae42fbeaab55437768b88818fb40ba88676`.
Tested tree: `7ef164554789ca46c51dd00f0d846d2b8b82c967`. The full suite started at
2026-09-07T20:58:23.2548417Z and finished at 2026-09-07T21:13:31.9379815Z;
shell elapsed time was 908.498 seconds (PHPUnit reports 15:07.488).
The separate final documentation commit/tree is recorded in the packet's
`REVIEW-IDENTITY.json` and outer SHA-256 manifest.

| Check | Result | Exact attribution |
| --- | --- | --- |
| C0 on original executable source | 2 tests / 6 assertions; 1 failure, 1 error | e0386e75 source, instruction b5ef4015, regression commit 2251da37 |
| C1 terminal refusal | 3 tests / 22 assertions, passed | C1 development proof retained |
| Formation and correction focused suite | 39 tests / 274 assertions, passed | ff650b88d84db3ae094d1dd6326e49dd57ad5816 |
| Final affected-path recheck, warnings fatal | 6 tests / 55 assertions, passed | 1a978ae42fbeaab55437768b88818fb40ba88676 |
| Full repository suite | 2,729 tests / 53,069 assertions; exit 0; no failures/errors/skips | 1a978ae42fbeaab55437768b88818fb40ba88676 |
| Ordinary command/DI demonstration | Receiving ACCEPTED and non-executing Step 1 validation | 1a978ae42fbeaab55437768b88818fb40ba88676; 3 fake calls |
| Correction command/DI demonstration | Terminal refusal, real child interruption/expiry/recovery, expired approval creates no child | 1a978ae42fbeaab55437768b88818fb40ba88676; recovery repeats no cognition and invents no acceptance |
| Public proof cross-check | 4 CF01 owner signatures, 73 unique CF02 signatures, 9 native witnesses, exact receipt bytes, one parent transition | Python cryptography, independent of PHP admission implementation; synthetic evidence only |
| Container validation | Passed | `php bin/console lint:container --env=test` on tested source |
| Preservation | 27 checks passed | Original packet, transcripts, frozen decisions, candidate branch and other worktree identities |

Exact test commands and commit/tree for each completed run are retained in
`proof/verification.json`, `focused-run.json`, `final-focused-run.json` and
`full-run.json`. Final full-suite command:

```powershell
php vendor/bin/phpunit tests --log-junit var/citadel-correction-proof/full-suite.xml --display-warnings --log-events-text var/citadel-correction-proof/full-events.txt
```

Environment: PHP 8.4.14 ZTS, Visual C++ 2022 x64; PHPUnit 13.3.0; Windows
PowerShell; Python 3.12.10 with cryptography 46.0.7 for public proof checking.
Locked `composer.lock` SHA-256:
`4ab52c72c931bcc945385cdbae48dd9ae3f192e4c207c59a7783e4cf38b8e6e9`. Dependencies were not updated.

The four PHP warnings are the same historical linked-worktree `.git/HEAD` reads:
DeploymentCustodyCrashDemonstration.php:290, OperationalConstructionCrashDemonstration.php:399,
TerminalRetirementCrashDemonstration.php:183 and UnknownProviderOutcomeCrashDemonstration.php:245.
Their source blobs are unchanged from e0386e75 and their tests passed. No warning
was hidden or used to excuse a correction failure. Container/full-suite generation
of `config/reference.php` was inspected, recorded and restored to the tested bytes;
no generated configuration change is part of this correction.

C0's initial PowerShell wrapper printed the result after PHPUnit and did not
separately capture PHP's exit code. That wrapper's 0 is not test success. The
retained JUnit and transcript directly establish the two expected failing results.
Later runs capture the actual PHP exit code explicitly.

## Finding dispositions and review boundary

| Finding | Disposition | Evidence and remaining boundary |
| --- | --- | --- |
| CF01 | CLOSED_LOCAL | Direct/indirect reopening and stale controls fail; old reopened projections and sealed admission remain fenced; legitimate resume, remaining budget, unknown exposure and a new authentic session are covered. |
| CF02 | CLOSED_LOCAL | Native child publication followed by interruption and approval expiry reconciles the same bytes and identity. Before-effect revocation/expiry cannot create a child. Later revocation/succession/expiry preserves the historical fact without authority. Missing/mismatched/corrupt/unverifiable evidence is fenced. Repeated, three-process and stale-snapshot interleavings preserve one effect and parent publication. |

All 40 formation/correction cases are included in the full committed-code suite.
The ordinary demonstration preserves the receiving-acceptance/Step 1 boundary.
The expired-effect demonstration stops at factual recognition; it does not renew
receiving authority. The original candidate and all prior results remain historical.
Independent review must assess the retained-custody assumptions and code before
any later integration. No live readiness or integration authority is asserted.
