# Imperium — Steps: Past, Present, and Future

Date: 24 September 2026 (UTC)  
Companion: [IMPERIUM-FLOW.md](IMPERIUM-FLOW.md)  
Repository: [gatomontes/imperium-symfony](https://github.com/gatomontes/imperium-symfony)

## 1. Direction

**The doctrine is Imperium's core business. The application must demonstrate its value through useful work.**

The operator chose to discard the previous implementation and restart with a clean Symfony application. The cognitive map and doctrine survive. The previous implementation's institutions, protocols, tests, and acceptance gates are not automatically requirements of the new runtime.

Build one usable mission path, then expand it. Use Symfony's existing capabilities before writing infrastructure. Add a mechanism when a concrete requirement, demonstrated failure, or newly introduced capability warrants it.

This document records completed work and proposes the implementation sequence. Future rows are planning milestones, not claims of implementation or grants of external execution authority.

## 2. Past — what happened

| Step | Work or decision | Disposition |
|---|---|---|
| P1 | Developed the institutional doctrine and cognitive map: offices, competence, mission formation, interview, review, and authority boundaries. | Retained as design knowledge; the current map and explicit operator decisions guide the restart. |
| P2 | Built and reviewed the former Symfony implementation, including personnel, provider onboarding, authority, evidence, and recovery mechanisms. | Historical implementation work. Its results do not validate the new application. |
| P3 | Began the Atheneum persistence migration to replace expensive journal reconstruction with current records and retained history. | Previous A1 acceptance was still open at the last reviewed handoff. It is not a pending migration task for the fresh application. |
| P4 | Reassessed whether implementation complexity was justified before a useful mission could run. | Operator chose a fresh start, preserving doctrine. |
| P5 | Deleted the previous implementation and created a clean Symfony foundation. | Operator reported deletion; the new root commit on `main` was independently inspected. This is not verification that all old remote branches, tags, or artifacts were deleted. |
| P6 | Installed AI Bundle, Agent, Platform, OpenAI, and DeepSeek integrations. | Dependency declarations and lockfile verified in the new repository. |
| P7 | Revised Atheneum's place in the cognitive map. | Agreed: inside Citadel, no direct connection arrows, shared institutional records and memory. Operator reports the map edit completed. Revised image bytes have not been independently inspected. |

### Restart commits

| Commit | Meaning |
|---|---|
| [`4108219`](https://github.com/gatomontes/imperium-symfony/commit/41082195f2233c0beed6610ffe7340198fdb131c) | New root: clean Symfony foundation. |
| [`f994e82`](https://github.com/gatomontes/imperium-symfony/commit/f994e82d0613fc28030c247c723c4beb6d003d0b) | AI Bundle added and registered. Agent and provider bridges were still absent at this checkpoint. |
| [`e9dc3ad`](https://github.com/gatomontes/imperium-symfony/commit/e9dc3ad7b5a6b4c5406b0d8283a99b27cdfe944d) | Agent, OpenAI, and DeepSeek packages present. Current inspected baseline. |

The former campaign names and checkpoints are historical references. Do not resume O4/O5, PPC10, or Atheneum A1–A5 as prerequisites of this restart unless the operator explicitly reintroduces a particular requirement.

## 3. Present — verified baseline

`main` was rechecked on 24 September 2026 and still points to `e9dc3ad7b5a6b4c5406b0d8283a99b27cdfe944d`. The following findings come from the source and dependency inspection at that commit.

| Area | Present state |
|---|---|
| Framework | Symfony 8.1; FrameworkBundle locked at 8.1.7. |
| PHP | Project declares PHP >=8.4; locked PHPUnit requires >=8.4.1. Local runtime version has not been verified. |
| AI | `symfony/ai-platform`, `ai-bundle`, `ai-agent`, `ai-open-ai-platform`, and `ai-deep-seek-platform` locked at v0.13.0. Generic and Open Responses platform dependencies are also present. |
| AI configuration | `config/packages/ai.yaml` is a commented starter template. No active platform or named agent is configured there. |
| Persistence | Doctrine ORM 3.7.2, DBAL 4.4.4, and migrations installed. PostgreSQL Compose configuration exists; database connectivity is unverified. |
| Interface | Twig, Forms, AssetMapper, Stimulus, and Turbo installed. No custom application controllers yet. |
| Supporting components | Security, Messenger, Validator, Serializer, HTTP Client, Console, and Monolog installed. |
| Authentication | Starter Security configuration; no implemented operator authenticator. |
| Application records | No custom entities, repositories, or migrations yet. |
| Tests | PHPUnit 13.3.4 and test bootstrap/configuration present; no application tests yet. |
| Working conventions | `AGENTS.md` requires native Symfony patterns, Flex installation, autowiring, attributes, migrations, and caller-facing behavior tests. |
| Doctrine documentation | Original map supplied in conversation; Atheneum revision agreed. These two Markdown files capture the restart's decisions and plan, not a complete recovered charter for every office. |

**No app boot, database operation, live model call, working interview, or completed mission has been demonstrated in this inspection.** Installed packages are the foundation, not evidence of operational behavior.

## 4. Next — proposed implementation sequence

These are small delivery milestones. Each should leave an observable capability behind. They are not a new campaign bureaucracy.

| Step | Deliverable | Evidence of completion |
|---|---|---|
| N1 — Run the foundation | Install from the lockfile; confirm PHP/extensions, app boot, container configuration, and the chosen local database connection. | Actual local results from Composer and Symfony diagnostics; resolve concrete failures. |
| N2 — Connect one model | Configure one provider and one explicit model for Seneschal. Keep the other bridge available. Configure credentials locally. | One bounded model response and a clear handled failure for unavailable configuration. No automatic provider fallback yet. |
| N3 — Establish the first interview | Implement one entry point and Seneschal's conversation. Clarify objective, constraints, expected output, success criteria, and material unknowns. | A usable exchange in which Seneschal seeks understanding and asks permission before drafting. |
| N4 — Retain and resume | Persist the interview and its current state through a small Atheneum service using Doctrine repositories. | Close and resume an interview without losing or confusing its exchanges. |
| N5 — Produce the proposal | After permission to draft, produce a versioned proposal with steps, resources, limits, and acceptance criteria. | Operator can request revisions or approve an identifiable proposal version. |
| N6 — Authorize and perform bounded work | Record the permitted resources and effects separately from plan approval; run one narrowly defined task through code-enforced permissions and limits. | A permitted operation succeeds; an unpermitted operation is refused. Relevant retries/interruption behavior is demonstrated for that operation. |
| N7 — Review and deliver | Compare the output to the agreed criteria; retain result, relevant evidence, usage/cost information where available, and disposition. | One real mission reaches delivery or an honest failure/partial outcome through the same ordinary user path. |

N3 and N4 may be delivered together when persistence is needed for a usable first interview. Keep the work small rather than forcing artificial stage boundaries.

### Implementation choices confirmed on 24 September

- Operator initially selected OpenAI, then changed the first Seneschal to DeepSeek; implementation defaults to `deepseek-flash`, with bounded attempts and output. The model is configurable. No live call has been demonstrated yet.
- Operator selected the local CLI.
- Operator selected PostgreSQL through the installed Doctrine ORM. Connection credentials remain local configuration.
- The first interface is operator-only, using local operating-system access. No web authenticator is required for this CLI milestone.
- Select a useful first mission and state its permitted effects. A draft deliverable is a simpler first target than sending messages, publishing, or altering an external system.

These choices are not reasons to reconstruct the previous bootstrap pipeline. Describe the initial configured officer/model honestly; do not claim a completed institutional appointment process that has not been implemented.

## 5. Future — expansion after the first mission

| Capability | Add when |
|---|---|
| Multiple Curia roles and delegated work | A working mission benefits from distinct responsibilities or specialist judgment. |
| Oracle model catalog and selection | Measured task requirements justify choosing among providers/models. |
| Persona and profile lifecycle | Real missions require repeatable construction, suitability assessment, admission, and assembly. |
| Clavium and Armory operations | Missions need managed credentials and explicit tool capabilities beyond the initial configuration. |
| External execution boundaries | Introduce Barbican, Iron Gate, Theatre, and Lazaretto behavior appropriate to the actual external effects and returned material. Implement required protections before those effects are enabled. |
| Background workers and concurrency | Task duration or contention requires them; use Messenger and relevant Symfony components. |
| Expanded Atheneum services | New features need additional records, retrieval, history, or recovery. |
| Senate proceedings | A concrete independent examination or disputed decision requires the formal process. |
| Stronger provenance and isolation | A defined trust boundary, adversary, audit requirement, or deployment model justifies the additional guarantees. |

The cognitive map remains the institutional design. The order in which its offices acquire runtime implementations follows demonstrated need. A mapped office does not automatically require a model, worker, database pair, network API, or separate deployment.

## 6. Rules for continuing the work

1. Preserve the doctrine's distinctions: understanding, proposal, approval, authority, execution, review, and completion.
2. Use the framework for framework work. Do not build custom queues, locks, provider clients, or persistence engines without an identified gap.
3. Keep deterministic work in ordinary application services. Invoke a model where judgment or language work is needed.
4. Keep Atheneum small and feature-driven. Do not require a universal records engine before saving the first interview.
5. Test meaningful behavior through the actual caller path. Match testing and recovery guarantees to the effects introduced.
6. Label statements accurately: proposed, implemented, tested locally, tested live, or deferred.
7. Keep real secrets and private mission evidence out of Git. Do not claim historical tests apply to the fresh code.
8. Preserve existing authorization across a task; ask again when scope or required authority changes, not for every internal step.

## 7. Immediate handoff

Start from the repository and its `AGENTS.md`. Read [IMPERIUM-FLOW.md](IMPERIUM-FLOW.md) and [SENESCHAL-CLI.md](SENESCHAL-CLI.md). The initial implementation is on `codex/seneschal-cli-interview`; use the setup instructions to configure the local database and API key. Do not restore the old campaign requirements as default acceptance criteria.

The near-term objective is an observable Seneschal interview. The first complete product milestone is one useful mission carried from understanding through delivery with explicit authority and retained evidence.

## 8. First interview implementation checkpoint

- **N1:** application boots under PHP 8.4.22; Composer validation, container lint, YAML lint, and ORM mapping checks passed. The initial feature passed PostgreSQL migration/schema validation and rollback/reapply in CI. Provider changes are rechecked by the same workflow; consult the current branch result.
- **N2:** named Seneschal agent and DeepSeek bridge configured. Mocked transport exercises Chat Completions JSON output and typed reply validation. Live model response remains unverified.
- **N3/N4:** CLI interview, persisted exchanges, resume/list, explicit retry, readiness, and permission-to-draft state are implemented. Local suite: 11 tests / 91 assertions passed using an isolated SQLite test database, not a native PostgreSQL server.
- **N5–N7:** remain future work. This version stops after recording permission to draft; it does not generate a proposal or perform a mission.

The source-inspection table above remains the historical pre-feature snapshot at `e9dc3ad`; this checkpoint records the feature branch's additional work. The runbook distinguishes local checks, PostgreSQL CI, and the still-required live smoke check.

## Basis

- Operator's restart decisions and revised Atheneum placement in this conversation.
- Supplied cognitive map, with the subsequent Atheneum change taking precedence over its old arrows.
- Repository source and lockfile at the linked restart commits.
- Previously reviewed Atheneum correction handoff, used only to distinguish unfinished historical work from the new baseline.

*Ad Imperium.*

### CLI interaction refinement — 25 September 2026

The operator approved a small CLI improvement: one focused question per turn,
a brief understanding summary before drafting permission, visible waiting feedback,
and actionable failure messages. The prompt and CLI now implement that interaction;
corrections and explicit approval retain the existing state transitions. No new
schema or proposal-generation stage is introduced. Local mocked tests pass
(16 tests / 188 assertions); consult the feature branch CI for PostgreSQL results.
Live DeepSeek conversation quality and the operator's local connection remain to
be confirmed.

### Follow-up: reply-format failure — 25 September 2026

An operator screenshot showed a saved Seneschal reply followed by a format failure.
The failed payload was not inspected. Assistant history was being replayed as plain
text despite JSON output requirements; it now uses the same JSON fields as replies.
Application-added permission text stays outside that provider history. Format errors
are now specific, and truncated output is rejected even if its JSON parses.
The possible contribution of inconsistent history still needs a live check.
Local tests pass (17 tests / 215 assertions); PostgreSQL CI runs on the branch.
