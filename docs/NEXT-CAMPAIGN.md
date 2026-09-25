# Next campaign — proposal review to execution authority

Prepared 25 September 2026.

## Start here

Repository: https://github.com/gatomontes/imperium-symfony

Read `AGENTS.md`, this document, `docs/IMPERIUM-STEPS.md`,
`docs/IMPERIUM-FLOW.md`, and `docs/SENESCHAL-CLI.md`. Inspect source and verify
current branch/PR state before editing.

Imperium is intentionally growing as one useful Symfony mission path. Preserve the
doctrine distinctions; do not reconstruct the previous implementation by default.

## Integrated baseline

`main` contains the first working mission path through saved proposal review:

**Mission → Interview → Understanding → Draft permission → Proposal generation →
PostgreSQL persistence → Proposal review**

The operator completed the live proposal review. PR #2 was merged into the interview
branch, then PR #1 was merged into `main` at
`c2faceafc18b37554ae47816d1ca16f43aa41ed2`.

That integrated baseline passed PostgreSQL CI with 33 tests / 311 assertions,
schema validation, and full migration rollback/reapply. Proposal review reopens the
saved version without another model call. Drafting permission remains distinct from
proposal approval, resource authority, and execution authority.

## Current campaign

Branch: `codex/proposal-review-approval`  
PR: #3 — **Add proposal revision and explicit approval**

This campaign adds the smallest proposal decision stage:

- a proposal remains an identifiable immutable version;
- a revision request produces `vN+1` and preserves previous versions;
- revision input is bounded operator guidance supplied to the existing DeepSeek
  proposal drafter with the authorized interview and current proposal;
- a failed revision does not replace or corrupt the current proposal;
- approval is an explicit application action against the observed latest version;
- stale-version approval is refused;
- approval persists with an approval timestamp and requires no model call;
- an approved proposal reopens read-only;
- proposal approval grants no resource authority, external-effect authority, or
  execution authority.

Implementation CI on the campaign branch passed PostgreSQL migrations, schema
validation, **38 tests / 334 assertions**, and migration rollback/reapply. A live
operator review is still required before merging this campaign.

## Current source map

| File | Responsibility |
|---|---|
| `src/Entity/Interview.php` | Interview transcript and drafting-permission state. |
| `src/Entity/Proposal.php` | Versioned proposal content and explicit approval state. |
| `src/Atheneum/InterviewRecords.php` | Deterministic interview persistence. |
| `src/Atheneum/ProposalRecords.php` | Latest proposal, proposal history, and persistence. |
| `src/Curia/InterviewService.php` | Interview orchestration and safe provider failure behavior. |
| `src/Curia/ProposalService.php` | Generate, revise, and approve proposals under the interview lock. |
| `src/Curia/ProposalDrafter.php` | DeepSeek structured proposal generation/revision. |
| `src/Command/InterviewCommand.php` | CLI interview and proposal review loop. |
| `tests/InterviewTest.php` | Interview caller-path behavior. |
| `tests/ProposalTest.php` | Proposal generation, revision, persistence, and approval boundaries. |

## Live review for this campaign

From the operator checkout, switch to `codex/proposal-review-approval`, apply
migrations, then reopen an interview with a saved draft proposal.

The expected review loop is:

1. Display the latest proposal version and status.
2. Choose **Request revision** and supply a small material change.
3. Confirm the revised proposal appears as the next version.
4. Reopen the interview and confirm the latest version persists without another
   model call merely to review it.
5. Choose **Approve proposal**.
6. Reopen again and confirm the approved version is read-only and visibly records
   that resource and execution authority remain ungranted.

Do not use a mission with real external effects for this review. This campaign
does not authorize or execute anything.

## Proposed next campaign after live acceptance

Implement the smallest **resource/effect authorization** record for one approved
proposal. Do not execute yet unless the authorization design and first bounded
operation are both explicit.

The next increment should answer only:

1. Which approved proposal version is the authority request based on?
2. Which resources or capabilities are requested?
3. Which external effects, if any, are requested?
4. What limits apply?
5. What did the operator explicitly authorize or refuse?

Authorization must be persisted separately from proposal approval. A proposal that
lists a resource requirement does not authorize that resource. Model text cannot
grant authority. Scope changes require reconsideration of the affected authorization.

Only after that fact exists should Imperium introduce its first narrowly bounded
execution operation.

## Practical notes

- Interface: local CLI.
- Persistence: PostgreSQL through Doctrine ORM/migrations.
- Provider: DeepSeek, default `deepseek-flash`.
- Secrets remain in local configuration; never commit API keys or private mission
  evidence.
- Per-interview Symfony Lock remains single-host `flock`; no distributed or
  exactly-once inference claim exists.
- Do not use `doctrine:schema:update` for deployment; use migrations.
- Tests must use a disposable test database, never the operator mission database.

*Nullum tempus quiescendi. Ad Imperium.*
