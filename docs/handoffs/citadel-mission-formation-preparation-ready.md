# Local handoff: Citadel mission formation Preparation Batch 0

Status: SUPERSEDED as an entry point. Preparation is complete and reviewed.
Use [citadel-mission-formation-implementation-ready.md](citadel-mission-formation-implementation-ready.md).
The original task and reading list below are retained as historical instructions;
do not rerun Batch 0 or treat its pending choices as unresolved.

## Start and preservation

Use current main containing this file in a new isolated worktree. Read applicable
AGENTS.md locally. None was present in the inspected remote tree at
56f4ec70f292ad5edfebedfcf8a40fa2175f48d4. Verify current HEAD and worktrees; retain
later/unrelated work. Do not reset or merge the existing source-review branches.

The prior preparation is already complete at local commit
7eb2e9abee148e024d227aa7809dfd92979af43f. In a shared worktree repository, read it
without switching or changing its files:

```powershell
git show 7eb2e9abee148e024d227aa7809dfd92979af43f:docs/imperator-seneschal-interview-preparation.md
git show 7eb2e9abee148e024d227aa7809dfd92979af43f:docs/handoffs/imperator-seneschal-interview-preparation-complete.md
```

Use its reviewed packet if that local object is unavailable. Do not claim its
commit was merged or repeat completed characterization unnecessarily. If neither
source is available, finish useful current-source work and identify that exact
missing input. Preserve the prior evidence as historical, not a Citadel proof.

## Required reading

Read the following current documents:
- contracts/citadel-mission-intake.md
- docs/next-campaign-citadel-mission-formation.md
- docs/next-lifecycle-delegate-mission-route.md (current entry and existing Step 1)
- docs/delegate-mission-flow.md (current direction and preserved lifecycle boundary)
- imperium-doctrine.md
- contracts/mission-planning.md
- contracts/planning-dossier.md
- contracts/imperator-planning-dossier-review.md
- contracts/bootstrap-state-machine.md
- contracts/seneschal-suitability.md
- offices/README.md
- offices/curia/README.md
- offices/curia/doctrine.md
- offices/curia/mechanics.md
- offices/curia/profile-seneschal.md
- offices/curia/seat-resident-seneschal.md
- offices/curia/profile-secretary.md
- offices/curia/seat-resident-secretary.md

Inspect these concrete entry/dependency sources and follow only relevant callers,
consumers, configuration and tests. Reuse prior findings where source is unchanged:
- src/Command/CuriaRequestCommand.php
- src/Command/CuriaRespondCommand.php
- src/Imperium/Runtime/Curia/CurianAudience.php
- src/Imperium/Runtime/Curia/CurianDeliberation.php
- src/Imperium/Runtime/Curia/ProceedingStore.php
- src/Imperium/Runtime/Curia/SymfonyAiSeneschalCognitionGateway.php
- src/Imperium/Runtime/Curia/CurianCognitionAuthorityService.php
- src/Imperium/Runtime/Curia/CurianGovernanceCognitionAuthorityResolver.php
- src/Imperium/Runtime/Cognition/GovernanceCognitionRequestService.php
- src/Imperium/Runtime/Imperator/GovernanceProviderResourceDecisionService.php
- src/Imperium/Runtime/Clavium/GovernanceCognitionLeaseService.php
- src/Imperium/Runtime/Clavium/GovernanceCognitionInvocationClaimService.php
- src/Imperium/Runtime/Clavium/GovernanceCognitionInvoker.php
- src/Imperium/Runtime/Curia/PlanningDossierAssemblyService.php
- src/Imperium/Runtime/Curia/ImperatorPlanningDossierReviewService.php
- src/Imperium/Runtime/Curia/MissionAuthorizationDerivationService.php
- src/Imperium/Runtime/Curia/DelegateMissionCapabilityDemandService.php
- src/Bootstrap/MasterMason.php
- src/Bootstrap/StateStore.php
- src/Imperium/Runtime/Bootstrap/V0ActivationService.php
- src/Imperium/Runtime/Bootstrap/RequiredV0SeatRegistry.php
- src/Imperium/Runtime/Imperator/FutureInstanceImperatorPrincipalConstitutionService.php
- .github/workflows/phpunit.yml

Inventory tracked Citadel implementation, instance-root/registry and routing
surfaces before selecting a holder or architecture. Existing runtime namespace
names are not proof of the new responsibilities. Do not boot real request/respond
to test discovery: their path reaches cognition.

## Local task prompt

Begin Citadel-led Mission Formation and Curia Handoff Preparation Batch 0.
Read this handoff, the campaign and required sources. Verify current repository
state and preserve the previous interview/source-review work.

Citadel receives and discusses new requests, checks existing missions and
establishes understanding. “I understand” closes the interview only. The responsible
Citadel cognitive officer then requests permission to draft with disclosed scope,
resources and limits. Actual drafting requires the Imperator's separate approval.
Citadel presents the proposal; only after its separate approval does the competent
path establish a new mission Curia and hand over the complete approved context.
The receiving Seneschal accepts responsibility within the mandate. Each Curia
retains its own cognitive Isolde.

Do not instantiate a provisional Curia to receive a request or invent a Secretariat.
Do not assume an assigned Citadel cognitive officer or standing interview grant
already exists. Establish those facts or present the minimum decision required.

Complete the campaign's preparation tasks, reusing reviewed Batch 0 evidence.
Focus on changed ownership, cross-mission registry and overlap, interview resource
authority, the distinct drafting decision, existing proposal consumers, legitimate
Curia constitution and exact handoff. Propose the smallest complete implementation
and proof plan. Keep semantic judgment separate from mechanical routing/admission.

Use only focused isolated offline characterization where needed. No production
implementation, live cognition, credential handling, source transmission, new
officer commissioning, bootstrap/Profile activation, protected-state changes,
mission execution, deployment, push, merge or branch deletion.

Update steps/flow with actual preparation status, commit relevant sanitized
material locally, and return the preparation report, completion handoff, proposed
decisions, allowlisted review ZIP and SHA-256 manifest. Stop before Stage 1 so
we can settle the specific cognitive-holder and resource-policy decisions.
