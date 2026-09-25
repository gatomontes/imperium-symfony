# Next campaign — from understanding to a saved proposal

Prepared 25 September 2026 (America/La_Paz). This is a handoff for a new chat.

## Start here

Repository: https://github.com/gatomontes/imperium-symfony

Read `AGENTS.md`, this document, `docs/IMPERIUM-STEPS.md`,
`docs/IMPERIUM-FLOW.md`, and `docs/SENESCHAL-CLI.md`. Inspect the current source
before editing. This handoff records a checkpoint; verify branch and PR state again.

Imperium was deliberately restarted as a small Symfony application. The cognitive
map and institutional doctrine survive; implementation grows through useful,
working capabilities. Do not reconstruct the previous campaign's machinery.
Symfony supplies the mechanics. Imperium supplies meaning and enforced authority.

## Campaign just completed

The local CLI Seneschal interview now reaches explicit permission to draft.
The operator reported success and supplied a terminal screenshot reading:

> Permission to draft recorded. Proposal generation is the next milestone; no proposal was generated or execution authorized.

This is operator-reported live smoke evidence. The implementation agent did not
make a live provider call or inspect the private transcript/database. It establishes
that the operator reached the intended terminal state, not general model quality.
Earlier provider/format failures remain historical incidents; their raw payloads
were not inspected and their precise causes were not established.

Verified repository checkpoint before this documentation update:

