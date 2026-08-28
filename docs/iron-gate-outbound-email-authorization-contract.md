# Iron Gate deterministic outbound-email authorization contract

## Status

`BATCH_3_AUTHORIZATION_SHAPE_DEFINED_NOT_ISSUED`

`imperium.la-cortine.deterministic-outbound-email-authorization/v1` defines the evidence required
before one exact deterministic `email.send` may be claimed. It is declarative. It does not identify
a competent issuer, create a source decision, issue or consume authority, select a holder, resolve
a credential, add a provider header, perform I/O, persist a receipt or open Lazaretto.

## Authority lineage and actor

The authorization must bind one sealed source decision by ID, digest, schema, disposition and
decision owner. The issuer and holder must each be exact actors identified by actor, Office, Seat,
binding and runtime-principal identities. Classification, command possession and an internal
cognition authorization grant no outbound authority.

The competent issuer remains `ABSENT`. This contract deliberately refuses to nominate one. A later
separately authorized batch must establish the native decision route before any record may exist.

## Exact scope

One authorization binds:

- operation `email.send`;
- one AgentMail inbox and one provider endpoint;
- recipient-set, subject, body, attachment-manifest and complete payload digests;
- commission identity and credential-reference digest without credential secret material; and
- exact expected return contract.

Any scope change requires a new source decision and authorization. The authorization is expiring,
single-use and non-continuing. One durable execution winner must consume it before provider I/O.

## Provider-safety binding

The authority must bind strategy `PROVIDER_IDEMPOTENCY_KEY`, provider and endpoint, the exact
idempotency key and its digest, complete request fingerprint, official provider-contract reference
and provider key expiry. The authority expiry may not outlive the provider key. Reuse of the same
key is permitted only for the identical request fingerprint and only through a durable claim's
recovery rule. A key is not authority and does not replace single-use consumption.

## Closed implementation boundary

Batch 3 defines no service and migrates no issuer or consumer. `AgentMailEmailSendCommand` still
fabricates process-local identifiers, `AgentMailEmailTransport` still supplies no governed stable
idempotency key, and no durable claim, provider journal, receipt store or reconstruction path has
been opened. The consumer posture remains `BLOCKED_NATIVE_ISSUER_AND_DURABLE_CLAIM`.
