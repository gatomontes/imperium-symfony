# Seneschal — interview and proposal CLI

Updated 25 September 2026. Implements the local interview, persisted proposals, revision history, and explicit proposal approval described in [IMPERIUM-STEPS.md](IMPERIUM-STEPS.md) and [IMPERIUM-FLOW.md](IMPERIUM-FLOW.md).

## What this version does

- Starts or resumes an interview from a UUID.
- Uses the configured Symfony AI agent with DeepSeek. Default model: `deepseek-flash`.
- Saves exchanges and current state in PostgreSQL through an Atheneum application service and Doctrine ORM.
- Prompts Seneschal to ask one focused question per turn, adapt to free-form answers, and summarize the agreed understanding before signaling readiness.
- Lets Seneschal clarify intent and signal readiness. The application then asks: “I am ready to draft a proposal. Do you approve?”
- Records drafting permission only through `/approve`, while a current readiness request is pending. `/decline` or a typed correction continues the interview.
- After drafting permission, generates and persists a structured proposal with objective, deliverable, referenceable steps, acceptance criteria, resource requirements, limits, and unresolved assumptions.
- Reopens the saved proposal without another model call merely to review it.
- Lets the operator request a revision; each accepted revision is saved as a new proposal version and previous versions remain retained.
- Records explicit approval of the observed latest proposal version. Approval itself makes no model call.
- Stops at proposal approval. Resource/effect authority, tools, and execution remain later work.

This is a local operator-only CLI. Access relies on the local operating-system account and database credentials; there is no web login or independent officer authentication. The same local operator can resume all interviews in this database. Do not expose this command through an unauthenticated web or remote execution endpoint.

## Setup in PowerShell

Use PHP 8.4.1 or newer with the extensions required by Composer, including `pdo_pgsql`, `mbstring`, XML/DOM, and `fileinfo`.

```powershell
composer install
php bin/console about
php bin/console lint:container
```

Add or update these entries in your existing **`.env.local`**. Keep actual credentials out of committed files. Do not overwrite unrelated local settings.

```dotenv
DEEPSEEK_API_KEY=your-deepseek-api-key
IMPERIUM_DEEPSEEK_MODEL=deepseek-flash
DATABASE_URL="postgresql://YOUR_USER:YOUR_PASSWORD@127.0.0.1:5432/imperium?serverVersion=16&charset=utf8"
```

Use the actual PostgreSQL server version and URL-encode reserved characters in the database username/password. A model override must be registered in the Symfony AI DeepSeek catalog and support Chat Completions, JSON output, and `thinking: {type: disabled}`. The current `deepseek-flash` name is registered through native `ai.model` configuration because the installed bridge catalog predates that name.

If PostgreSQL is already running, use its connection settings. If using the repository's Docker Compose database instead:

```powershell
docker compose up -d database
docker compose port database 5432
```

Use the reported host port and Compose credentials in `.env.local`. The recipe's override can assign a dynamic host port; do not assume it is 5432.

Create the selected new application database if necessary, then apply migrations:

```powershell
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
```

The migration chain now creates interview records, aliases, versioned proposal records, and the nullable proposal approval timestamp, plus the `messenger_messages` table required by the already configured Doctrine Messenger transport. The interview/proposal path is synchronous and uses no worker.

## Use

```powershell
php bin/console imperium:interview
php bin/console imperium:interview --new
php bin/console imperium:interview --list
php bin/console imperium:interview YOUR_INTERVIEW_UUID
```

The default command opens the interview list. Type `new` to create an interview,
or select an existing row to continue or delete it. The `new` option is available
even when the list is empty. `--new` starts an interview directly; `--list` remains
an explicit alias for the default view. Opening or quitting the list creates no record.

Each row has a number, a six-character display ID, and a mission
alias such as `leg-day workout`. The short ID uses the UUID's random suffix; it is
not a unique lookup key. Row selection still resolves to the full UUID, and the
printed resume command retains that UUID.

Seneschal suggests a brief alias with its reply, without an extra model request.
The first nonempty suggested alias is saved and stays stable on later turns. Before
that, or for older interviews, the list uses the first mission message shortened to
80 characters; an interview with no input displays `Untitled mission`. Alias metadata
is optional: a missing or malformed alias does not reject an otherwise valid reply.
Run the new alias migration when updating an existing installation.

