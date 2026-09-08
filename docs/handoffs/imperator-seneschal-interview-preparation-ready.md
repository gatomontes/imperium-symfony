> SUPERSEDED DIRECTION: Citadel now owns new-request intake and proposal preparation.
> Understanding alone does not permit drafting; a separate Imperator decision is required.
> Prior local Batch 0 at 7eb2e9abee148e024d227aa7809dfd92979af43f is complete and
> reviewed as preparation. Preserve its findings; do not run this old prompt or
> treat its proposed Stage 1 as authorized.
> Current campaign: /docs/next-campaign-citadel-mission-formation.md.
> Current local runner: /docs/handoffs/citadel-mission-formation-preparation-ready.md.
> Earlier selection and authority wording below is historical.

# Local handoff: Imperator–Seneschal interview Preparation Batch 0

Status: ready for local preparation only.

Read [the campaign](../next-campaign-imperator-seneschal-interview.md) first.
Start in an isolated worktree of current main containing this file. Preserve the
existing Windows source-review checkout, uncommitted work and local-only commits.

## Required reading

Read these exact files and then follow their directly relevant implementation
dependencies. Read any applicable AGENTS.md present locally; none was found in
the remotely inspected tree at bac06f0d9b34b82f65ec3b7e995a76e0c5e3e441.

- docs/next-campaign-imperator-seneschal-interview.md
- docs/next-lifecycle-delegate-mission-route.md (current entry phase and Step 1)
- docs/delegate-mission-flow.md (current direction and preserved lifecycle boundary)
- imperium-doctrine.md
- contracts/mission-planning.md
- contracts/planning-dossier.md
- contracts/imperator-planning-dossier-review.md
- offices/curia/doctrine.md
- offices/curia/mechanics.md
- offices/curia/profile-seneschal.md
- offices/curia/seat-resident-seneschal.md
- offices/curia/profile-secretary.md
- offices/curia/seat-resident-secretary.md
- offices/curia/profile-chamberlain.md
- src/Command/CuriaRequestCommand.php
- src/Command/CuriaRespondCommand.php
- src/Imperium/Runtime/Curia/CurianAudience.php
- src/Imperium/Runtime/Curia/CurianDeliberation.php
- src/Imperium/Runtime/Curia/ProceedingStore.php
- src/Imperium/Runtime/Curia/SeneschalCognitionGateway.php
- src/Imperium/Runtime/Curia/SymfonyAiSeneschalCognitionGateway.php
- src/Imperium/Runtime/Curia/CurianCognitionAuthorityService.php
- src/Imperium/Runtime/Curia/CurianGovernanceCognitionAuthorityResolver.php
- src/Imperium/Runtime/Clavium/GovernanceCognitionInvoker.php
- src/Imperium/Runtime/Curia/PlanningDossierAssemblyService.php
- src/Imperium/Runtime/Curia/ImperatorPlanningDossierReviewService.php
- src/Imperium/Runtime/Curia/MissionAuthorizationDerivationService.php
- src/Imperium/Runtime/Curia/ImperatorActs.php
- src/Imperium/Runtime/Curia/CommissioningService.php
- config/services.yaml
- config/packages/ai.yaml
- tests/Imperium/Runtime/CurianAudienceTest.php
- tests/Imperium/Runtime/CurianDeliberationTest.php
- tests/Imperium/Runtime/CurianGovernanceCognitionBoundaryTest.php
- tests/Imperium/Runtime/SymfonyAiSeneschalCognitionGatewayTest.php
- tests/Imperium/Runtime/PlanningDossierAssemblyServiceTest.php
- tests/Imperium/Runtime/ImperatorPlanningDossierReviewServiceTest.php
- .github/workflows/phpunit.yml

Inspect only public configuration and source; do not resolve secret values.
Do not run request/respond to “see what happens”: their production path reaches
cognition. Trace first; characterize only in an isolated offline test environment.

## Working example

“Examine Nomina's hourly-earnings calculation for an actionable functional defect.”

This is a representative request for designing and testing the entry path. It
does not authorize a live mission or transmission of the prepared payroll source.
Use synthetic data for any offline conversation exercise. The implementation agent
must not substitute its own interview for an Imperium-executed one.

## Resume prompt

Continue Imperium locally with Imperator–Seneschal Interview Preparation Batch 0.

Read this handoff, the campaign, applicable repository instructions and all required
sources. Verify current HEAD, branch and worktree state. Preserve the existing
source-review branches, prepared Nomina input, historical records and real installation.

The approved behavior is:
Isolde receives and coordinates; the Seneschal interviews the Imperator directly;
the Chamberlain maintains the dossier. The interview's single completion criterion
is the Seneschal's explicit “I understand.” Understanding can coexist with objection
and is not agreement, endorsement, feasibility, proposal readiness or authorization.
Actual proposal elaboration begins only after that declaration. Planning and
authorization are Imperium defaults, not instructions the user must put in a prompt.

Complete the campaign's Batch 0 source inventory, cognition-authority trace,
understanding-gate design options, producer/consumer and bypass map, and focused
offline characterization where needed. Determine the first missing production
connection and the smallest complete path to the existing proposal machinery.
Identify authentic interview cognition prerequisites without creating or assuming
authority. Inspect public metadata only.

Return a concrete source-cited preparation report and handoff, minimal proposed
changes, meaningful proof plan and only the decisions that require our discussion.
Update steps and flow with the actual preparation disposition, commit sanitized
local preparation material and produce an allowlisted review ZIP plus hash manifest.

Do not implement Stage 1, invoke a provider, handle credentials, transmit source,
commission officers, regenerate bootstrap artifacts, change protected state,
activate a revised Profile, execute a mission, deploy, push, merge or delete branches.

Stop after preparation so we can discuss the implementation choices.
Nulla requies.
