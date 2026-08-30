# Next campaign: Provider Execution Effect Readiness

## Status

`CAMPAIGN_SELECTED_PREPARATION_BATCH_0_ONLY`

Provider Execution Boundary Redesign and its Activation-Consumption Remediation
are complete pre-provider only. Their corrected v2 corridor proves one
same-root combined activation, authority-consumption and effect-start winner,
stationary credential resolution, exact read-only replay, revocation
exclusivity, expiry refusal and durable secret exclusion. It does not authorize
a provider effect.

Provider Execution Effect Readiness is selected to determine whether the four
remaining stop conditions can be joined lawfully, and in what order:

1. the executor principal is attested but inert;
2. the provider binding is bound but inactive;
3. no live-call runtime contract joins an admitted request to provider
   invocation; and
4. provider-contract and provider-outcome assurance gaps remain open.

Only Preparation Batch 0 is authorized. Refusal, deferral, or division into
separate remediation campaigns are valid results.

## Preparation questions

Preparation must inventory and classify:

1. the competent authority, exact transition and lifecycle evidence required to
   activate the executor principal;
2. the competent authority, exact scope and revocation posture required to
   activate one provider binding for one operation;
3. the smallest live-call contract that could join the combined v2 winner to
   credential resolution and provider invocation without granting authority;
4. the exact provider-contract evidence required for AgentMail direct send,
   including organization scope, key syntax, request equivalence, duplicate
   behavior and completion-anchored retention;
5. the continuing posture for in-progress duplicates, disconnects, timeouts and
   unknown outcomes;
6. whether principal activation, binding activation, contract definition and
   assurance admission must be separate campaigns or separately consumed
   authorities;
7. the pre-first-byte ordering and the last lawful refusal point;
8. crash recovery, contention, expiry, revocation and reconstruction across the
   proposed joining boundary;
9. secret exclusion from records, logs, exceptions, test evidence and
   reconstruction;
10. the exact live consumer, transport and command surfaces that remain
    unmigrated;
11. which facts are evidence, which are authority, which are process-local
    mechanisms, and which remain non-authorities; and
12. whether any missing provider fact requires a sterile conformance campaign
    before effect readiness can proceed.

Every requirement must be classified as `EXISTS_CANONICALLY`,
`EXISTS_FRAGMENTED`, `ABSENT`, or `DEFERRED_BOUNDARY`, with its producer,
consumer, trust boundary, crash posture and non-authorities.

## Preparation output

Preparation Batch 0 must produce:

- a stop-condition inventory;
- an authority and ordering matrix;
- a crash/replay/unknown-outcome matrix;
- candidate campaign boundaries and their non-authorities;
- the smallest lawful sequence, if one exists; and
- a Batch 1 gate or an explicit refusal.

Preparation may not define runtime contracts, change runtime behavior, activate
a principal or binding, admit provider-contract authority, issue or consume
execution authority, resolve or handle a credential or capability, invoke a
provider, perform external I/O, send an outbound byte, authorize retry, migrate
a live consumer or command, or open Iron Gate or Lazaretto.

`UNKNOWN_REPLAY_PROHIBITED` remains binding after any possible provider
effect-start unless separately admitted evidence resolves the outcome.