Each list row has a selection number. Select that number to open the action menu:
`1` continues the interview, `2` permanently deletes it, and `0` returns to the list.
Deletion removes the selected interview and its transcript, then refreshes the list;
it makes no model call. Interviews currently locked by another operation cannot be
deleted. Row numbers belong to the displayed list and can change after a refresh.
An empty answer or `/quit` at the row prompt exits. With `--no-interaction`, the
numbered list prints and exits without prompting or changing records.

Copy the actual UUID printed when starting an interview. `new` in the menu or `--new` creates a new record; passing the UUID resumes it and shows its saved exchanges.

| Input | Behavior |
|---|---|
| Ordinary text | Save the operator's message, then request Seneschal's reply. |
| `/quit` or an empty answer | Exit; the record is retained. |
| `/retry` | Explicitly retry a saved message whose reply was not completed. |
| `/approve` | Grant permission to draft, only for the current readiness request. |
| `/decline` | Decline drafting and return to clarification. |
| A correction while awaiting permission | Withdraw the pending readiness state and send the correction to Seneschal. |

While requesting a reply, the CLI prints `Waiting for Seneschal...`. Replies are displayed when complete; this version does not stream partial JSON. A ready reply is labeled for review, and the correction/approval choices also appear when resuming an interview awaiting permission. Declining invites you to explain what needs to change without making another model call.

A conversational “yes” remains conversation text. It does not grant permission. After `/approve`, the application records drafting permission and enters the proposal stage. Generation is an explicit menu action.

A saved draft proposal offers **Approve proposal**, **Request revision**, or **Back**. Revision guidance is bounded to 6,000 characters. A revision produces the next proposal version and never overwrites the previous version. Approval applies only to the observed latest version; a stale approval is refused. Approved proposals reopen read-only.

Proposal approval records plan acceptance only. It does not authorize a resource, external effect, credential, tool, or execution.

## Failures and limits

An operator message is saved before inference. A failed provider call or interrupted process leaves a pending reply that can be resumed with `/retry`. Retries are never automatic. If the provider completed a request before the process lost its response, a retry may incur a second charge; this milestone does not claim exactly-once inference.

Each interview allows at most 20 reserved model attempts, including failed or interrupted attempts. Each input is limited to 6,000 characters, retained context before a new request to 80,000 characters, and requested output to 2,048 tokens. These limits bound work; they are not a dollar budget. Input is never silently truncated. Provider idle timeout is 60 seconds and total request duration is capped at 90 seconds.

Symfony Lock prevents overlapping operations on the same interview on one host. The default `flock` store assumes one local machine with a shared lock directory. Doctrine's version field also guards record writes; permission decisions reject a stale observed version. This is not a distributed execution guarantee.

The agent has no registered tools. State changes are controlled by PHP, not by claims made in model text. Model readiness is still a judgment: automated tests establish application behavior, not that a live model always understands correctly or always follows the interview prompt.

Messages are retained locally and sent to DeepSeek for inference. OpenAI remains installed but is inactive for Seneschal; no OpenAI key is needed and there is no automatic fallback. Development logs and database contents remain local private data; never commit them.

The bridge sends Chat Completions requests with JSON mode, `max_tokens: 2048`, and thinking disabled. Symfony AI decodes the JSON, then Symfony Serializer enforces the typed reply and Validator checks its content. Invalid or empty output leaves the saved input pending for explicit retry. Earlier assistant turns are replayed as JSON with `message` and `readyToDraft`; the application-added permission question is excluded from that provider history. Displayed transcripts remain plain text. Readiness reconstructed for history does not grant permission or modify interview state. JSON mode does not provide server-side schema enforcement.

