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

**Create one new local file under `public/output/`.**

Code-enforced boundaries:

- the latest proposal must be approved;
- its authorization must be `authorized`;
- authorization resources must include the exact canonical capability `Local filesystem write access`;
- authorization effects must exactly permit `Create one local file` or `Create one local test file`;
- filenames are 1–120 safe characters and contain no directory separators;
- output is confined to `public/output/`;
- existing targets are never overwritten;
- authorization limits must include `One new local file only` and `No overwrite`; unknown limits fail closed because this executor cannot claim to enforce them;
- `No external publication` conflicts with `public/output` and therefore causes execution refusal, because files in Symfony's public document root may be web-reachable;
- content is limited to 32 KiB;
- one authorization permits one execution attempt;
- an `ExecutionAttempt` is persisted before filesystem I/O;
- evidence stores relative target path, expected SHA-256, status, bytes written,
  failure code, and timestamps;
- no model call participates in execution.

Interruption behavior is deliberately conservative. A prepared attempt is never
automatically retried. On reopen, recovery may only inspect the expected target:
a merely PREPARED attempt never claims success from an existing file; if a target exists before persisted effect-start evidence, recovery fails closed. Only an EFFECT_STARTED attempt may be reconciled by comparing the target hash. A missing PREPARED target remains prepared and is never retried automatically.

PostgreSQL CI passes migrations, schema validation,
**63 tests / 469 assertions**, and full migration rollback/reapply.

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
- effect: `Create one local test file` or `Create one local file`;
- limits: `One new local file only.` and `No overwrite.`;
- do **not** include `No external publication.` for this executor, because `public/output` is intentionally inside the web document root.

Then reopen the mission. The authorization displays first and the CLI offers:

`Create authorized local file`

Choose it and use a harmless unique filename such as
`imperium-live-test.txt` with simple test content.

Expected endpoint:

- `Execution attempt — succeeded`
- target under `public/output/`
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


## Structured executable authority — 26 September 2026

Execution permission no longer depends on proposal/model wording. New authorization
requests that ask for the supported local-file effect persist this canonical scope:

```text
capability = filesystem.write.public_output
effect = file.create.public
root = public/output
visibility = public
allowed_extensions = [txt]
max_files = 1
overwrite = false
max_bytes = 32768
```

The operator sees this scope before choosing Authorize/Refuse. The executor checks
only these structured fields. Human-readable proposal resources/effects/limits remain
context and provenance, not machine permission.

Existing authorization records from before this change retain a null execution scope
and are intentionally non-executable. They are not silently upgraded after consent.

Because the target is public, this first executor accepts only `.txt` files.


## Authorization replacement / re-consent — 26 September 2026

Authorizations are now versioned per approved proposal. A decided authorization may
be followed by a new authorization version; the prior decision is preserved unchanged.

This specifically repairs the live migration path from pre-structured authority:

```text
Authorization v1 — authorized
Executable scope: none (historical)
  → Prepare replacement authorization
Authorization v2 — pending
Executable scope: canonical structured grant
  → operator Authorize / Refuse
```

Only the latest authorization version is eligible for execution. A pending latest
authorization blocks another replacement. Existing v1 rows migrate as version 1;
the migration does not populate execution_scope or alter their prior decision.


## Public-output publication integrity — 26 September 2026

Public output is no longer written in place. Imperium writes and hashes the complete
content under `var/execution-staging/`, persists effect-start immediately before
publication, and publishes the verified inode into `public/output/` with a hard link.
The publish operation cannot overwrite an existing target. This prevents an empty or
partially written public file from becoming visible while content is still being
generated.

Mission deletion is also blocked once any execution attempt exists. The public output
and its authorization/execution evidence therefore remain in custody together rather
than leaving an orphaned public file after cascade deletion.
