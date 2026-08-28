# Iron Gate AgentMail provider-safety assessment

## Status

`BATCH_2_PROVIDER_SAFETY_NOT_PROVED_NO_ELIGIBLE_CONSUMER`

This Batch 2 assessment is documentation-only. It evaluates exactly the deterministic
`email.send` operation performed by `AgentMailEmailSendCommand`. It performs no provider call,
credential resolution, authority issuance or consumption, claim, receipt, admission or runtime
mutation.

## Exact candidate

| Requirement | Evidence | Classification | Consumer posture |
| --- | --- | --- | --- |
| Operation, destination and payload | The command fixes `email.send`, one AgentMail `/messages/send` destination and a payload digest before dispatch. | `EXISTS_FRAGMENTED` | `BLOCKED_SOURCE_AUTHORITY_AND_PROVIDER_SAFETY` |
| Native source authorization | The command creates random commission, authorization and request identifiers inside the executing process. No persisted competent decision authorizes the exact recipient, subject, body, attachment digest and inbox. | `ABSENT` | `BLOCKED_SOURCE_AUTHORITY_AND_PROVIDER_SAFETY` |
| Curia bounded execution authorization | `BoundedExecutionAuthorizationService` authorizes one internal iteration while explicitly setting tool, credential, external-action and network authority false. It cannot be reinterpreted as outbound email authority. | `DEFERRED_BOUNDARY` | `INELIGIBLE_AS_OUTBOUND_SOURCE_AUTHORITY` |
| Provider idempotency for direct send | AgentMail documents `clientId` idempotency for create operations such as drafts, but advises applications to track sent message IDs or use the draft workflow to prevent duplicate sends. The direct send examples expose no caller-supplied idempotency identity. | `ABSENT` | `NON_REPLAYABLE_UNKNOWN_OUTCOME_REQUIRED` |
| Unknown outcome | A timeout or process death after provider acceptance and before the returned message ID is observed cannot be distinguished locally from no effect. The current lane has no durable pre-I/O claim or provider-outcome journal. | `ABSENT` | `UNKNOWN_REPLAY_PROHIBITED` |
| Provider receipt | A successful direct send returns a Message with a provider message ID; the transport validates the expected response shape. | `EXISTS_CANONICALLY` | `SUCCESS_ONLY_NOT_DURABLE` |
| Durable receipt binding and recovery | `RawExternalPayload` and Lazaretto admission are process-local and no outbound receipt store or read-only reconstruction boundary exists. | `ABSENT` | `NO_FORWARD_RECOVERY` |

## Provider evidence

Official AgentMail documentation checked on 2026-08-28:

- [Preventing duplicate sends](https://docs.agentmail.to/knowledge-base/preventing-duplicate-sends)
  limits `clientId` idempotency to create operations, including draft creation.
- [Rate limits](https://docs.agentmail.to/knowledge-base/rate-limits) advises tracking sent message
  IDs in the application or using the draft workflow for duplicate-send prevention.
- [Messages](https://www.agentmail.to/docs/messages) documents direct send returning the Message
  object and its message ID, but documents no caller-supplied direct-send idempotency key.

These sources do not prove that retrying the exact direct send is safe after an unknown outcome.
Application tracking of a message ID is available only after a response is observed and therefore
does not close the acceptance-before-response crash window. The draft workflow is a different
provider operation and is not silently substituted for the assessed direct-send consumer.

## Decision

The candidate fails both prerequisites of the Batch 1 contract:

1. no native, persisted competent authorization exists for the exact outbound email; and
2. neither truthful provider idempotency nor a durable non-replayable unknown-outcome boundary is
   present for direct send.

Accordingly, no deterministic consumer is eligible for migration. Automatic retry after an
unknown outcome is prohibited. A provider message ID observed on success is not retroactive proof
that an unobserved attempt did or did not take effect.

## Smallest safe continuation sequence

No item below is authorized merely because it is listed:

1. separately define the competent issuer and durable, exact, expiring, single-use outbound-email
   authorization; do not derive it from internal cognition authority;
2. separately assess a provider operation with explicit replay safety, including whether draft
   creation plus provider-controlled scheduling has truthful end-to-end semantics;
3. only after both proofs, design the durable pre-I/O winner and non-replayable unknown-outcome
   checkpoint without placing network I/O inside an internal rollback fiction; and
4. only then consider migrating one consumer and binding its returned provider receipt.

Sortie, Oracle research admission, inbound Lazaretto, credential-platform, revocation,
propagation, telemetry, reassessment, containment and incident boundaries remain closed.
