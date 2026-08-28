# Next campaign: Provider Execution Assurance

## Status

`CAMPAIGN_SELECTED_PREPARATION_BATCH_0_ONLY`

Iron Gate Evidence Authenticity Remediation is terminal through Batch 11. Its local deterministic
evidence corridor is coherent and caller-authority bound, but its terminal audit deliberately leaves
provider-side idempotency, remote response authorship and distributed execution assumptions
unproved. Provider Execution Assurance is selected to examine those exact external facts before any
live deterministic consumer adoption can be considered.

Only Preparation Batch 0 is authorized. Preparation is inventory and classification only. It may
read contracts, adapters, provider documentation already admitted to the repository, transport
surfaces and associated tests. It may not call AgentMail or any provider, expose a credential,
change runtime behavior or migrate a live consumer.

## Preparation questions

Every provider operation considered must answer:

1. What exact request identity does the provider recognize for deduplication?
2. What is the collision domain and retention interval of that identity?
3. What does the provider return for an exact duplicate before, during and after completion?
4. Which timeout/disconnect states are replayable, queryable or permanently unknown?
5. What provider operation or receipt identity correlates the response to the authorized request?
6. Does any signature, authenticated channel property or provider lookup support remote authorship?
7. Which request fields are immutable across a retry, and which provider-generated fields may vary?
8. Where is the effect-start point relative to credential use and network transmission?
9. What durable record must exist before the first byte may leave the authoritative root?
10. What recovery is valid after admission, credential attempt, send start and response observation?
11. Which facts are provider-contract evidence, which are locally enforced, and which remain trust?
12. What secret-exclusion proof covers requests, logs, receipts, exceptions and reconstruction?

## Required classifications

Each requirement must be classified as `EXISTS_CANONICALLY`, `EXISTS_FRAGMENTED`, `ABSENT`, or
`DEFERRED_BOUNDARY`, with an exact consumer posture and cited evidence. Preparation must distinguish:

- local request/idempotency binding from provider-side deduplication;
- callback lineage from remote authorship;
- provider-declared guarantees from observed behavior;
- safe retry from query-before-retry and permanent replay prohibition;
- one-root crash recovery from distributed delivery semantics;
- credential capability use from provider execution authority.

## Smallest safe sequence to propose

Preparation Batch 0 must propose, not authorize, the smallest safe sequence. Expected candidates are:

1. provider-contract evidence admission;
2. durable idempotency registration and collision refusal;
3. exact request/response correlation contract;
4. unknown-outcome query or permanent replay-prohibition rule;
5. sterile provider conformance harness, if evidence cannot be established without observation;
6. adversarial fault, duplication, timeout, tamper and secret-exclusion proof;
7. terminal audit before any separate live-consumer adoption campaign.

The inventory may prove that a provider or operation is not safely adoptable. Refusal is a valid
campaign result.

## Closed boundaries

Preparation Batch 0 opens no network I/O, Iron Gate, Lazaretto, sortie, credential-platform,
revocation, propagation, telemetry, reassessment, containment, incident or live-adoption behavior.
It may not redesign hostile-writer hardening or distributed persistence, which remain separate
campaigns. It may not reopen completed evidence-authenticity or receipt-binding campaigns.

