# Provider Execution Assurance campaign ready

## Completed foundation

Iron Gate Execution Authority and Receipt Binding is terminal through Batch 11. Iron Gate Evidence
Authenticity Remediation is also terminal through Batch 11 under
`docs/handoffs/iron-gate-evidence-authenticity-remediation-campaign-complete.md`.

The local deterministic corridor binds exact caller authority, effect checkpoints, callback-produced
response evidence, raw receipt, Lazaretto admission and read-only reconstruction. Its terminal audit
preserves four limits: provider-side idempotency/deduplication, remote cryptographic authorship,
hostile-writer non-forgeability and multi-host atomicity are not proved.

## Selected campaign

The next campaign is Provider Execution Assurance. Only Preparation Batch 0 is authorized.

Read, at minimum:

1. `docs/next-campaign-provider-execution-assurance.md`;
2. `docs/iron-gate-evidence-authenticity-remediation-terminal-audit.md`;
3. `docs/iron-gate-evidence-authenticity-adversarial-proof.md`;
4. `docs/handoffs/iron-gate-evidence-authenticity-remediation-campaign-complete.md`;
5. `docs/runtime/agentmail-email-transport.md`;
6. `docs/iron-gate-agentmail-idempotent-send-assessment.md`;
7. `docs/iron-gate-journal-bound-agentmail-invocation.md`;
8. every deterministic provider, credential, effect-start, response-envelope, raw-result,
   Lazaretto and reconstruction runtime file and associated test.

Preparation must inventory provider request identity, idempotency collision domain and retention,
duplicate behavior, effect-start, timeout/disconnect unknown outcomes, query-before-retry support,
response correlation/authorship, durable registration, recovery, concurrency, tamper and secret
exclusion. Classify every requirement and propose the smallest safe migration sequence.

## Preserved perimeter

Do not perform external I/O, invoke AgentMail, resolve a live credential, open Iron Gate or Lazaretto,
migrate a command/transport, assess sortie, or open credential-platform, revocation, propagation,
telemetry, reassessment, containment or incident behavior. Hostile-writer hardening and distributed
persistence remain separate deferred campaigns.

## New-chat continuation

> Continue Imperium from `main` after the merged Provider Execution Assurance campaign-selection
> commit.
>
> Read `docs/handoffs/provider-execution-assurance-campaign-ready.md`,
> `docs/next-campaign-provider-execution-assurance.md`,
> `docs/iron-gate-evidence-authenticity-remediation-terminal-audit.md`,
> `docs/iron-gate-evidence-authenticity-adversarial-proof.md`,
> `docs/runtime/agentmail-email-transport.md`,
> `docs/iron-gate-agentmail-idempotent-send-assessment.md`,
> `docs/iron-gate-journal-bound-agentmail-invocation.md`, every deterministic provider, credential,
> effect-start, response-envelope, raw-result, Lazaretto and reconstruction runtime file, and all
> associated tests.
>
> Begin Provider Execution Assurance Preparation Batch 0 only. Inventory exact provider request
> identity, idempotency collision domain and retention, duplicate response behavior, effect-start,
> timeout/disconnect unknown outcomes, query-before-retry support, response correlation and remote
> authorship evidence, durable registration, recovery, concurrency, tamper and secret exclusion.
> Classify every requirement as `EXISTS_CANONICALLY`, `EXISTS_FRAGMENTED`, `ABSENT`, or
> `DEFERRED_BOUNDARY`; assign exact consumer postures; and propose the smallest safe sequence.
>
> Do not change runtime behavior, perform external I/O, invoke AgentMail, expose credentials, migrate
> live consumers, or open Iron Gate, Lazaretto, sortie, credential-platform, revocation, propagation,
> telemetry, reassessment, containment or incident boundaries.

