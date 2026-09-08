> SUPERSEDED DIRECTION: Citadel now owns new-request intake and proposal preparation.
> Understanding alone does not permit drafting; a separate Imperator decision is required.
> Prior local Batch 0 at 7eb2e9abee148e024d227aa7809dfd92979af43f is complete and
> reviewed as preparation. Preserve its findings; do not run this old prompt or
> treat its proposed Stage 1 as authorized.
> Current campaign: /docs/next-campaign-citadel-mission-formation.md.
> Current local runner: /docs/handoffs/citadel-mission-formation-preparation-ready.md.
> Earlier selection and authority wording below is historical.

# Imperator–Seneschal interview entry campaign

Status: selected; local Preparation Batch 0 authorized.
Decision date: 2026-09-07.
Implementation and operational activation: pending discussion after preparation.

## Objective and completion boundary

Make the approved mission entry behavior executable through the institutional
route: Imperator request, direct Seneschal interview, attributable “I understand”
declaration, then actual proposal elaboration under the Seneschal.

The interview's single objective is understanding intent, desired outcome and
relevant constraints. Agreement, endorsement, feasibility, proposal readiness
and authorization are separate judgments. A Seneschal can understand and contest
a mission. Questions and challenges during the interview serve understanding;
preserve objections without substituting them for the Imperator's intent.

Isolde receives, coordinates and preserves the exchange. The Seneschal conducts
the interview directly and alone determines understanding. The Chamberlain
maintains the versioned dossier. Secretary vacancy must not block authenticated
direct access; an absent or incompetent Seneschal is not replaced by clerical
judgment. No user prompt must request these governance steps.

Success is an exercised interview-to-proposal handoff with preserved authority
boundaries. It is not live payroll analysis, broad officer commissioning, a new
Profile admission policy, or universal production-readiness certification.

## Baseline and evidence

Documentation decision commits:
- 92686b5961002fdf04d784fedf2d4a57dfc15061: default mission-formation entry phase.
- bac06f0d9b34b82f65ec3b7e995a76e0c5e3e441: direct interview and understanding gate.

Preparation starts from the current main containing this campaign, not by
resetting to either checkpoint. The remotely reviewed main before publication
was bae87a72da11dd19157cc03df3912e995b854c5f. The source-review implementation,
excerpt work and commissioning audit reported in the owner's Windows checkout
are not thereby proved to be integrated into remote main. Preserve their branches,
working changes, records and prepared Nomina material. Inventory any dependency
before proposing its integration; do not merge unrelated local work implicitly.

Source observations at bac06f0d9b34b82f65ec3b7e995a76e0c5e3e441:
- CuriaRequestCommand dispatches CurianAudience::open.
- CurianAudience requires CURIA_READY and all three Curial occupancies, then opens
  audience cognition authority and calls SeneschalCognitionGateway::decide.
- CuriaRespondCommand dispatches CurianDeliberation::respond, which obtains
  deliberation cognition authority and calls the gateway's advance method.
- SymfonyAiSeneschalCognitionGateway permits MISSION_PLAN_DRAFTED on advance;
  its return contract does not define an explicit understanding declaration.
- The opening path's mandatory Secretary check conflicts with the approved
  direct-access rule and needs bounded investigation.
- The existing tests and governance invoker must be traced before concluding
  what is enforced in the complete path. These are source observations, not a
  production execution result.

## Ordered campaign stages

| Stage | Work | Exit evidence | Authority now |
| --- | --- | --- | --- |
| 0 — Preparation | Trace the actual entry, interview, cognition authority, records and proposal consumers. Identify the first missing connection and present implementation choices. | Source-cited inventory, authority map, minimal proposed changes, proof plan and exact next step. | Authorized locally, offline. |
| 1 — Interview and understanding | After discussion, implement the approved direct interaction and attributable understanding transition through existing production surfaces. | Focused tests of authorship, understanding with dissent, unanswered clarification and record preservation. | Pending approval of the implementation choices. |
| 2 — Proposal handoff | Connect the understanding gate to every relevant actual proposal producer and consumer; preserve authorization and revision boundaries. | Positive handoff and bypass/refusal proof through the real integration path. | Pending approval. |
| 3 — Integrated proof and review | Exercise the real commands with isolated test providers, independently reconstruct the records, review the exact candidate and produce the owner-run handoff. | Reviewable proof packet, limitations and genuine operational prerequisites. | Pending approval. |

Stages may be consolidated after Batch 0 if evidence supports it. Do not invent
additional stages to meet a count. No live provider interview is authorized by
this preparation. Before a live trial, present its real identity, custody,
outgoing content, provider/model, budget, authority basis and owner actions.

## Batch 0 work

