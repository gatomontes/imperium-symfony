# Mission Amendment Authority and Evidence Binding — local audit

MISSION_AMENDMENT_CORRECTION_LOCAL_COMPLETE_PENDING_INDEPENDENT_REVIEW
DEPLOYMENT_ISOLATION_UNPROVED_OPERATOR_SETUP_REQUIRED

This is local implementation-agent self-audit, not independent acceptance, remote CI or deployed isolation.
No push, PR, merge into main, branch deletion, installer (including WhatIf), real trust/key/mission,
provider or external-system operation was performed.

## Exact bases and commits

Implementation: 7deb84b9f687ecb09c93f568464a606f0a602a89.
Documentation selection: 5b01f7d8a020daff2950ea408e5025438ef92196.
Documentation-only selection diff checked from 0099c5f7e4008fe06cafc9c5dfa3458dc68f4db9.
Integration: 3d5a40f3b1918675f92ecdc848aea287d31fc7ab, with implementation then selection as its two parents.
Only delegate-mission-flow.md overlapped; current amendment instructions precede intact prior audit history.

| Batch | Commit |
|---|---|
| 0 reproduction | ebb1897b9aa320eea1982634104844e842a81079 |
| 1 signed activation | f503ae70c2ec2ceb2309e1910f49b55e7f24d652 |
| 2 generation binding | df3ee368bd6bad0c971ea23cd15422d3fb11f77a |
| 3 process/PowerShell proof, before audit | 2a2b746feb9c66c961033ffafcf961f3bb49c4b5 |

Audited commit: 2a2b746feb9c66c961033ffafcf961f3bb49c4b5.
Audited tree: a7d81d3ea3ccc2091c68db2a8bad59b280c13245.
Branch: codex/mission-amendment-authority-evidence-correction.
The final evidence commit contains documentation only and is not separately tested. Its exact HEAD/tree
are returned with the final local handoff; this document deliberately does not invent a self-referential SHA.

Main remains b267e2c2b6a122694418ce59d2bf16319e602b07.
Previous candidate remains 7deb84b9f687ecb09c93f568464a606f0a602a89.
Earlier quarantine refs remain 8df34679beab0ba8699a68fdd458570bf658c4c8 and 3c4890ffd30f403f72a35b92f1e639d51c8c98f8.

## Test attribution

Focused command: php vendor/bin/phpunit --filter 'ProtectedMissionAuthority|MissionAmendment' tests.
Batch0 focused used --filter ProtectedMissionAuthority.
Full command: php vendor/bin/phpunit tests.
PowerShell: pwsh -NoProfile -File tests/Imperium/Runtime/Support/mission_amendment_powershell.ps1.

| Run | Result | Exact attribution |
|---|---|---|
| Batch0 reproduction and focused | AM01/AM02 measured; 16 / 221 | HEAD 3d5a40f, unchanged candidate production plus new reproduction script; script/evidence committed in ebb1897 |
| Batch1 focused | 18 / 240 | Pre-commit executable tree subsequently committed as f503ae7 |
| Batch2 focused | 20 / 305 | Pre-commit executable tree subsequently committed as df3ee36 |
| Batch2 full | 2669 / 52599 | Exact committed df3ee368bd6bad0c971ea23cd15422d3fb11f77a |
| Batch3 focused | 26 / 404 | Pre-commit executable tree subsequently committed as 2a2b746 |
| Audit focused | 26 / 404 | Exact committed 2a2b746feb9c66c961033ffafcf961f3bb49c4b5 |
| Audit PowerShell | MISSION_AMENDMENT_POWERSHELL_PASSED | Exact committed 2a2b746feb9c66c961033ffafcf961f3bb49c4b5 |
| Audit full | 2675 / 52696 | Exact committed 2a2b746feb9c66c961033ffafcf961f3bb49c4b5 |

All successful PHPUnit runs report zero skips. Pre-commit focused runs are not misrepresented as commands
run with the later commit already at HEAD. Full and terminal audit runs used committed code.
The prior 2665 tests plus ten added tests explains terminal 2675. Focused assertions increase by 183,
including one added assertion in the replaced old policy expectation. Full assertions increase by 181:
the unchanged InternalOperationalLeaseInterruptionEnforcementServiceTest::testClaimAndEnforcementRaceProducesExactlyOneWinnerAndNoPartialArtifacts
has two conditional claim-winner assertions (lines 123–126), making its count 5 or 7. Twelve normal
isolated reruns measured 7; a disposable audit-only copy forcing enforcement first measured 5 while
retaining every original assertion. This accounts for the observed two-assertion total difference;
the original full run did not retain per-case winner telemetry, so exact winner attribution is inferred.
The unchanged test is not edited or relabeled, and the measured full result remains 52696.
Probe source, outputs and adaptation are retained in var/mission-amendment-evidence/.
Initial ordering comparison failure, PHPUnit helper-name collision and PowerShell request-order refusal
are documented in batch notes and retained local evidence. Assertions were not relaxed to pass.
Full tests regenerate Symfony config/reference.php doc comments; that exact generated diff is retained
and restored, with no executable difference carried into the campaign.

## Findings and correction evidence

AM01 reproduction: an unsigned prepare made A inactive. AM02 reproduction: after A admit/inspect, signed
B with different paths completed using A findings at the same commit/tree. Both use the public protocol
and fresh disposable identities, without constructing either counterexample by journal editing.
Historical reproduction: docs/mission-amendment-reproduction.json and tools/reproduce-mission-amendment.php
at Batch0. Do not run the expected-vulnerable script as corrected acceptance.

