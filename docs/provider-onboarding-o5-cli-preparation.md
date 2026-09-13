# O5-B0 CLI journey — preparation only

O4 performance work is deferred by the operator. Preserve its V2 checkpoint; PR #798 remains draft and unmerged. O5 implementation and end-to-end acceptance wait for accepted O4 integration. This preparation selects documentation only: no PHP changes, profiling, provider activity or CI runs.

The existing [onboarding contract](../contracts/provider-onboarding.md) and [continuation contract](../contracts/provider-onboarding-continuation.md) own command schemas, authority, replay, statuses and exit codes. This document maps them into an operator experience; it does not amend approved bytes or claim the proposed CLI exists. [Example sessions](provider-onboarding-o5-cli-examples.md). [Implementation checklist](handoffs/provider-onboarding-o5-cli-checklist.md).

## Command specification

| Proposed invocation after implementation | Purpose | Permitted effect |
| --- | --- | --- |
| `php bin/console imperium:provider:onboard <public-request-file>` with `mode: preview` in JSON | Explain the requested step, proposed settings, remaining prerequisites and limits | Pure inspection; no ID reservation, state migration, credential read or provider call |
| Same command with `mode: advance` | Submit exactly one authorized registration or ready step | Existing owner performs admission/consumption and bounded effects; no implicit loop through later steps |
| `php bin/console imperium:provider:status <sequence-id>` | Report current progress and the next permissible action | Read-only observation; no credential dependency or automatic authentication probe |
| `php bin/console imperium:provider:resume <public-request-file>` | Recognize/reconcile retained evidence for one exact advancing command | May publish verified recognition/completion metadata; no new claim, dispatch, personnel act or uncommitted assignment |

Preview is a request mode, not a fourth command or a `--preview` switch. The deployment supplies fixed root, clock, adapter and trust. No credential/private-key values in arguments or public files; no force, retry-unknown, reset, activation or enrollment switches. Credential custody and authority admission use their existing separate interfaces.

`onboard` retains the exact v2 request fields: schema, sequence_id, command_id, mode, instance_id, policy_ref, expected_head, predecessor_ref, step_id, evidence_refs. `resume` retains its separate exact v2 fields: schema, sequence_id, command_id, expected_head, recognize_command_ref. Do not invent a simplified approval envelope. Validate unique-key UTF-8 JSON, size/depth bounds, versions, closed fields and registered references through the canonical ingress.

Use the existing request validator's exact domain-specific ID rules; an older generic ID paragraph must not silently broaden its accepted identifiers. Resolve that discrepancy explicitly during implementation review if broader input is desired. Public SHA-256 prefixes and internal bare journal hashes cross only their established validated boundary.

## Operator presentation

Default human output should lead with status, what happened, what remains and one next permissible action. Show proposed Courtthane and formation Locksmith provider/model/configuration settings when actual evidence supports a proposal, the proposed initial Augur base separately, and the relevant policy/evidence references. Do not imply a proposal changed persistent settings or appointed personnel.

Offer `--format=json` as an O5 presentation option, with the existing exact `imperium.provider-onboarding-status/v2` envelope; it must not enter the request fingerprint or change execution. JSON goes to stdout without banners; bounded diagnostics go to stderr. Human and JSON presentations must agree on facts, reason codes, exit and effect count. This rendering option is proposed here, not implemented.

Preserve all seven independent fact dimensions: configuration, credential, authentication, base_selection, augur_authority, assessment and assignment. Missing observations remain unknown, never inferred from another dimension. Status may display previously retained credential facts but must not contact the credential source. Effects describe retained original claims/exposure/receipts; `new_effects_this_command` is false for preview, status and receipt recognition. Unverifiable exposure is null, never zero.

Keep exact underlying reason codes in machine output and explain them plainly for the operator. An admission's internal status such as `RETAINED` is not a substitute for the public status vocabulary. Do not claim current usability from an old application receipt: retain the historical fact while reporting stale prerequisites or unresolved effects.

| Public result | Exit | Operator meaning |
| --- | ---: | --- |
| CONFIGURED, MISSING_CREDENTIAL, AUTHENTICATION_PENDING | 2 | Preparation is incomplete; identify the exact missing observation/evidence |
| BASE_SELECTED, MISSING_AUGUR_AUTHORITY, ASSESSMENT_AUTHORIZED | 2 | A proposal or prerequisite exists; show the next permitted step, never automatic progression |
| RESULT_PENDING | 2 | Known work awaits retained evidence |
| ASSIGNMENT_APPLIED | 0 | A valid committed assignment receipt exists; this does not mean live activation |
| REFUSED | 1 | Explain invalid input, unsupported capability, conflict or terminal refusal |
| OUTCOME_UNKNOWN | 3 | Preserve exposure and refuse automatic retry/refund |

Apply the existing precedence: integrity/conflict/terminal refusal, unresolved effect, valid application receipt, then the first unmet prerequisite. Successful inspection alone is not exit-0 onboarding completion. An absent sequence is REFUSED/SEQUENCE_NOT_FOUND; an absent root is not an invitation to initialize a new installation.

## Journey and interruption rules

1. Begin with admitted public originals and policy; preview the exact request. Display prerequisite gaps without manufacturing approval or contacting the provider.
2. Register the sequence through one explicit advance. Registration binds the existing policy, graph and budget; it grants no additional spend rights.
3. Preview and explicitly advance each ready access, FRESH Augur and W1/W2/W3 step using exact retained predecessor/head references. Reuse existing authorization where it still covers the next step; do not ask for repeated ceremonial approval. Material changes require the actual owner act.
4. After validated assessments, preview the whole Courtthane/Locksmith assignment set. Apply it only through the accepted O4 owner under its exact authority. Persistent settings remain until explicit authorized replacement/revalidation.
5. After interruption, inspect status. Resume recognizes original evidence only. Uncertain dispatch remains OUTCOME_UNKNOWN; an unfinished predecessor blocks new progression. After a reconciliation changes the aggregate head, a new advance uses a new command ID and fresh head with the same logical predecessor.
6. Identical request replay recognizes its original result before fresh progression checks and performs no effect. Changed contents under that command ID refuse with COMMAND_CONFLICT. Resume never reissues a process-local credential capability.

All five operational flags remain false, the actual retry allowlist remains empty, and live commissioning stays separately deferred.

## Existing owners and work still needed

| Surface inspected | Existing responsibility | O5 work / limitation |
| --- | --- | --- |
| `Ledger/CommandLedger::request` and `advance` | Exact request validation, registration, replay and step admission | Thin command routing; audit preview purity rather than assuming the advance entry is a complete CLI preview |
| `Ledger/Recovery::status` | Journal observation and retained-command presentation | Currently reports RETAINED and pending_claims; implement evidence-backed public fact/status projection, not relabeling |
| `Ledger/Recovery::resume` | Recognition and custody reconciliation | Preserve canonical command identity and evidence-only behavior; map bounded owner outcomes |
| `DeepSeek/Runtime::advance` and `Ledger/CustodyCoordinator` | Authorized adapter/custody execution | Use fixed composition and offline doubles for tests; CLI must not bypass this owner |
| O4 Assignment assessment/application/settings owners | Whole-set assignment and persistent resolution | Reviewed V2 source exists but accepted integration is pending; runtime dependency blocks O5 implementation |

The inspection used accepted main and the identified O4 V2 source, not a new execution. Existing inspection acquires journal synchronization; its lock-file creation and missing-root behavior need a precise purity check before claiming zero-write status/preview. Never auto-migrate or initialize state to make a read succeed. If owner changes are needed, review them explicitly rather than hiding them in console formatting.
