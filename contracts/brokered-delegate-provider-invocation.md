# Brokered Delegate provider invocation contract

## Purpose

Make a durable provider-invocation claim mandatory for the Delegate Symfony AI path and keep credential material inside the credential-broker callback.

## Required sequence

1. Citadel validates the exact activation, model binding, access attestation, commission, lease, and turn authority.
2. Clavium atomically creates or returns the single durable invocation claim.
3. The Delegate cognition gateway passes that exact claim to the brokered provider invoker.
4. The credential broker resolves the provider secret only inside its callback.
5. The invoker durably records `INVOCATION_IN_FLIGHT` immediately before constructing and calling the ephemeral Symfony AI platform.
6. The stable persisted idempotency key is attached to the provider HTTP request.
7. A returned response is represented durably by its identity digest before JSON result processing.
8. Any exception after `INVOCATION_IN_FLIGHT` becomes `PROVIDER_OUTCOME_UNKNOWN_REPLAY_PROHIBITED`.

## Credential boundary

`SymfonyAiDelegateMissionCognitionGateway` may not receive `PlatformInterface`, an API key, an environment-variable name, a Clavium credential reference, or a credential capability. It receives only a `DelegateProviderInvoker`.

The production invoker creates an ephemeral Symfony Generic platform inside `CredentialBroker::consume()`. The secret is never returned, persisted, logged, serialized, placed in an exception, or passed to the cognition gateway.

## Recovery semantics

- absence of an invocation journal means external I/O did not begin through this adapter;
- `INVOCATION_IN_FLIGHT` after process death is an unknown outcome;
- `PROVIDER_OUTCOME_UNKNOWN_REPLAY_PROHIBITED` cannot be automatically replayed;
- `PROVIDER_RESPONSE_IDENTITY_SEALED_PENDING_RESULT_PROCESSING` proves response receipt without persisting response content in the journal; and
- an existing journal blocks a second provider start for the same claim.

## Exclusions

This transition grants no tool use, perimeter crossing, external action, execution, continuing turn, redeployment, or reuse authority. Other resident cognition gateways remain outside this Delegate-only migration and must be migrated separately before system-wide credential custody can be claimed.
