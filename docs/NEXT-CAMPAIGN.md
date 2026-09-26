# Next campaign — first bounded execution to result review

Prepared 26 September 2026.

## Start here

Repository: https://github.com/gatomontes/imperium-symfony

Read `AGENTS.md`, this document, `docs/IMPERIUM-STEPS.md`,
`docs/IMPERIUM-FLOW.md`, and `docs/SENESCHAL-CLI.md`. Inspect source and verify
current branch/PR state before editing.

## Integrated baseline

`main` contains the working mission path through explicit resource/effect
authorization. Authorization PR #4 was live-accepted by the operator and merged to
`main` at:

`91e8707be9d2794e5e5e37af80200d4ad27ae916`

The integrated path is:

**Interview → Understanding → Draft permission → Proposal → Proposal approval →
Resource/effect authorization**

Authorization is proposal-version-bound, explicit, persisted, deterministic, and
separate from execution.

## Current campaign

Branch: `codex/first-bounded-execution`  
PR: #5 — **Add first bounded local-file execution**

This campaign adds exactly one execution operation:

**Create one new local file under `var/execution/`.**

Code-enforced boundaries:

- the latest proposal must be approved;
- its authorization must be `authorized`;
- authorization resources must include filesystem write capability;
- authorization effects must explicitly permit one local file (including the
  bounded smoke-test wording “Create one local test file”);
- filenames are 1–120 safe characters and contain no directory separators;
- output is confined to `var/execution/`;
- existing targets are never overwritten;
- content is limited to 32 KiB;
- one authorization permits one execution attempt;
- an `ExecutionAttempt` is persisted before filesystem I/O;
- evidence stores relative target path, expected SHA-256, status, bytes written,
  failure code, and timestamps;
- no model call participates in execution.

Interruption behavior is deliberately conservative. A prepared attempt is never
automatically retried. On reopen, recovery may only inspect the expected target:
matching content/hash closes the attempt as succeeded; a mismatched existing file
fails closed; an absent file leaves the attempt prepared.

PostgreSQL CI passes migrations, schema validation,
**55 tests / 422 assertions**, and full migration rollback/reapply.

## Current source map

| File | Responsibility |
|---|---|
| `src/Entity/ExecutionAttempt.php` | One authorization-bound execution attempt and retained evidence. |
| `src/Atheneum/ExecutionRecords.php` | Deterministic execution evidence persistence. |
| `src/Curia/LocalFileExecutionService.php` | Mechanical authorization checks, bounded file effect, verification, recovery. |
| `src/Command/InterviewCommand.php` | Execution action and evidence display after authorization. |
| `tests/ExecutionTest.php` | Scope refusal, path/content limits, no-overwrite, success, recovery, and CLI behavior. |

## Live review for this campaign

Use a mission whose **approved proposal and authorization explicitly include**:

- resource/capability: local filesystem write access;
- effect: `Create one local test file` or `Create one local file`.

Then reopen the mission. The authorization displays first and the CLI offers:

`Create authorized local file`

Choose it and use a harmless unique filename such as
`imperium-live-test.txt` with simple test content.

Expected endpoint:

- `Execution attempt — succeeded`
- target under `var/execution/`
- SHA-256 displayed
- bytes written displayed
- success message that the authorized effect completed and evidence was recorded.

Reopen the mission afterward. The saved execution result must display directly and
must not repeat the effect or offer an automatic retry.

If the existing live authorization does not contain the supported filesystem
resource/effect, the execution service should refuse it. Do not weaken the check;
create/revise a mission with the required scope instead.

## Proposed next campaign after live acceptance

Add **result review and disposition** for this one execution type.

The smallest useful review should:

1. inspect the retained execution evidence;
2. compare the observed result with the approved proposal's acceptance criteria;
3. record a deterministic or operator-confirmed disposition such as
   `accepted`, `needs_correction`, or `failed`;
4. retain the final deliverable/evidence reference;
5. avoid repeating the external/local effect merely to review it.

Do not generalize execution until one complete mission has reached review and
delivery through the ordinary path.

*Ad Imperium.*