1. Read repository instructions, the local handoff and every required source.
   Verify branch, HEAD, working changes and relevant local-only dependencies.
2. Trace request intake through occupied Seneschal identity, direct dialogue,
   exact statement preservation, turn persistence and existing draft-plan entry.
   Attribute who asks, interprets, records and decides; code and caller edges
   must substantiate the map.
3. Trace cognition authorization to the provider boundary for the interview
   itself. Distinguish non-authorizing intake, authorized interview cognition,
   resource-bearing planning and mission execution. Determine what authentic
   standing/bootstrap authority exists, if any. If none exists, identify the
   missing owner decision and smallest legitimate setup. Do not infer metered
   authority from intent, from “I understand,” or from a newly generated local act.
4. Identify the smallest representation for an attributable understanding
   declaration and understood intent bound to the exact proceeding, interview
   record and current Seneschal. Separate this cognitive judgment from mechanical
   validation. A keyword in user prose, a schema-valid model response or a copied
   boolean cannot independently establish the required provenance or authority.
   Schema validity also cannot prove that the model actually understands.
5. Inventory every relevant path able to elaborate or admit an actual proposal,
   including alternate commands/services, direct store input and legacy records.
   Propose where the gate must be checked before elaboration starts as well as
   before a result is accepted. Merely rejecting a completed premature draft
   does not establish that proposal elaboration was prevented.
6. Cover corrections, question attribution, replay/idempotency, competing turns,
   stale declarations, changed intent, Seneschal succession and Secretary vacancy.
   Preserve previous statements and historical dossiers; do not silently grant
   legacy records the new declaration or reinterpret refusals as understanding.
7. Propose the minimum terminal handoff to the existing proposal/dossier route.
   Identify which planning and later authorization boundaries remain separate.
   No new owner-supplied Profile route or replacement authority system.
8. Return the actual gaps and only the human decisions needed to resolve them.
   Do not ask the owner for internal identifiers that do not exist. Finish the
   inventory and concrete design options before stopping for that discussion.

## Verification scope

Preparation may add focused characterization tests or an isolated fake-provider
harness when needed to establish a specific source finding. Production commands
must not run against the real state root or invoke live cognition. Fake providers
and synthetic identities belong only in isolated tests.

Future implementation proof must include:
- a plain objective with no governance wording still enters the interview;
- absent or forged understanding cannot begin actual proposal elaboration;
- the Seneschal can understand while preserving a substantive objection;
- Secretary recording or a user saying “I understand” cannot impersonate his act;
- wrong proceeding, changed intent, stale occupancy and conflicting turns refuse;
- restart/replay preserves exact records without hidden additional provider calls;
- direct authenticated access works without a Secretary under the approved policy;
- a valid declaration reaches the established proposal path, while granting no
  resource, personnel, Profile, deployment or execution authority;
- preauthorized cognition is distinguished from new resource demands, with no
  claim that fake-provider proof establishes real operational authority.

Select tests based on actual changed surfaces and repository gates. Keep exact
test-to-commit attribution. Do not repeat the full suite for inventory-only work
unless a required gate or concrete risk calls for it. Later terminal review
assesses the complete implementation through its actual consumers.

## Preservation and scope limits

This selects preparation only. No production code changes, bootstrap regeneration,
officer commissioning, trust enrollment, real authority/lease issuance, live
provider call, credential handling, private source transmission, protected-state
mutation, payroll execution, pricing renewal, deployment, push or merge of local
implementation, or branch deletion follows from the local runner.

A remote documentation merge does not install or activate a revised Seneschal
Profile. Any later profile derivation, approval and activation must use the
established institutional process. Do not edit sealed bootstrap Profile artifacts.

Preserve Delegate Steps 1–69, the completed inspector campaign, its evidence and
custody qualifications, and all subsequent source-review work. Mark this campaign
as selected/preparation-ready, not implemented, operational or complete.

## Required preparation deliverables

- docs/imperator-seneschal-interview-preparation.md: exact source identities,
  call map, gap classification, authority and data boundaries, local-only work,
  proposed decisions and minimal changes.
- docs/handoffs/imperator-seneschal-interview-preparation-complete.md: disposition,
  exact tested commit if applicable, remaining decisions and next local step.
- Updated current entries in the steps and flow without rewriting history.
- An allowlisted review ZIP and SHA-256 manifest containing relevant source
  excerpts with exact file identities, sanitized reports and any offline proof.
  No private chains, keys, credentials, raw private journals or Nomina source.

Commit only the relevant local preparation material. Stop before Stage 1 and
present the specific implementation choices for discussion.

Local entry: [handoffs/imperator-seneschal-interview-preparation-ready.md](handoffs/imperator-seneschal-interview-preparation-ready.md).

*Nulla requies.*
