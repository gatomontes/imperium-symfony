# Iron Gate outbound-email native records

## Status

`BATCH_5_NATIVE_AUTHORIZATION_ROUTE_IMPLEMENTED_NO_CONSUMER`

Batch 5 implements the competent route selected in Batch 4 as three immutable record transitions:

| Transition | Competent actor | Durable record | Authority effect |
| --- | --- | --- | --- |
| Request | occupied `curia.seneschal` | `imperium.curia-deterministic-outbound-email-request/v1` | requests one exact act; grants none |
| Decision | Imperator | `imperium.imperator-deterministic-outbound-email-decision/v1` | refusal opens nothing; authorization opens one expiring issuance authority |
| Issuance | separate Imperator issuer | `imperium.imperator-outbound-email-authorization-issuance/v1` | consumes that issuance authority and embeds one exact outbound authorization |

The records bind the instance, actor, holder, request and decision digests, exact commission, operation,
destination, payload digests, credential-reference digest, expected return contract, provider
idempotency identity and expiry. Every record is content-digested and immutable.

## Atomicity and replay

Request replay is keyed by the complete deterministic request identity. One Seneschal occupancy may
request more than one distinct act. Decision is a one-winner transition per request. Issuance is a
one-winner transition per single-use issuance authority.

The issuance and issued authorization are one immutable aggregate. Embedding the authorization in
the issuance record avoids a partial two-file state in which issuance authority is marked consumed
without the resulting authorization, or an authorization exists without evidence of consumption.
Exact replay returns the same record; competing content fails closed.

Refusal has `issuance_authority=null` and cannot be converted into an issuance. Digest tamper in any
source record fails before a downstream transition. Only a credential-reference digest is carried;
no credential material is accepted or persisted.

## Closed boundary

These services do not construct or migrate `AgentMailEmailSendCommand`, create an execution claim,
consume a credential capability, add a provider header, invoke a provider, create a raw receipt or
admit evidence to Lazaretto. `external_action_performed=false` is sealed into decision and issuance.

The resulting authorization is deliberately unconsumed. No runtime consumer is
`DURABLE_RECEIPT_BOUND`; the existing perimeter posture remains blocked until a later, separately
authorized claim transition exists.

## Smallest safe continuation

Only a separately authorized Batch 6 may define and prove one durable execution-claim transition
that consumes the embedded authorization before external I/O. It must stop before credential
resolution, command migration, provider invocation, raw receipt, Lazaretto admission and sortie.
