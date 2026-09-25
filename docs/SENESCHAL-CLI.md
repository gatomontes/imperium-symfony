# Seneschal — first working CLI interview

24 September 2026. Implements the first interview and minimal Atheneum persistence described in [IMPERIUM-STEPS.md](IMPERIUM-STEPS.md) and [IMPERIUM-FLOW.md](IMPERIUM-FLOW.md).

## What this version does

- Starts or resumes an interview from a UUID.
- Uses the configured Symfony AI agent with DeepSeek. Default model: `deepseek-flash`.
- Saves exchanges and current state in PostgreSQL through an Atheneum application service and Doctrine ORM.
- Prompts Seneschal to ask one focused question per turn, adapt to free-form answers, and summarize the agreed understanding before signaling readiness.
- Lets Seneschal clarify intent and signal readiness. The application then asks: “I am ready to draft a proposal. Do you approve?”
- Records drafting permission only through `/approve`, while a current readiness request is pending. `/decline` or a typed correction continues the interview.
- Stops at drafting permission. Proposal generation, plan approval, resource authority, tools, external mission execution, and formal personnel commissioning are later work.

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

The initial migration was generated from ORM metadata for PostgreSQL. It creates the interview table and the `messenger_messages` table required by the already configured Doctrine Messenger transport. The interview itself is synchronous and uses no worker.

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

A conversational “yes” remains conversation text. It does not grant permission. After `/approve`, the application records the decision and exits without generating a proposal or authorizing execution.

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

Tests use Symfony's mock HTTP transport through the actual Agent, provider bridge, structured-output conversion, and application container. They do not call DeepSeek or require a paid key. They exercise the CLI and application services, including fresh-kernel resumption, failures, permission decisions, lock contention, and attempt limits.

Use a dedicated test database. Symfony appends `_test` to the configured PostgreSQL database name in the test environment. The test suite clears interview rows from that test database.

```powershell
php bin/console doctrine:database:create --env=test --if-not-exists
php bin/console doctrine:migrations:migrate --env=test --no-interaction
php bin/console doctrine:schema:validate --env=test
php bin/phpunit
```

If test credentials differ, set `DATABASE_URL` in `.env.test.local`. Keep `.env.test`'s non-secret mock API key; the test configuration routes all model requests into the mock transport.

Local implementation checks used PHP 8.4.22 with an isolated SQLite schema for behavior tests because the execution workspace had no PostgreSQL server. The GitHub `Seneschal interview` workflow runs the same tests against PostgreSQL 16, applies the migration, checks schema consistency, and verifies rollback/reapply on its disposable database. Consult the actual workflow result before treating PostgreSQL validation as passed.

The CLI interaction improvements passed 28 local tests with 283 assertions, including the default list/new flow, alias persistence/fallback, compact IDs, numbered selection, deletion persistence and lock contention, noninteractive listing, resumed corrections, JSON history replay, truncated replies, malformed replies, and safe failure hints. Single-question pacing and summary quality are prompt instructions: mocked tests verify that these instructions reach the provider, not that a live model always follows them. The PostgreSQL workflow validates the current branch separately.

A local operator screenshot showed a saved Seneschal question followed by a format-validation failure on the next turn. The failed raw reply was not available for inspection. Consistent JSON history addresses a possible contributor; it does not establish the cause of that incident. Errors now distinguish empty content, invalid JSON, a non-object result, missing fields, wrong field types, an empty/oversized message, and output-limit truncation without showing private reply content.

No live DeepSeek call has been performed by the implementation agent. On 25 September 2026, the operator reported a successful local interview and supplied a terminal screenshot confirming that permission to draft was recorded. This is operator-reported live smoke evidence; the private transcript and database were not independently inspected, and it does not establish general conversation quality. The next campaign is described in [NEXT-CAMPAIGN.md](NEXT-CAMPAIGN.md).
