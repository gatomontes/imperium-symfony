# One actionable software defect — preparation only

Prepared 2026-09-06 from clean main at bae87a72da11dd19157cc03df3912e995b854c5f,
tree fc5416f1fd08a2d42bfed076547fe5cd96b1a804. No repository or ancestor AGENTS.md
was found; C:/Users/gatom/.codex/AGENTS.md exists and is empty.

The current completion notice in handoffs/local-isolation-scratch-correction-ready.md,
local-isolation-integration.md and local-isolation-batch-5.md closes the preceding
inspection campaign. Its installation, enrollment, mission and evidence are historical
and must remain preserved. This document grants no authority and changes no runtime.

## Implementation finding

There are executable governance and cognition components, but no complete, demonstrated
source-snapshot-to-analysis mission entry point in the inspected implementation.
This is a missing integration finding, not a claim that Imperium has no model runtime.

| Step | Implemented behavior and limit |
| --- | --- |
| Submission | src/Command/CuriaRequestCommand.php:15 accepts a request string. CurianAudience.php:23 checks bootstrap/occupants and invokes Seneschal cognition at lines 73–74. Submission is not an offline preparation command. |
| Plan | src/Imperium/Runtime/Curia/SymfonyAiSeneschalCognitionGateway.php:35 implements deliberation and a structured draft; required_inputs is an array of strings describing inputs. The protected runner instead takes an already formed plan through src/ProtectedMission/Cli.php and Ceremony.php:14. |
| Operator approval | src/Imperium/Runtime/Curia/ImperatorActs.php:17 persists exact plan-digest approval under development-local-cli authority, explicitly without resource/execution grants. ProtectedMission/Ceremony.php:47 validates a signed response and derives authorization separately at line 61. These are distinct approval paths. |
| Protected execution | src/ProtectedMission/Ceremony.php:134 permits only READ_EXACT_GIT_OBJECTS and requires NETWORK, TARGET_MUTATION, PROVIDERS and CREDENTIALS prohibitions. AuthorityOwner.php:105 issues only protected-git-inspector commissions; consume calls InspectionProcess at line 174. |
| Snapshot result | src/ProtectedMission/OfflineGitInspector.php:11 reads loose Git objects and returns per-file base64 bytes, path, blob ID, size and SHA-256 at lines 35–40. AuthorityOwner.php:180 stores the snapshot in the completion receipt. Its findings are file evidence, not software-defect conclusions. |
| Operational cognition | src/Command/CuriaAuthorizeBoundedExecutionCommand.php accepts only target-url; Curia/BoundedExecutionAuthorizationService.php:10 requires exactly an HTTPS target_url. Mission/BoundedOperationalExecutionService.php:12 calls the configured cognition gateway, but there is no source-file input in that contract. |
| Delegate cognition | Curia/DelegateMissionBoundedCognitionCommissionService.php:86 copies mission-use fields into a commission. Citadel/SymfonyAiDelegateMissionCognitionGateway.php:49 serializes objective, scope, deliverables, required_inputs, expected_outcomes and stop_conditions into a model prompt. It does not resolve or verify a source snapshot. Citadel/DelegateMissionBoundedCognitionTurnService.php:102 invokes the gateway and persists the result pending Curia disposition. |
| Other entry points | src/Command/SortieRunCommand.php:16 consumes an already sealed manifest and expected digest. SortieSmokeCommand.php:47 constructs authorization.smoke. Neither supplies the missing approved source-review handoff. |

The search covered command registrations, protected CLI/PowerShell orchestration,
Curia planning and approval, operational and Delegate cognition input producers and
consumers, service aliases, and sortie entry points. Generic strings could contain
code, but that does not establish exact snapshot custody, source approval or a
source-review input contract. No invented command, smoke test or direct assistant
review is offered as this mission's execution.

## Smallest bounded integration

Add one source-review input adapter to the existing bounded cognition path, with
an additive input contract rather than relaxing the protected inspection policy:

