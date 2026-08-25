# Delegate provider-turn forward-recovery contract

## Purpose

Recover a missing bounded cognition-turn record from an already sealed provider response without credentials, provider access, or another provider invocation.

## Preconditions

- one immutable recovery authorization names the exact invocation claim and response-envelope digests;
- its recovery authority is live, unexpired, exercisable, and single-use;
- the durable claim, journal, response envelope, activation, and commission form one exact digest-bound chain;
- the journal is either in flight with the response envelope already sealed, or response-identity sealed with the same envelope identity; and
- no conflicting turn exists.

## Transition

The recovery service revalidates the exact JSON cognition contract, consumes the recovery authority through `AuthorityConsumptionStore`, and writes the deterministic bounded-turn identity through `ImmutableRecordStore`.

An exact replay returns the existing turn. Changed evidence, payload, lineage, authorization, or authority consumption fails stopped.

## Absolute exclusions

The recovery service has no provider adapter, credential broker, credential reference, provider credential, or provider invocation method. Recovery grants no new cognition, tool, perimeter, external-action, execution, continuation, redeployment, or reuse authority.