AM01 correction: proposals append bounded non-authorizing records only. Exported signed bytes disclose
INITIAL_AUTHORIZATION with null predecessor or REPLACE_AUTHORIZATION with exact predecessor ID/digest.
Canonical authenticated review binds those bytes. Canonical derive compares predecessor, verifies
approval, retires A and initializes B under the common owner transaction. Pending proposals do not
overwrite a mission-wide challenge pointer. Failed requests publish no frame. Capacity refusal evicts
nothing; an existing approved challenge can still activate.

AM02 correction: exact authorization/dossier/generation, target, paths and budgets bind commission,
signed capability, initialized lifecycle, inspection and receipt. Each new authorization starts
AUTHORIZED with empty history and no receipt. Dossiers have distinct canonical IDs and their own
schema-local version; generation is a digest identity rather than an assumed mission-ID counter.
B-complete before B-admit/inspect refuses. Fresh B execution succeeds under independently varied
paths, budget and repository identity, even with identical commit/tree. Wrong-generation records
and old/mixed state schemas refuse. A history and receipt remain A's, never B progress.

Process proofs: controlled readiness/release barriers force inspect-before-activate and
activate-before-inspect, plus both revoke/activate orders. Two approved replacements released
concurrently have exactly one activation winner. Completion before a previously approved amendment
prevents reopening. Lost derive response is recovered by read-only challenge-status; replay refuses.
Existing actual-process consumed-nonce replay, torn-tail recovery and real-clock expiry remain.

The common lock covers currentness, signature verification, canonical derivation, consume and signed
control through complete journal-frame publication. Barriers are before public dispatch, not arbitrary
instruction-level hooks. This is finite cooperating-process evidence, not exhaustive interruption proof.

## Historical tests and evidence

All prior 16 protected test methods remain; none was removed.
Batch1, Batch2 and Batch5Audit classes are unchanged from the preserved candidate.
Batch3's testBadSignaturesAmendmentCancellationAndInvalidPlansLeaveNoAuthorityResidue:
SUPERSEDED_WITH_REPLACEMENT only for unsigned-prepare-supersedes-pending behavior. It now requires exact
previous payload equality and successful authenticated submission; forgery, cancellation, invalid-plan
and unchanged-journal assertions remain.
Batch4's testBudgetMissingObjectTreeMismatchAndCompletionWithoutEvidenceRefuse:
PORT_WITH_JUSTIFICATION for explicit corruption fixture indexing, now authorization ID with preserved
generation binding. Missing inspection must still refuse with no journal mutation or receipt.
ProtectedMissionFixture adds amendment.txt to its fresh loose-object target; old exact evidence.txt
byte/hash checks remain unchanged.
All other previous methods are RETAIN_UNCHANGED. FAILED is reserved but has no public producer; its
terminal guard is tested through explicitly labeled disposable corruption, not claimed real execution.

The four previous candidate transcripts still exist and match every SHA-256 in
docs/protected-mission-authority-evidence-ledger.json, including audit-full
7b973f48fa9442a7ae6b0369b24b9ca1bde44e9a4fe3098bd3c1568294cb1564.
The historical 2665 / 52515 at f12524f2b25942b8149e8c455a39da5d26217a9b remains historical, with its later
7deb84b documentation commit. No old keys, dossiers, capabilities or runtime records were imported.
Earlier 2661 / 52390 disposition and missing original transcript qualification remain unchanged in
docs/protected-mission-authority-batch-5-audit.md.

## Per-version examples and instructions

The committed sanitized PowerShell output is docs/mission-amendment-status-examples.json.
A: mission-authorization-d0d6e8b09280b2812949; lifecycle INSPECTING, inactive amended,
is_current false, receipt null.
B: mission-authorization-d4a9012f2a24c0105ae4; initially AUTHORIZED, empty history and null receipt;
then COMPLETED with only b.txt in its exact generation-bound snapshot and receipt.
Whole disposable Git target hashes are unchanged. These are test-only identities and outputs,
not real Operator evidence or reusable authority.

Executable PowerShell instructions and amendment semantics:
docs/protected-mission-operator-runbook.md (amendment supplement).
Reading provenance: docs/mission-amendment-reading-ledger.json.
Hashes, exact audit source blobs and local transcripts: docs/mission-amendment-evidence-ledger.json.
Local raw test outputs: var/mission-amendment-evidence/.

## Remaining limits

DEPLOYMENT_ISOLATION_UNPROVED_OPERATOR_SETUP_REQUIRED remains. All identities/processes here run as
the same user in disposable roots. Actual Runtime/caller ACL isolation and installed-account success
are unmeasured; trusted Runtime code/admin/PHP compromise is outside this boundary.
No installer, even dry-run, was executed during this correction.

Production startup is Windows-only. The inspector supports local loose SHA-1 Git objects, not packs,
worktree indirection, symlink blobs or network storage. Inspection launches no Git hooks/config/lazy
fetch. Deadlines depend on OS scheduling. Raw receipt content is not admission to internal cognition.

Schema v2 refuses old/mixed journals. Actual migration is not implemented or performed and needs future
owner review; old evidence must remain historical, never be silently promoted or a real journal reset.
Capacity is 64 retained proposals, 16 MB states and 64 MiB journal, with no automatic eviction/compaction.
Availability under resource exhaustion is bounded. Hardware power-loss durability and exhaustive crash
cuts are not proved. Inspection failures leave the prior nonterminal state; no public FAILED transition
is supplied. No progress-transfer or evidence-revalidation mechanism between generations exists.

Stop locally for independent review of this exact candidate. No real mission, real trust or key action,
deployment or publication is authorized by this completion.