1. Admit only the user-supplied service, necessary dependencies, existing tests and
   expected-behavior text into a new mission-local snapshot. Canonicalize relative
   paths, reject links/path escape, record exact bytes and SHA-256 values, and seal
   a manifest digest. No dependency installation, repository crawling or test execution.
2. Bind that snapshot digest, behavior digest, exact prompt/payload, provider/model,
   limits and permitted result to a new Operator-approved analysis commission.
   A local preparation operation must stop before any cognition/provider call.
   The completed inspection receipt provides evidence only, never new authority.
3. Resolve and recheck the approved bytes immediately before the existing governed
   provider invocation. Supply actual source text with stable path/line references,
   not a path or a list of requested inputs. Preserve single-use and uncertain-outcome
   no-replay behavior. Do not reuse closed mission identifiers or consumed authority.
4. Validate and persist an attributable result tied to input/prompt/authorization
   digests and the provider response. Require either one substantiated finding,
   NO_ACTIONABLE_DEFECT_FOUND, or INSUFFICIENT_INPUT. A finding needs location,
   triggering input, expected versus actual behavior, causal explanation, impact,
   smallest suggested correction and a proposed regression case. Unexecuted tests
   and model hypotheses must be labeled as such.

Focused offline acceptance checks should prove exact approved-byte delivery using
a fake provider, refusal after source changes, no provider invocation during
preparation or without approval, enforced limits, no replay, and result provenance.
They must not rerun or alter the closed real campaign. This is a proposed integration,
not implementation authorization or an operationally verified deployment plan.

## Proposed mission packet and limits

No current executable input schema or invocation exists for this source-review
adapter. The following is a preparation contract, not JSON accepted by today's CLI:

- Objective: identify at most one actionable functional software defect in the
  supplied service against the supplied expected behavior.
- Inputs: service files; only necessary dependency source/interfaces; existing tests;
  runtime/language version; expected behavior; known relevant invocation example.
- Proposed local storage: a new var/imperium/mission/source-review/<new-id>/ directory
  containing snapshot/, manifest.json, behavior.txt, proposal.json and later result
  records. This path does not exist by virtue of this document and is not the old
  ProtectedMissionExchange or ProtectedMissionTarget.
- Bounds: at most 30 supplied text files, 128 KiB total file content, one analysis
  provider request, no automatic retry, 32,000 total input tokens including governance
  context, 4,000 output tokens, 120-second request deadline, proposed USD 1 ceiling.
  Stop and revise scope if the supplied packet exceeds any bound; do not silently
  truncate it. No fixes, source execution, external research or broad hardening.
- Acceptance: one evidence-supported finding or an honest no-finding/insufficient-input
  result. Finding a defect is not guaranteed. Reproduction execution is separate scope.

These are proposed limits, not currently enforced capabilities. In particular,
Citadel/DeepSeekDelegateModelConfiguration.php:10 permits only temperature; it does
not accept max_tokens or a monetary cap. The integration must enforce the chosen
limits, including a conservatively priced preflight, before activation. No current
price quotation or provider availability is claimed.

## Provider and disclosure

The inspected Delegate/operational adapter identifies deepseek and deepseek-v4-flash
(src/Imperium/Runtime/Citadel/DeepSeekDelegatePlatformAdapter.php:11–15).
OperationalExecutionCognitionGateway is aliased to the Symfony AI implementation
in config/services.yaml:270. This is repository configuration, not evidence that
the installed closed-campaign package has an active or usable provider binding.

If that adapter is selected later, the explicitly approved source text, tests,
dependency snippets, behavior and prompt would leave the machine for DeepSeek.
The current operational gateway serializes the entire authorization and manifestation
in its prompt (Mission/SymfonyAiOperationalExecutionCognitionGateway.php:105), while
the Delegate gateway sends commission contract fields. The final integration must
show the exact outgoing payload rather than assuming that only source leaves.
Credential resolution is performed by the existing broker; no credential was opened,
tested, requested or handled for this preparation. Provider metadata and transport
disclosure must also be reviewed before any activation request.

No provider calls, mission submission, signing, enrollment, installation, source
transmission, mission execution or runtime test suite occurred in this inspection.
Only repository reads and this additive preparation document were performed.