- Branch: `codex/seneschal-cli-interview`.
- PR [#1](https://github.com/gatomontes/imperium-symfony/pull/1): open, ready for review, unmerged.
- Implementation commit: `ecbff6a2d9ec62ba6d27e4e5bcfe76438a6c852b`.
- Implementation tree: `67012ae489599c3a6c9670cdbbf165a860098d44`.
- Local validation: 28 tests / 283 assertions using isolated SQLite.
- [PostgreSQL CI](https://github.com/gatomontes/imperium-symfony/actions/runs/36158926205): passed for that commit, including migrations, schema consistency, and rollback/reapply.

The current task prepares the handoff only. It does not merge PR #1 or implement
proposal generation. Check the PR before choosing the next branch base; `main`
did not yet contain the interview at this checkpoint.

## Working product and settled choices

| Concern | Current decision or behavior |
|---|---|
| Interface | Local CLI, access through the operating-system account. |
| Persistence | PostgreSQL through Doctrine ORM and migrations. |
| Provider | DeepSeek; OpenAI is installed but inactive for Seneschal. |
| Configuration | `DEEPSEEK_API_KEY` in local secrets; `IMPERIUM_DEEPSEEK_MODEL` defaults to `deepseek-flash`. |
| Entrance | `php bin/console imperium:interview` opens the saved-interview list. |
| New interview | Type `new` in the list, or pass `--new`. |
| Selection | Numbered rows; `1` Continue, `2` Delete permanently, `0` Back. |
| Identity | Six-character display suffix plus a saved mission alias; full UUID remains the storage/resume identity. |
| Conversation | One focused question per turn; understanding summary before the application asks permission. |
| Decision | `/approve` records drafting permission only when a current request is pending; `/decline` or corrections continue clarification. |
| Recovery | Messages survive failed replies; `/retry` is explicit and may incur provider charges. |
| Current endpoint | `draft_authorized`; no proposal exists yet. |

Atheneum is inside Citadel with no direct connections on the revised cognitive
map. It currently supplies deterministic record operations, not another model or
an institutional routing stage.

## Source map

| File | Responsibility |
|---|---|
| `src/Command/InterviewCommand.php` | CLI entrance, numbered management, conversation and decisions. |
| `src/Entity/Interview.php` | Transcript, alias, limits, version and state transitions. |
| `src/Atheneum/InterviewRecords.php` | Doctrine record operations. |
| `src/Curia/InterviewService.php` | Orchestration, persistence ordering, locking and safe failures. |
| `src/Curia/Seneschal.php` | Named Symfony AI agent, provider history and typed reply validation. |
| `src/Curia/SeneschalReply.php` | Reply DTO: message, readiness and optional alias. |
| `src/Curia/DeepSeekResponseListener.php` | Preserve HTTP failures and diagnose invalid/truncated JSON responses. |
| `config/prompts/seneschal.txt` | Interview instructions. |
| `config/packages/ai.yaml` | Named agent and DeepSeek configuration. |
| `tests/InterviewTest.php` | Caller-level tests through the real Symfony AI stack with mock HTTP. |

Installed baseline: Symfony 8.1, PHP 8.4 (use at least 8.4.1), Symfony AI 0.13,
Doctrine ORM, Serializer, Validator, and Lock. Verify exact versions in Composer
before depending on an API. Provider requests use JSON mode, 2,048 output tokens,
and thinking disabled. Assistant history is replayed in the expected JSON shape;
the application-added permission question is excluded from provider history.

Keep the current guarantees: save input before inference; reserve attempts before
calling; no automatic retries; validate output before saving a reply; prevent
overlapping operations with Symfony Lock; reject stale permission decisions.
The default lock is single-host `flock`. There is no exactly-once inference claim.
Current limits are 20 model attempts, 6,000 characters per input, and 80,000
characters of retained context before a request. Do not silently truncate intent.

## Proposed next campaign

Deliver the smallest usable proposal stage: an interview with recorded drafting
permission can produce a proposal that is saved and readable after restarting.
This scope is proposed for the next chat, not already implemented or approved as
a detailed design. Start by showing the small implementation plan.

1. Inspect the existing authorized-interview state and choose a minimal explicit
   CLI action to request its proposal. Reuse recorded drafting permission; do not
   ask for the same permission again merely because the process restarted.
2. Generate from the saved understanding and material constraints. A proposal
   should identify objective, deliverable, referenceable steps, acceptance criteria,
   resource requirements, limits, and any unresolved assumptions.
3. Validate and persist the proposal through Doctrine, linked to its interview
   with an identifiable version. Use the simplest schema that supports this
   behavior, generated migrations, and ordinary application services.
4. Display the saved proposal and allow it to be reopened without another model
   call. Decide the smallest revision interaction from the existing CLI; keep
   proposal approval distinct from drafting permission.
5. Preserve explicit retry and failure behavior. A failed generation must not
   erase the interview or claim that a proposal was saved. Reopening a completed
   draft must not silently regenerate it or charge for another call.

Acceptance evidence should cover refusal without drafting permission, successful
generation and persistence, restart/review without inference, invalid/provider
failure recovery, and preservation of the authority boundary. Use caller-level
tests, run PostgreSQL migration/schema checks if the schema changes, and let the
operator smoke-test a real proposal in their configured environment.

The useful endpoint is a reviewable saved proposal. Proposal approval, authority
to use resources, execution, and delivery remain separate facts. Do not infer
execution authority from drafting permission or from model text. Broader officer
lifecycles, tool execution, web interfaces, workers, and formal proceedings should
wait for a concrete need. The larger product milestone remains one useful mission
carried through understanding, proposal, explicit authority, execution and review.

## Practical continuation notes

- Operator checkout: `E:\htdocs\imperium` in PowerShell. Preserve local configuration
  and unrelated changes. Never print or commit API keys or private mission records.
- After pulling the interview feature, apply migrations and clear the cache as
  needed. An earlier `OPENAI_API_KEY` warning came from stale code; current
  Seneschal uses DeepSeek.
- Two migrations exist: initial interview/Messenger schema and nullable alias.
  Follow the runbook; do not run production schema-update shortcuts.
- The implementation workspace used a temporary PHP 8.4.22 runtime and an isolated
  SQLite test database. Those paths are disposable; rediscover the environment in
  a new session. Never run tests against the operator's mission database.
- Remote publication used the GitHub connector because shell push lacked
  credentials. Local commit metadata differs from GitHub; compare trees before
  assuming divergence means changed code. The implementation local commit was
  `9b033e1a6ff1716088587006d9f51d411f7cb79f`, with the same tree recorded above.

## New-chat opening prompt

Continue Imperium in `gatomontes/imperium-symfony`. Read `AGENTS.md` and
`docs/NEXT-CAMPAIGN.md` from `codex/seneschal-cli-interview` (or its merged
successor), then the linked doctrine and runbook. Verify current PR/branch state.
The live Seneschal interview has reached recorded permission to draft. Prepare
the smallest Symfony-native proposal-generation increment: persist a reviewable
proposal from an authorized interview, preserve restart/retry behavior, and keep
drafting permission separate from proposal approval and execution authority.
Present the implementation plan first. Retain CLI, PostgreSQL/Doctrine ORM and
DeepSeek. Build on the working application and introduce doctrine gradually.

*Nullum tempus quiescendi. Ad Imperium.*