Provider references: [model names](https://api-docs.deepseek.com/quick_start/pricing/), [JSON output](https://api-docs.deepseek.com/guides/json_mode/), and [thinking control](https://api-docs.deepseek.com/guides/thinking_mode/).

If configuration or storage is unavailable, check `DATABASE_URL`, the running PostgreSQL instance, and migration status. If replies fail, check the API key, model access, and network availability. Failures now show a useful hint for authentication, balance, access, model/request options, rate limits, server availability, connection/TLS problems, or invalid reply format. HTTP errors include their numeric status. The DeepSeek response listener preserves statuses before the installed bridge can mistake an error body for a reply. Raw provider exception text is not printed in the interview terminal. See [DeepSeek error codes](https://api-docs.deepseek.com/quick_start/error_codes/).

## Validation

Tests use Symfony's mock HTTP transport through the actual Agent, provider bridge, structured-output conversion, and application container. They do not call DeepSeek or require a paid key. They exercise the CLI and application services, including fresh-kernel resumption, failures, permission decisions, proposal generation/revision, version preservation, stale proposal approval refusal, lock contention, and attempt limits.

Use a dedicated test database. Symfony appends `_test` to the configured PostgreSQL database name in the test environment. The test suite clears interview rows from that test database.

```powershell
php bin/console doctrine:database:create --env=test --if-not-exists
php bin/console doctrine:migrations:migrate --env=test --no-interaction
php bin/console doctrine:schema:validate --env=test
php bin/phpunit
```

If test credentials differ, set `DATABASE_URL` in `.env.test.local`. Keep `.env.test`'s non-secret mock API key; the test configuration routes all model requests into the mock transport.

Local implementation checks used PHP 8.4.22 with an isolated SQLite schema for behavior tests because the execution workspace had no PostgreSQL server. The GitHub `Seneschal interview` workflow runs the same tests against PostgreSQL 16, applies the migration, checks schema consistency, and verifies rollback/reapply on its disposable database. Consult the actual workflow result before treating PostgreSQL validation as passed.

The current proposal-review campaign passes PostgreSQL CI with 39 tests / 341 assertions, including the existing interview behaviors plus proposal generation, restart review without inference, revision history, failed-revision safety, approval persistence, stale-version refusal, and no-inference approval. Single-question pacing and drafting quality remain model behaviors: mocked tests verify the instructions and application boundaries, not general live-model reliability.

A local operator screenshot showed a saved Seneschal question followed by a format-validation failure on the next turn. The failed raw reply was not available for inspection. Consistent JSON history addresses a possible contributor; it does not establish the cause of that incident. Errors now distinguish empty content, invalid JSON, a non-object result, missing fields, wrong field types, an empty/oversized message, and output-limit truncation without showing private reply content.

No live DeepSeek call has been performed by the implementation agent. On 25 September 2026, the operator reported successful live interview and proposal review milestones. The private transcript/database were not independently inspected. The proposal revision/approval campaign still requires its own live operator review before merge. See [NEXT-CAMPAIGN.md](NEXT-CAMPAIGN.md).


## Resource/effect authorization campaign — 25 September 2026

After an approved proposal, the CLI now offers **Prepare authorization request**.
Nothing is created merely by reopening the mission. Preparing the request copies the
approved proposal's resource requirements and limits, then asks the operator to
declare intended external effects (semicolon-separated, or blank for none).

The resulting request offers **Authorize requested scope**, **Refuse requested
scope**, or **Back**. The decision is persisted and reopens read-only. Authorization
itself uses no DeepSeek call and performs no execution.

The campaign's PostgreSQL CI passes **47 tests / 383 assertions**, schema validation,
and migration rollback/reapply. The live operator review remains required before
merge.


## First bounded execution campaign — 26 September 2026

For an authorization whose recorded resources include filesystem write access and
whose effect explicitly permits one local file, the CLI now offers
**Create authorized local file**.

The operation accepts only a filename (no directories) and file contents. Output is
confined to `public/output/`, content is limited to 32 KiB, and an existing target
is never overwritten. The execution attempt is recorded before I/O and, after
success, displays the relative path, SHA-256, and bytes written. No DeepSeek call is
made.

A saved execution attempt prevents another attempt under the same authorization.
Recovery distinguishes PREPARED from EFFECT_STARTED. PREPARED plus an existing target is ambiguous and fails closed; it is never treated as proof that Imperium created the file. Only a persisted EFFECT_STARTED attempt can be closed as success from a matching file hash. No state automatically retries the effect.

PostgreSQL CI passes **61 tests / 461 assertions** plus migration rollback/reapply.
Live operator acceptance remains required before merge.


> **Public-output note:** the bounded executor now writes to `public/output/`.
> Depending on the web-server configuration, files there may be directly reachable
> over HTTP. For that reason an authorization containing `No external publication.`
> is rejected rather than treated as compatible with this executor.


Structured executable authority is displayed separately from proposal context.
For the current public-output executor the canonical grant is
`filesystem.write.public_output / file.create.public`, rooted at
`public/output`, public visibility, `.txt` only, one file, no overwrite,
32 KiB maximum. Legacy free-form authorizations cannot execute.


Historical authorizations that predate structured authority now offer
**Prepare replacement authorization**. The replacement is saved as the next
authorization version and requires a fresh explicit Authorize/Refuse decision.
Earlier authorization versions remain unchanged and queryable as history.
