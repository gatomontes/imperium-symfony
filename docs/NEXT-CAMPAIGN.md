# Next campaign — authorization to first bounded execution

Prepared 25 September 2026.

## Start here

Repository: https://github.com/gatomontes/imperium-symfony

Read `AGENTS.md`, this document, `docs/IMPERIUM-STEPS.md`,
`docs/IMPERIUM-FLOW.md`, and `docs/SENESCHAL-CLI.md`. Inspect source and verify
current branch/PR state before editing.

Imperium is intentionally growing as one useful Symfony mission path. Preserve the
distinctions between understanding, proposal, approval, authorization, execution,
review, and delivery.

## Integrated baseline

`main` now contains the working mission path through **explicit proposal approval**.

Proposal revision/approval PR #3 was live-smoke accepted by the operator and merged
to `main` at:

`3c0a1f831e187cc18f104efba2a6810bf75fdcc3`

The integrated path is:

**Mission → Interview → Understanding → Draft permission → Proposal generation →
Proposal revision/history → Explicit proposal approval**

Proposal approval is attached to one proposal version and does not grant resource
authority, external-effect authority, or execution authority.

## Current campaign

Branch: `codex/proposal-authorization`  
PR: #4 — **Add explicit resource and effect authorization**

This campaign implements the next deterministic authority gate:

- one `Authorization` record is linked to one approved proposal version;
- an authorization request cannot exist for an unapproved proposal;
- requested resources/capabilities are snapshotted from that approved proposal's
  `resourceRequirements`;
- limits are snapshotted from the approved proposal;
- intended external effects are declared explicitly by the operator and stored;
- merely reopening an approved proposal creates no authorization request;
- the operator explicitly chooses **Authorize requested scope**, **Refuse requested
  scope**, or **Back**;
- a decided authorization record is immutable/read-only;
- the decision requires no model call;
- no execution action exists in this campaign.

External effects declared at the authorization gate do not amend the approved
proposal. They must remain consistent with that proposal and its recorded limits;
a material plan/scope change belongs in a revised proposal rather than silently
widening authority.

Implementation CI passes PostgreSQL migrations, schema validation,
**47 tests / 383 assertions**, and full migration rollback/reapply.

## Source map added by this campaign

| File | Responsibility |
|---|---|
| `src/Entity/Authorization.php` | Proposal-bound resource/effect scope and explicit decision. |
| `src/Atheneum/AuthorizationRecords.php` | Deterministic authorization persistence/retrieval. |
| `src/Curia/AuthorizationService.php` | Request/decision orchestration under the interview lock. |
| `src/Command/InterviewCommand.php` | Explicit authorization preparation and decision UI. |
| `tests/AuthorizationTest.php` | Authority-boundary caller tests with no execution. |

The database table is `mission_authorization`; `authorization` is a PostgreSQL
keyword and is deliberately not used as the table name.

## Live review for this campaign

From the operator checkout, switch to `codex/proposal-authorization`, apply
migrations, and reopen the mission whose proposal is already approved.

Expected sequence:

1. Approved proposal displays normally.
2. Choose **Prepare authorization request**.
3. Confirm resources/capabilities are copied from the approved proposal.
4. Declare any intended external effects explicitly, or leave blank for none.
5. Confirm the displayed limits match the approved proposal.
6. Choose **Authorize requested scope** or **Refuse requested scope**.
7. Reopen the mission.
8. Confirm the decision persists and is read-only.
9. Confirm the CLI explicitly states that execution remains unavailable and that
   no operation was performed.

The live review should use a harmless/no-effect scope. This campaign creates
authority facts only; it has no executor.

## Proposed next campaign after live acceptance

Introduce the **first bounded execution operation**, but only for a deliberately
small effect whose preconditions can be enforced mechanically.

Before execution is implemented, choose one concrete operation and define:

1. the exact authorization fields it requires;
2. which authorized resource/capability it consumes or uses;
3. the allowed effect;
4. applicable limits;
5. the result/evidence to retain;
6. retry/interruption behavior appropriate to that effect.

The execution service must refuse when authorization is missing, refused, for a
different proposal, or outside scope. Model text cannot grant or expand authority.

Do not generalize into a universal execution framework before one real operation
needs it.

## Practical notes

- Interface: local CLI.
- Persistence: PostgreSQL through Doctrine ORM/migrations.
- Provider: DeepSeek for interview/proposal language work; authorization itself is
  deterministic and uses no model.
- Secrets remain local; never commit API keys or private mission evidence.
- Per-interview Symfony Lock remains single-host `flock`.
- No distributed or exactly-once execution claim exists.
- Deploy schema changes through migrations, not `doctrine:schema:update`.
- Tests use a disposable test database, never the operator mission database.

*Nullum tempus quiescendi. Ad Imperium.*
