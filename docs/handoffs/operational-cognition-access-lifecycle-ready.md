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

The operational route is not yet mediated. `BoundedExecutionAuthorizationService` explicitly denies credential and network authority, while `SymfonyAiOperationalExecutionCognitionGateway` still receives a directly configured Symfony agent. That mismatch is the first target. Thirty-one other main-runtime agents plus the separately configured sortie agent, and two `%env(DEEPSEEK_API_KEY)%` platforms, remain later migration work, so no intermediate batch may claim the system-wide bypass gate closed.

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
4. **Broker — implemented:** migrate the operational gateway to a claim-bound broker that resolves the credential inside its callback and constructs a provider adapter for that call only. Remove the operational agent definition from the directly invokable platform.
5. **Proof — implemented:** test authorization refusal; missing, malformed, mismatched, expired, consumed, and superseded decisions and leases; exact replay; divergent concurrency; interruption around claim/I/O boundaries; unknown outcome; and absence of secrets from persistence, exceptions, logs, and serialized output.
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

## Batch 4 implementation checkpoint

`OperationalClaimBoundCredentialBroker` and the rewritten operational gateway remove the operational Manifestation's direct Symfony-agent path.

- The broker locates exactly one durable claim through the exact bounded-execution authorization identity/digest and cognition-request lineage, then rereads and validates the intact claim before capability issuance.
- It requires both single-use consumptions, live lease expiry, strict DeepSeek provider/model binding, durable pre-I/O state, stable idempotency identity, no prior credential resolution or network use, and no automatic replay authority.
- The credential exists only inside the broker consumption callback. That callback invokes the strict per-call DeepSeek adapter, starts the existing provider-invocation journal, seals the provider response envelope and response identity, and suppresses provider diagnostics on an unknown outcome.
- The operational agent definition and direct `AgentInterface` injection are removed. The Manifestation receives only the parsed bounded result.
- The operational claim now carries the source lease expiry required for authoritative broker validation. The shared journal and response-envelope services accept both Delegate and operational claim families without weakening either claim contract.
- This closes the direct-agent bypass for the operational route only. Other platform-bound agents and the shared environment-backed platform remain, so the system-wide credential-boundary gate remains open.

Focused verification:

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/SymfonyAiOperationalExecutionCognitionGatewayTest.php
```

## Batch 5 implementation checkpoint

The operational boundary proof is complete across the focused Batch 1–5 suites.

- Request/decision tests prove exact replay, independent Imperator authorization/refusal, expiry, model mismatch, and refusal without lease authority.
- Lease tests prove refused, expired, mismatched, unauthorized, and credential-capable issuance attempts fail stopped; the valid lease remains opaque and exact-replay safe.
- Claim tests prove expired, consumed, substituted, divergent, partial, and process-contention cases fail stopped or converge on one durable claim.
- Gateway proof now covers missing, malformed, mismatched, expired, consumed, and superseded claims before credential issuance; exact invocation replay; interruption after pre-I/O reservation; credential failure; unknown provider outcome; response sealing; direct-agent configuration absence; and recursive persisted-secret exclusion.
- The proof exposed and closed one ordering weakness: the gateway now atomically reserves the invocation journal before broker consumption. Exact replay or a concurrent/interrupted reservation is therefore rejected before a second credential can be issued or resolved. External I/O is marked started only inside the broker callback immediately before adapter invocation.
- Pre-I/O and unknown-outcome diagnostics remain suppressed, and neither permits automatic replay.

Focused verification:

```bash
php vendor/bin/phpunit \
  tests/Imperium/Runtime/OperationalCognitionAccessRequestDecisionTest.php \
  tests/Imperium/Runtime/OperationalCognitionLeaseServiceTest.php \
  tests/Imperium/Runtime/OperationalCognitionInvocationClaimServiceTest.php \
  tests/Imperium/Runtime/SymfonyAiOperationalExecutionCognitionGatewayTest.php
```

## Batch 6 implementation checkpoint

The remaining surface is sealed in `docs/credential-boundary-agent-inventory.json` and `docs/handoffs/credential-boundary-batch-6-inventory-complete.md`. It contains 31 main-runtime definitions plus the separately configured La Cortine sortie agent: 32 remaining agents across nine clusters and two environment-backed platform definitions. An automated inventory test requires every configured definition and declared injection to remain classified exactly once.

## Batch 7 implementation checkpoint

The common internal-governance substrate is implemented through its durable pre-I/O claim. It requires exactly one cluster-specific resolver to reread and normalize a native authority, then preserves independent request, Imperator decision/refusal, opaque Clavium lease, and atomic lease-plus-authority claim boundaries. No gateway or agent definition moved; the inventory remains 32.

## Batch 8 implementation checkpoint

Foundry specification, revision, ordinary review, and adversarial review now use exact native authority resolution and the common governance claim-bound broker path. The two direct Foundry agent definitions and all four direct injections are removed; the executable inventory is 30.

## Batch 9 implementation checkpoint

Hagiography and Studium resident subordinate-requirements cognition now use office-specific native authority resolution and the shared governance claim-bound broker path. The resident `sanctographer` and `chancellor` definitions and injections are removed; the executable inventory is 28.

## Batch 10 implementation checkpoint

Hagiography and Studium section authorship now use exact acceptance/commission/specification/case authority resolution and the shared governance claim-bound broker path. Both section-authorship definitions and injections are removed; the executable inventory is 26.

## Batch 11 implementation checkpoint

Ordinary and Delegate-mission Profile elaboration now use exact Laboratorium authority resolution and the shared governance claim-bound broker path. The direct Alchemist agent and transient caller are removed; the executable inventory is 25.

The next batch is **Batch 12: Senate Profile-examination migration**, covering its nine direct agents only.

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
> Begin credential-boundary remediation Batch 13: Senate Persona-confirmation migration. Starting from the merged Batch 12 Senate Profile-examination path, migrate only the ten `senator_*`, `persona_witness`, `senator_finding_*`, and `lord_speaker_disposition` definitions. Add exact native authority resolution for each question, witness-answer, jurisdictional finding, and disposition stage, reuse the shared governance claim-bound broker path, preserve all four jurisdictions, prove cross-jurisdiction/stage refusal and replay behavior, remove those definitions and injections, then update the inventory from 16 to 6. Do not migrate another cluster or claim the system-wide gate closed. I will run local PHP commands.

Batch 13A exposed and repaired the first chained-call ordering defect: the first Practice question is now sealed durably and stops pending separately authorized testimony cognition. Continue with Batch 13B from `docs/handoffs/credential-boundary-batch-13a-first-persona-question-seam-complete.md`; do not restore immediate question-to-witness execution.

Batch 13B routes the exact sealed Practice question through the governance claim-bound witness path and removes the direct `persona_witness` definition. Continue with Batch 13C from `docs/handoffs/credential-boundary-batch-13b-first-persona-testimony-complete.md`; inventory is 15 and the remaining Persona-confirmation questions must preserve the same durable pause.

Batch 13C seals only the Governance baseline question because later jurisdictions depend on testimony that does not yet exist. Continue with Batch 13D from `docs/handoffs/credential-boundary-batch-13c-governance-baseline-question-seam-complete.md`; do not reconstruct the former three-jurisdiction cognition loop.
>
> Ad Imperium. Not one step back.
