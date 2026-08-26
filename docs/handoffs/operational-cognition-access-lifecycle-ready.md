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

1. **Request and decision — implemented:** introduce the Curia request and independent Imperator authorize/refuse service with exact replay and conflict rejection.
2. **Lease — implemented:** have Clavium validate the current authorized decision and issue one opaque, exact, expiring lease without resolving credentials.
3. **Claim — implemented:** atomically consume the lease and cognition authority into one durable invocation claim before any external I/O.
4. **Broker:** migrate the operational gateway to a claim-bound broker that resolves the credential inside its callback and constructs a provider adapter for that call only. Remove the operational agent definition from the directly invokable platform.
5. **Proof:** test authorization refusal; missing, malformed, mismatched, expired, consumed, and superseded decisions and leases; exact replay; divergent concurrency; interruption around claim/I/O boundaries; unknown outcome; and absence of secrets from persistence, exceptions, logs, and serialized output.
6. **Remaining clusters:** migrate every other direct platform-bound agent by governance cluster, remove the global environment-backed credential platform, then implement the final repeatable bypass demonstration and sanitized summary.

Each batch must stop at its named boundary, add focused service tests, preserve existing public behavior where authority permits it, and update this handoff plus `docs/credential-boundary-remediation.md`. The user will run local PHP commands.

## Batch 1 implementation checkpoint

Batch 1 is implemented on branch `codex/operational-cognition-access-batch-1` after `70e4dcd`.

- `OperationalCognitionRequestService` seals one exact Curia request from the existing bounded-execution authorization. It binds the Manifestation, Seat/binding, custody lineage, input digest, Profile/model requirements digest, iteration `1`, stop conditions, request time, and strict expiry.
- The request carries one unconsumed, single-use cognition authority and explicitly carries no credential-use, network-access, provider-invocation, or execution-continuation authority.
- `OperationalProviderResourceDecisionService` independently records Imperator `AUTHORIZED` or `REFUSED` against that exact request. It binds the current strict DeepSeek provider/model contract, normalized model configuration, integer token/cost ceiling, rationale, attribution, decision time, and expiry.
- Authorization opens only one exact, single-use Clavium lease-activation authority. Refusal opens none. Neither branch issues a lease or grants provider invocation.
- Both services return the exact prior record on identical replay and reject divergent reuse of the same source authority/request.

Focused verification:

```bash
php bin/phpunit tests/Imperium/Runtime/OperationalCognitionAccessRequestDecisionTest.php
```

Batch 2 follows this checkpoint and implements the opaque Clavium lease without resolving or disclosing any credential.

## Batch 2 implementation checkpoint

`OperationalCognitionLeaseService` implements the Clavium lease boundary after Batch 1.

- It rereads and validates the exact intact, unexpired `AUTHORIZED` Imperator decision and its digest-bound Curia cognition request.
- It requires an exact active Locksmith occupancy with operational cognition lease-issuance authority and without credential-disclosure or execution authority.
- It consumes the exact lease-activation authority into one immutable lease record while leaving the lease itself unconsumed.
- The opaque lease binds the request and decision identities/digests, target Manifestation and Seat/binding/custody lineage, provider, model, normalized configuration, resource ceiling, input digest, Profile/model requirements digest, iteration, issuer, issue time, and strict expiry.
- It persists no credential reference, credential material, secret-bearing endpoint, transferable network authority, or provider-invocation authority.
- Identical replay returns the same lease. Divergent reuse, refusal, expiry, lineage mismatch, competing decision, or unauthorized Locksmith fails stopped.

Focused verification:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/OperationalCognitionLeaseServiceTest.php
```

Batch 3 follows this checkpoint and implements the durable pre-I/O invocation claim.

## Batch 3 implementation checkpoint

`OperationalCognitionInvocationClaimService` implements the sole pre-I/O consumption boundary.

- Batch 1 now gives the single-use cognition authority an explicit deterministic identity.
- The claim transition locks the cognition-authority identity and lease identity in a fixed nested order, rereads the complete request/decision/lease chain, validates all digests, expiry, target, provider/model/configuration, resource, input, Profile/model requirement, iteration, and unconsumed-state bindings, then persists both consumptions in one immutable claim write.
- The claim carries one stable provider idempotency identity and explicit durable pre-I/O state: external I/O has not started, no provider was invoked, no credential was resolved, no network access occurred, and automatic replay is prohibited after an unknown outcome.
- Exact replay returns the same claim independent of retry time. Divergent reuse, substitution, expiry, prior consumption, tampered stored claims, or partial/crossed consumption fails stopped.
- Process-level contention coverage proves two workers converge on one claim.

Focused verification:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/OperationalCognitionInvocationClaimServiceTest.php
```

The next batch is **Batch 4: claim-bound operational broker**. It must replace the operational gateway's directly injected Symfony agent, reread this exact durable claim, resolve the credential only inside the broker callback, construct a per-call DeepSeek adapter, and return only the bounded output. Unknown-outcome and response-envelope behavior must reuse the existing governed recovery contracts.

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
> Begin Operational Cognition Access Batch 4: claim-bound operational broker. Replace the operational Manifestation gateway's directly injected Symfony agent with a broker that rereads the exact Batch 3 durable claim, resolves the credential only inside its consumption callback, constructs the strict DeepSeek adapter for that call only, and returns only the bounded output. Preserve the stable idempotency identity, response envelope, failure journal, pre-I/O failure, unknown-outcome prohibition, and governed recovery contracts. Remove the operational agent from the directly invokable platform. Add focused broker, configuration, failure, replay, and secret-exclusion tests. Do not claim the system-wide bypass gate closed while other direct agents remain. Do not invent Delegate Mission Step 70 or Runtime Integrity Hardening Step 36. I will run local PHP commands.
>
> Ad Imperium. Not one step back.
