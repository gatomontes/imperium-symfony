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

### Choices needed when implementation reaches them

- Select the first provider/model and a modest usage limit. Having both bridges installed does not select either provider.
- Confirm the first interface. A local CLI is the proposed smallest starting point; Twig is already available if a browser interface is preferred.
- Confirm use of the installed Doctrine ORM for interview persistence and the actual database engine/connection.
- Decide how the operator is identified for the chosen interface. A local operator-only CLI and a remotely accessible web application have different authentication needs.
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

Start from the new repository baseline and its `AGENTS.md`. Read [IMPERIUM-FLOW.md](IMPERIUM-FLOW.md). Confirm the implementation choices relevant to N1–N4, then build the first working interview and its minimal persistence. Do not restore the old campaign requirements as default acceptance criteria.

The near-term objective is an observable Seneschal interview. The first complete product milestone is one useful mission carried from understanding through delivery with explicit authority and retained evidence.

## Basis

- Operator's restart decisions and revised Atheneum placement in this conversation.
- Supplied cognitive map, with the subsequent Atheneum change taking precedence over its old arrows.
- Repository source and lockfile at the linked restart commits.
- Previously reviewed Atheneum correction handoff, used only to distinguish unfinished historical work from the new baseline.

*Ad Imperium.*
