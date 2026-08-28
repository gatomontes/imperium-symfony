# Iron Gate outbound-email competent decision and issuer route

## Status

`BATCH_4_COMPETENT_ROUTE_AND_ISSUER_CONTRACT_DEFINED_ONLY`

Batch 4 identifies the competent native route for one exact deterministic outbound email and
defines it declaratively in `OutboundEmailAuthorizationIssuanceContract`. No request, decision,
issuance authority, issuance result or outbound authorization is created.

## Competent route

| Stage | Competent actor or service | Exact power | Explicit limit |
| --- | --- | --- | --- |
| Request | occupied `curia.seneschal` | Request one exact `email.send` for a named holder and sealed scope. | A request grants no authority. |
| Decide | `imperator` | `AUTHORIZED` or `REFUSED` on the exact request, rationale and limitations. | The decision performs no action and grants no credential access. |
| Open issuance | authorized Imperator decision | Open one expiring, single-use issuance authority to the exact issuer service. | Refusal opens none; this is not execution authority. |
| Issue | `imperator.outbound-email-authorization-issuer` | Consume the exact issuance authority and materialize the Batch 3 outbound-email authorization. | It cannot change scope, resolve credentials, dispatch or perform I/O. |
| Hold | exact occupied actor named by the request | Hold the sealed, expiring, single-use outbound authorization. | Holding does not expose credentials or permit an alternate consumer. |
| Consume later | `la-cortine.deterministic-boundary-executor` | A future durable claim may consume the authorization for its exact act. | Iron Gate is a consumer, never an issuer or decision owner. |

This assignment follows existing constitutional ownership. Curia orchestrates and requests;
Imperator owns the external-action decision. Clavium may later issue the separately scoped opaque
credential capability, but it does not approve the action. La Cortine validates and consumes
already-decided power; it does not manufacture it.

## Required lineage

The request binds the requester and holder identities, purpose, complete Batch 3 email scope,
provider-idempotency identity, request time and transitive expiry. It remains sealed with
`authority_granted=false`.

The decision binds the exact request ID and digest, Imperator actor, disposition, rationale,
limitations and expiry. Only `AUTHORIZED` opens one issuance authority. That authority binds its
own ID, exact issuer service, permitted transition, request and scope digests, expiry, single-use
state, exercisability, consumption state and no continuing authority.

The issuance result must bind the decision, request, consumed issuance authority, exact emitted
`imperium.la-cortine.deterministic-outbound-email-authorization/v1` record, issuer and issuance
time. It cannot widen scope or outlive the decision.

## Why adjacent authorities are ineligible

- Curia's bounded internal execution authorization expressly denies tools, credentials, network
  and external action.
- Operational provider-resource decisions concern cognition expenditure and expressly deny network
  and provider-invocation authority.
- Clavium owns credential capability, not the institutional decision to send an email.
- Iron Gate and `DeterministicBoundaryExecutor` are enforcement consumers, not political issuers.
- A CLI operator invocation is an initiation surface, not a sealed competent decision record.

## Closed boundary

All three schemas are declarative only:

- `imperium.curia-deterministic-outbound-email-request/v1`;
- `imperium.imperator-deterministic-outbound-email-decision/v1`; and
- `imperium.imperator-outbound-email-authorization-issuance/v1`.

Batch 4 adds no persistence, service, route, command option, provider header, credential use,
execution claim, journal, receipt, admission or reconstruction behavior. The consumer posture
remains `BLOCKED_ROUTE_NOT_IMPLEMENTED_AND_NO_DURABLE_CLAIM`.
