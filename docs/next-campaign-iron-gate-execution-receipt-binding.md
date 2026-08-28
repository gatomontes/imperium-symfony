# Next campaign: Iron Gate Execution Authority and Receipt Binding

## Status

`BATCH_4_COMPLETE_COMPETENT_ROUTE_DEFINED_ONLY`

Transactional Authority Consumption Adoption is terminal through Batch 13. This successor is
selected for preparation only. No runtime implementation, external call or authority change is
authorized merely because it appears below.

## Current continuation point

Preparation Batch 0 and contract/assessment Batches 1–4 are complete. AgentMail direct-send
idempotency, the outbound-email authorization shape and the competent Curia request → Imperator
decision → separate Imperator issuance route are declaratively canonical. The route is not
implemented and no durable execution claim or consumer adoption exists. Only bounded Batch 5 may
next be considered, and it is not authorized without an explicit continuation instruction.

## Selected boundary

The campaign concerns production-grade authority consumption at the moment an exact outbound act
crosses Iron Gate and the binding of its raw provider receipt through Lazaretto.

Preparation must inventory both existing lanes without merging them:

1. deterministic execution of an already decided operation, destination and payload; and
2. one-shot sortie execution with external cognition, tools, capabilities and terminal retirement.

The smallest first migration candidate is the deterministic lane. It has no external cognition and
already requires one exact destination, payload digest, operation, commission, authorization,
credential capability and expected return contract. This selection is a hypothesis for Batch 0 to
prove, not authorization to implement it.

## Existing substrate and exact preparation gaps

| Element | Existing evidence | Preparation gap |
| --- | --- | --- |
| Outbound request | `OutboundRequest` binds authorization ID/digest, commission, operation, purpose, mode, destinations, tools, capabilities, payload digest, return contract and expiry. | It is an in-memory object; Preparation must identify its authoritative persistent issuer and exact single-use execution power without inventing one. |
| Iron Gate dispatch | `IronGate::dispatch()` validates expiry and separates deterministic from sortie mode. | Execution and sortie IDs are random and dispatch is not durable. There is no shared winner identity, replay contract or recovery record. |
| Deterministic validation | `DeterministicBoundaryExecutor` validates mode, payload digest, one destination, credential scope/expiry and transport support before execution. | Validation, capability consumption, network effect, raw receipt and admission are not one checkpointed transition. |
| Credential capability | `CredentialBroker` exposes opaque bounded issue/consume operations; current tests prove one-use behavior and secret exclusion. | `EnvironmentCredentialBroker` consumption is process-local. Preparation must not redesign credentials, but must identify what durable pre-I/O evidence can truthfully bind their use. |
| External effect | Exact operation, destination and payload reach one transport callback. | A crash after the provider accepts the effect but before a durable receipt is an unknown outcome. Automatic replay is unsafe without provider idempotency and a journal rule. |
| Raw receipt | `RawExternalPayload` binds execution, commission, authorization, content digest, sources, tools, capabilities and observed/received time. | It is not a durable execution receipt and does not itself prove the provider accepted the exact requested effect. |
| Lazaretto admission | `Lazaretto` validates execution/commission/authorization, tool/capability scope and sortie lineage. | Admission is in-memory, the expected return contract is copied rather than semantically validated, and no immutable receipt-to-authority consumption record exists. |
| Sortie lane | Sealed manifest, isolated process, brokered cognition, tool/capability checks, unknown-outcome refusal and `finally` retirement exist. | External cognition, tool execution, provider journal and terminal lifecycle create a different recovery boundary. It may not be smuggled into the deterministic first slice. |

## Preparation Batch 0 only

Preparation must inspect every issuer and consumer of outbound execution power and returned external
evidence, including deterministic transport, sortie cognition/tools, credential capability use,
Iron Gate dispatch, process launch, provider journal, raw payload production, Lazaretto admission
and downstream evidence acceptance.

For each exact act it must inventory:

1. authoritative source IDs/digests, competent issuer and holder;
2. operation, destination, payload, tool, capability, credential-reference and return-contract scope;
3. expiry, single-use identity, retry and provider-idempotency semantics;
4. dispatch/execution identity and every competing path;
5. lock scope/order and the point at which external effect may begin;
6. pre-I/O claim, provider journal, unknown-outcome and recovery behavior;
7. raw receipt, Lazaretto admission and durable result representation;
8. every partial-write/process-death exposure; and
9. existing concurrency, fault, tamper, secret-exclusion and reconstruction proof.

Every requirement must be classified as `EXISTS_CANONICALLY`, `EXISTS_FRAGMENTED`, `ABSENT`, or
`DEFERRED_BOUNDARY`. Preparation must assign an exact consumer posture and define it before use; it
may reuse an existing posture only where the semantics are identical. It must propose the smallest
safe migration sequence and stop if no durable source authority or truthful unknown-outcome rule
exists.

## Preparation stop conditions

Preparation Batch 0 may change inventory, campaign, handoff and documentation-consistency tests
only. It may not:

- change `OutboundRequest`, `IronGate`, `BoundaryDispatch`, either boundary executor, credential
  brokerage, a transport, `RawExternalPayload`, `Lazaretto`, sortie cognition/tools or lifecycle;
- issue, consume, revoke, supersede, propagate or reinterpret authority;
- perform a live external call or create a provider-side effect;
- create a durable claim, journal, dispatch, receipt or admission record;
- merge deterministic execution with sortie cognition;
- expand Lazaretto from exact admission into trust or sanitization policy;
- expose credential material or create new credential-platform work;
- open generalized revocation, propagation, telemetry, reassessment, containment or incidents; or
- reopen Delegate Mission Step 70 or any terminal campaign.

## Provisional sequence

No step is authorized merely because it is listed:

1. **Completed.** Preparation Batch 0 — complete issuer/consumer, effect, receipt, recovery and
   proof inventory;
2. **Completed.** Define separately versioned deterministic execution-claim and receipt-binding
   contracts without migrating a consumer;
3. **Completed.** Define the missing native outbound authorization shape and prove the provider's
   direct-send idempotency contract without issuing authority or adopting a consumer;
4. **Completed.** Identify the competent native decision and issuance route without implementing it;
5. prove competing execution, crash-before-I/O, unknown-outcome and receipt-recovery behavior;
6. add read-only reconstruction from source authority through admitted receipt;
7. assess the sortie lane as a separate boundary only after deterministic proof; and
8. close the campaign documentation-only.

Any missing authority identity, provider receipt, idempotency key, durable checkpoint or recovery
rule must remain `ABSENT` or `EXISTS_FRAGMENTED`; preparation may not manufacture it to preserve the
forecast.

## Required preparation inputs

Read the transactional campaign closeout and coverage audit, the credential-boundary closeout,
`docs/runtime/deterministic-execution-lane.md`, `docs/runtime/agentmail-email-transport.md`,
`todo/defensible-decision-record.md`, `todo/continuous-agent-governance-controls.md`, every PHP file
under `src/Imperium/Runtime/LaCortine` and `src/Imperium/Runtime/Sortie`, the Oracle research
issuer/consumer pair, and all associated tests before proposing a migration.
