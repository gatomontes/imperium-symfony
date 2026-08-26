# Operational Cognition Access lifecycle ready

## Exact sequence

1. Curia authorizes one bounded internal execution iteration.
2. Imperator separately authorizes or refuses the exact provider/model resource expenditure.
3. Clavium validates that decision and issues one opaque, expiring lease.
4. A durable invocation claim consumes that lease and the cognition authority atomically.
5. The broker constructs the provider adapter for that call only.
6. The Manifestation receives output, never credentials or network authority.

This is the separately bounded **Operational Cognition Access lifecycle**. It does not reopen the terminal Delegate, create Delegate Mission Step 70, create Runtime Integrity Hardening Step 36, or grant a continuing turn. The severe-source cleanup gate and four-demonstration crash-evidence program remain closed.

## Start state

Start from `main` at or after merged commit `38752404772f4f3f58eb346d77f5887701fcb6c0`.

Read before changing code:

1. `docs/delegate-mission-flow.md`;
2. `docs/credential-boundary-remediation.md`;
3. `docs/handoffs/runtime-integrity-hardening-leg-complete.md`;
4. `docs/handoffs/runtime-severe-source-cleanup-closed.md`;
5. `docs/handoffs/crash-demonstration-program-complete.md`; and
6. `todo/blackquill-todos.md`.

The Delegate route is terminal through Step 69. Runtime Integrity Hardening is complete through Step 35. The Citadel Legate cognition route already uses the claim-bound Clavium broker. Reuse its broker, durable invocation claim, idempotency identity, provider-response envelope, failure journal, credential resolver, adapter factory, clocks, stores, and tests where their contracts fit.

The operational route is not yet mediated. `BoundedExecutionAuthorizationService` explicitly denies credential and network authority, while `SymfonyAiOperationalExecutionCognitionGateway` still receives a directly configured Symfony agent. That mismatch is the first target. Thirty-two other direct platform-bound agents and the shared `%env(DEEPSEEK_API_KEY)%` platform remain later migration work, so no intermediate batch may claim the system-wide bypass gate closed.

## Authority and record contract

Use separate immutable records. Names may follow established repository vocabulary, but their semantic fields and ownership must remain exact.

### Operational cognition request

The Curia record binds:

- schema and record identity;
- exact Manifestation, mission Seat, deployment/custody lineage, and generation;
- exact bounded-execution authorization identity and digest;
- exact input digest, Profile/model requirements digest, iteration number, and stop conditions;
- `cognition_authority=true`, single-use and expiring; and
- `credential_use_authority=false`, `network_access_authority=false`, `provider_invocation_authority=false`, `execution_continuation_authority=false`.

### Imperator provider-resource decision

The decision binds:

- exact cognition request identity and digest;
- exact provider, model, normalized model configuration, and resource ceiling;
- decision status `AUTHORIZED` or a non-authorizing disposition;
- attributable Imperator occupancy, rationale, decision time, and expiry; and
- one single-use Clavium activation authority only when authorized.

Neither Curia nor Clavium may author, infer, widen, or substitute this decision.

### Opaque Clavium lease

The lease binds:

- exact authorized decision and cognition request identities and digests;
- exact Manifestation, provider, model, configuration, input digest, and iteration;
- opaque lease identity, issuer identity, issue time, and strict expiry;
- single-use, unconsumed state; and
- no credential reference, credential material, endpoint secret, or transferable network authority.

### Durable invocation claim

The claim binds:

- exact request, Imperator decision, and Clavium lease identities and digests;
- exact Manifestation, provider/model/configuration, input digest, and iteration;
- one stable provider idempotency identity;
- durable pre-I/O state and claim time; and
- atomic consumption of both the cognition authority and lease.

Exact replay returns the same claim. Any divergent claimant, changed authoritative input, consumed or expired source, or partial consumption fails stopped before provider I/O. An unknown provider outcome remains non-reinvokable and follows the existing sealed-envelope recovery contract.

No private record may persist raw prompts beyond the repository's established input policy, credentials, credential references, environment values, authorization headers, provider endpoints containing secrets, or credential-adjacent diagnostics.

## Implementation batches

1. **Request and decision:** introduce the Curia request and independent Imperator authorize/refuse service with exact replay and conflict rejection.
2. **Lease:** have Clavium validate the current authorized decision and issue one opaque, exact, expiring lease without resolving credentials.
3. **Claim:** atomically consume the lease and cognition authority into one durable invocation claim before any external I/O.
4. **Broker:** migrate the operational gateway to a claim-bound broker that resolves the credential inside its callback and constructs a provider adapter for that call only. Remove the operational agent definition from the directly invokable platform.
5. **Proof:** test authorization refusal; missing, malformed, mismatched, expired, consumed, and superseded decisions and leases; exact replay; divergent concurrency; interruption around claim/I/O boundaries; unknown outcome; and absence of secrets from persistence, exceptions, logs, and serialized output.
6. **Remaining clusters:** migrate every other direct platform-bound agent by governance cluster, remove the global environment-backed credential platform, then implement the final repeatable bypass demonstration and sanitized summary.

Each batch must stop at its named boundary, add focused service tests, preserve existing public behavior where authority permits it, and update this handoff plus `docs/credential-boundary-remediation.md`. The user will run local PHP commands.

## New-chat continuation prompt

Copy this prompt verbatim into the next chat:

> 1. Curia authorizes one bounded internal execution iteration.
> 2. Imperator separately authorizes or refuses the exact provider/model resource expenditure.
> 3. Clavium validates that decision and issues one opaque, expiring lease.
> 4. A durable invocation claim consumes that lease and the cognition authority atomically.
> 5. The broker constructs the provider adapter for that call only.
> 6. The Manifestation receives output, never credentials or network authority.
>
> Continue Imperium from `main` at or after the merge recorded in `docs/handoffs/operational-cognition-access-lifecycle-ready.md`. Read that handoff, `docs/credential-boundary-remediation.md`, `docs/delegate-mission-flow.md`, `docs/handoffs/runtime-integrity-hardening-leg-complete.md`, `docs/handoffs/runtime-severe-source-cleanup-closed.md`, `docs/handoffs/crash-demonstration-program-complete.md`, and `todo/blackquill-todos.md` before changing code.
>
> Begin Operational Cognition Access Batch 1: request and independent Imperator provider-resource decision. Reuse existing authority, immutable-record, replay, clock, occupancy, and canonical-validation primitives. Curia grants neither credentials nor network authority. Imperator alone authorizes or refuses the exact provider/model expenditure. Clavium must not issue a lease in this batch. Add focused tests and documentation; never commit raw private evidence or credential-adjacent material. Do not invent Delegate Mission Step 70 or Runtime Integrity Hardening Step 36. I will run local PHP commands.
>
> Ad Imperium. Not one step back.
