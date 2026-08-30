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


## Preparation Batch 0 result

Preparation Batch 0 is complete at
`PREPARATION_BATCH_0_COMPLETE_EFFECT_GATES_SEPARABLE_ASSURANCE_FIRST` in
`docs/provider-execution-effect-readiness-preparation-inventory.md`.

The inert principal, inactive implementation binding, absent live-call
contract and incomplete provider assurance remain separate authoritative stop
conditions. They may not be collapsed into one activation-and-call action.

Only Batch 1 may next be considered: authority-empty Provider Assurance
Evidence Admission contracts for exact AgentMail direct send. Batch 1 grants no
provider, activation, credential, execution, retry, external-I/O, live-adoption,
Iron Gate or Lazaretto authority.

The active handoff is
`docs/handoffs/provider-execution-effect-readiness-preparation-batch-0-complete.md`.


## Batch 1 result

Batch 1 is complete at
`BATCH_1_AUTHORITY_EMPTY_PROVIDER_ASSURANCE_CONTRACTS_COMPLETE`.

Three separately versioned contracts now define evidence-source provenance,
the exact AgentMail direct-send assurance profile and a future evidence
admission result. They have no producer, validator, fixture, immutable admitted
record or runtime consumer and grant no authority.

Only Batch 2 may next be considered: pure fail-closed validators and immutable
caller-supplied offline fixture stores for those contracts. The active handoff
is `docs/handoffs/provider-execution-effect-readiness-batch-1-complete.md`.


## Batch 2 result

Batch 2 is complete at
`BATCH_2_FAIL_CLOSED_ASSURANCE_FIXTURE_VALIDATION_COMPLETE`.

Pure validators and immutable caller-supplied offline fixture stores enforce
the three Batch 1 schemas without fetching provider evidence or creating live
provider truth or authority.

Only Batch 3 may next be considered: offline interruption, exact replay,
changed-evidence conflict and same-root contention proof for all three fixture
paths. The active handoff is
`docs/handoffs/provider-execution-effect-readiness-batch-2-complete.md`.
