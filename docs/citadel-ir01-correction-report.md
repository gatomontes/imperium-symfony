# Citadel IR01 local correction and review handoff

Disposition: **IR01_CORRECTED_LOCAL_PENDING_INDEPENDENT_REVIEW_COMMISSIONING_BLOCKED**.
The independent review and its provenance-clearing addendum are preserved in the
packet's `review/` directory. This report records local correction and validation;
it does not claim independent acceptance, integration, or commissioning.

## Corrected boundary

`FormationCognition` now rejects interview authorization sources while an intake
retains understanding. This blocks fresh grants as well as existing calls. The
existing checks before reservation, durable transport start, and response admission
all consume that guard. On admitting UNDERSTOOD, the same journal transaction
stores completion markers on every existing interview session for that intake.
OPEN sessions become COMPLETED; refused/deferred statuses and all accounting are
retained. Controls cannot revive a marked session. A stale response can be sealed
and settled but cannot replace the first admitted understanding. Unknown outcomes
retain their unsettled maxima; no retry or refund is introduced.

Original-attempt recovery returns the original admitted record without reopening
authority. Drafting still requires its own request and exact signed approval.
The existing signed reply operation remains the explicit way to resume discussion:
a reply after understanding clears the intake's current understanding but preserves
completion of old grants, requiring fresh authority. This also fences older journals
that contain UNDERSTOOD without the new marker before the reply clears it. Replies
to an unfinished QUESTION can still continue under the existing valid session.
Changed intent retains the existing intent-version invalidation. No new automatic
reopening policy or production transport was introduced.

## Tests and retained regressions

`CitadelInterviewCompletionTest` adds seven command/DI cases covering:

- No extra call, reservation, or journal mutation after understanding; existing
  sibling and replacement grants, stale controls, original recovery, and separate drafting.
- Interleaved calls where the first admitted understanding wins over a stale return.
- A sealed unknown outcome that remains unadmitted and retains maximum exposure
  after another attempt completes.
- Signed replies with and without changed intent, in current and legacy journal
  projections; even a projected OPEN cannot revive a completed grant.

No existing assertion was removed. The CF01 resume test and its proof producer now
return QUESTION for the unfinished interview used to test deferral/resume/refusal.
The registry-reassessment test now supplies an explicit signed reply before fresh
understanding; it retains its assertion that unchanged approved mission terms need
no new mission approval. CF01/CF02 and the complete offline handoff, receiving
acceptance, and non-executing Step 1 remain covered and passed.

## Committed-code validation

Reviewed entry: `648dcd6eb3ef3bf876805389e1b631552c306e65`.
Tested commit: `41e7ef45c71b42a3d118cccfb435b240d7690466`.
Tested tree: `3e89cd506282486dca22fa3ff96539cd44846037`.
Local correction commits are `f711f2b9`, `8baea9ab`, and `41e7ef45`.

`python tools/verify-citadel-ir01.py` ran the following against committed executable
code with the existing locked dependencies. Exact argument arrays, UTC times,
durations, exit codes, and JUnit counts are in `proof/verification.json`.

| Command | Result |
| --- | --- |
| `php vendor/bin/phpunit tests/Imperium/Runtime/CitadelInterviewCompletionTest.php tests/Imperium/Runtime/CitadelFormationCorrectionTest.php tests/Imperium/Runtime/CitadelMissionFormationTest.php tests/Imperium/Runtime/CitadelReadinessPreparationTest.php --log-junit var/citadel-ir01-proof/focused.xml` | 57 tests / 399 assertions; no errors, failures, or skips; exit 0. |
| `php bin/console lint:container` | PASS, exit 0. |
| `php tools/prove-citadel-ir01.php` | COMPLETED; zero additional synthetic transport calls; CMF067; unchanged journal/exposure; idempotent original recovery. |
| `php tools/prove-citadel-correction.php` | CF01/CF02 CLOSED_LOCAL; retained receipt bytes unchanged; offline. |
| `php tools/prove-citadel-formation.php` and `php tools/prove-citadel-readiness.php` | PASS; complete synthetic handoff and non-executing Step 1; nine preparation steps; live_ready false. |
| `php vendor/bin/phpunit tests --display-warnings --log-junit var/citadel-ir01-proof/full-suite.xml` | 2,746 tests / 53,196 assertions; no errors, failures, or skips; four historical warnings; exit 0; 933.657 seconds wall time. |

The full run was 2026-09-08 00:03:21–00:18:54 UTC (September 7 locally).
The four warnings are the documented linked-worktree `.git/HEAD` reads in
DeploymentCustodyCrashDemonstration.php:290,
OperationalConstructionCrashDemonstration.php:399,
TerminalRetirementCrashDemonstration.php:183, and
UnknownProviderOutcomeCrashDemonstration.php:245. Their source is unchanged.
Full warning text is retained in `proof/full-suite.txt`.

A preliminary run was interrupted to add legacy-reply coverage; its partial logs
remain in `var/citadel-ir01-preliminary-proof`. A later focused run identified the
registry-reassessment test's implicit reopening; its 57-test, one-error logs remain
in `var/citadel-ir01-pre-reassessment-proof`. The corrected targeted test passed
(1 test / 5 assertions), followed by the complete passing run above. Neither earlier
run is presented as final validation.

Symfony regenerated reference annotations only. Their diff is retained as
`proof/generated-reference.diff`, and the file was restored to committed bytes.
Subsequent adopted changes are Markdown documentation only. The final commit/tree
and exact tested-to-final path list are in `REVIEW-IDENTITY.json` and the external
manifest; the bounded bundle contains both commit objects.

## Packet and remaining boundary

`python tools/package-citadel-ir01.py` exports allowlisted committed source, tests,
tools, public synthetic proofs, original reviews, and pre/post diagnostic results.
It includes `history.bundle`, internal SHA-256 hashes, source Git blob identities,
and an external ZIP SHA-256 manifest. It excludes dependencies, environment files,
private runtime evidence, and credential-adjacent working material. The source
packet is for review, not deployment. The bundle requires the accepted baseline
`7a7881b91f10f8c2382b28029ba2846d448370a9`.

Original diagnostic, original history bundle, and the historical readiness ZIP and
manifest have unchanged SHA-256 hashes in `proof/preservation-check.json`.
Historical packets and CF01/CF02 acceptance remain preserved.

Next: independent review of this corrected candidate. Real installation/custody,
institutional authority and appointments, formation trust/enrollment, supported
credentials/transport, the explicit B1 cost/timeout decision, and separately
authorized activation remain unresolved. Default live transport continues to
refuse. No push, merge, real keys, enrollment, installation changes, live provider
calls, activation, or mission execution were performed by this correction task.

Imperium via solitaria est.
