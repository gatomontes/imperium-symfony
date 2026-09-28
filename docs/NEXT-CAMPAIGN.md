# Next campaign — close first bounded execution, then review result

Prepared 26 September 2026.

## Start here

Repository: https://github.com/gatomontes/imperium-symfony

Read `AGENTS.md`, this document, `docs/IMPERIUM-STEPS.md`,
`docs/IMPERIUM-FLOW.md`, and `docs/SENESCHAL-CLI.md`. Inspect PR #5 and the
current branch head before editing.

Imperium is intentionally growing as one useful Symfony mission path. Preserve the
authority distinctions and the evidence chain. Do not generalize execution before
this first operation is fully closed and reviewed.

## Integrated baseline

`main` is integrated through explicit resource/effect authorization at:

`91e8707be9d2794e5e5e37af80200d4ad27ae916`

Integrated path:

**Interview → Understanding → Draft permission → Proposal → Proposal approval →
Resource/effect authorization**

## Current campaign

Branch: `codex/first-bounded-execution`  
PR: #5 — **Add first bounded local-file execution**

The live operator smoke test **succeeded**. Imperium created:

`public/output/imperium-test.txt`

and displayed retained execution evidence including:

- operation: `local_file_create`
- status: `succeeded`
- target: `public/output/imperium-test.txt`
- SHA-256 recorded
- bytes written: 32

This confirms the ordinary path can now reach a real effect:

**Understand → Propose → Approve → Authorize → Execute → Evidence**

Do not merge PR #5 yet. The latest automated review on current head
`4a1531a1b0b0ad71d843cefde0a0ef8f9fb2c644` found one remaining blocker:

> `public/output` may already be a symlink. The executor currently accepts it via
> `is_dir()`, so publication could land outside the authorized project root while
> evidence still records `public/output/...`.

### First task in the new chat

Fix output-root containment before merge.

The executor must refuse execution when `public/output` (or any relevant path
component used for the authorized root) resolves through a symlink or otherwise
resolves outside the intended project-root path.

The fix should remain narrow:

1. preserve the canonical authorization root as `public/output`;
2. verify the actual directory used for publication is the intended non-symlinked
   directory beneath `kernel.project_dir`;
3. refuse before effect-start/publication if containment cannot be proven;
4. add caller-level tests for a symlinked `public/output` and a normal directory;
5. rerun PostgreSQL migrations, schema validation, PHPUnit, and rollback/reapply;
6. request fresh automated review on the exact corrected head;
7. only then merge PR #5 to `main`.

## Current execution design

The first executor supports exactly one operation: publish one new public text file.

Canonical executable authorization:

```text
capability: filesystem.write.public_output
effect: file.create.public
root: public/output
visibility: public
allowedExtensions: [txt]
maxFiles: 1
overwrite: false
maxBytes: 32768
```

Human-readable proposal resources/effects/limits are provenance/context only.
Execution permission comes from the structured authorization scope.

Authorizations are versioned. Historical free-form authorization rows remain
unchanged and non-executable. The operator can explicitly create a replacement
authorization version and authorize it separately.

Current publication behavior:

1. create and persist an `ExecutionAttempt`;
2. write complete content under `var/execution-staging/`;
3. verify SHA-256 before publication;
4. persist effect-start;
5. atomically publish by hard-link into `public/output/` without overwrite;
6. retain the staging inode until success is durably recorded;
7. verify target ownership/inode + SHA-256 during recovery;
8. remove staging witness after durable success.

A mission with execution evidence cannot be permanently deleted, preserving custody
between the public file and its authorization/execution record.

Latest fully green validation before the final symlink review finding:

**64 tests / 471 assertions**, PostgreSQL schema synchronized, full migration
rollback/reapply passed.

## After PR #5 merges

Start the smallest **result review / disposition** campaign.

Do not execute again merely to review.

The review stage should:

1. load the succeeded `ExecutionAttempt`;
2. inspect retained evidence and the current public target;
3. compare observed evidence with the approved proposal acceptance criteria;
4. record a persisted disposition such as:
   - `accepted`
   - `needs_correction`
   - `failed`
5. record operator review time and any short review note;
6. retain the deliverable/evidence reference;
7. reopen read-only after disposition;
8. never silently repeat the effect.

Keep the first review deterministic/operator-confirmed. Do not add another model
unless a concrete need appears.

## Practical notes

- Interface: local CLI.
- Persistence: PostgreSQL through Doctrine ORM/migrations.
- Provider: DeepSeek only for interview/proposal language work.
- Authorization and execution are deterministic application logic.
- Public output root: `./public/output`.
- Non-public staging root: `./var/execution-staging`.
- Only `.txt` is allowed by this first public executor.
- No overwrite.
- One execution attempt per authorization version.
- Per-interview Symfony Lock remains single-host `flock`; target ownership is
  additionally proven with the retained staging inode during recovery.
- Never commit private mission evidence or secrets.

*Fortuna Eruditis Favet. Ad Imperium.*
