# Next campaign: Provider Execution Boundary Redesign

## Status

`CAMPAIGN_SELECTED_PREPARATION_BATCH_0_ONLY`

Provider Execution Assurance reconsideration ended correctly with no Batch 1 because
`REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE` remained authoritative. The subsequent Blackquill review
found that the refusal exposes a category error: the design treated a process-local capability
object as the durable authority and required its identity to survive process death.

Provider Execution Boundary Redesign is selected to test a corrected architecture in which provider
credentials remain inside one credential-owning execution boundary while exact durable execution
authority is validated and consumed there.

Only Preparation Batch 0 is authorized. Missing evidence, an incoherent boundary or permanent
refusal are valid results.

## Preparation questions

Preparation must answer:

1. Which deployment boundary owns provider credential access?
2. Which exact process principal may enter that boundary?
3. What durable artifact represents execution authority without carrying credential material?
4. Which actor competently issues that authority, and which exact executor consumes it?
5. How are tool authority, effect authorization, provider binding, request identity, destination,
   payload, assurance profile, expiry and principal bound together?
6. Can authority consumption and effect-start be ordered atomically before credential resolution or
   the first outbound byte?
7. Which crash cuts permit continuation, read-only reconstruction or permanent replay prohibition?
8. How do replay, contention, expiry and revocation fail closed?
9. Does a same-process governed executor satisfy the declared one-root trusted-writer threat model?
10. What additional threats would justify a local broker or external custodian?
11. How are credentials excluded from records, logs, exceptions and reconstruction?
12. Which existing records are evidence only and may not be reinterpreted as execution authority?

## Required classifications

Every requirement must be classified as `EXISTS_CANONICALLY`, `EXISTS_FRAGMENTED`, `ABSENT` or
`DEFERRED_BOUNDARY`, with its exact producer, consumer, trust boundary, crash posture and
non-authorities. Preparation must distinguish:

- credential possession from execution authority;
- durable authority identity from process-local capability identity;
- binding selection from binding activation;
- authority consumption from credential resolution;
- local effect-start truth from provider outcome truth;
- same-process execution from cross-process credential transfer;
- same-root contention from distributed execution;
- trusted-writer integrity from hostile-writer non-forgeability; and
- read-only reconstruction from credential or authority reconstruction.

## Candidate sequence to propose

Preparation may propose, but does not authorize:

1. boundary and authority-empty contract definition;
2. exact executor-principal and binding-activation authority;
3. atomic authority consumption and effect-start admission;
4. process-local credential resolution inside the winning execution boundary;
5. crash, replay, contention, expiry, revocation and secret-exclusion proof;
6. Provider Execution Assurance resumption against the redesigned boundary; and
7. terminal audit before any live-consumer adoption.

## Closed perimeter

Preparation Batch 0 may not define runtime contracts; change runtime behavior; activate a principal
or provider binding; issue, consume or reconstruct authority; issue, transfer, resolve or reconstruct
a credential capability; read a credential; invoke a provider; perform external I/O; migrate a live
command; or open Iron Gate or Lazaretto. It may not claim hostile-writer, multi-host, distributed
custody or split-brain guarantees.


## Terminal closure and successor selection

Provider Execution Boundary Redesign is complete pre-provider only at
`PROVIDER_EXECUTION_BOUNDARY_REDESIGN_COMPLETE_PRE_PROVIDER_ONLY`.
Activation-Consumption Remediation closed the Batch 10 defect at
`BATCH_7_ADVERSARIAL_PROOF_COMPLETE_TERMINAL_AUDIT_PASSED` while preserving
the earlier refusal marker as history.

No provider effect is authorized. The executor principal remains inert, the
provider binding remains inactive, no live-call contract exists, provider
assurance gaps remain open, and `UNKNOWN_REPLAY_PROHIBITED` remains binding.

The next separately selected campaign is Provider Execution Effect Readiness,
Preparation Batch 0 only. Its campaign definition and new-chat handoff are:

- `docs/next-campaign-provider-execution-effect-readiness.md`;
- `docs/handoffs/provider-execution-effect-readiness-campaign-ready.md`; and
- `docs/deferred-local-test-ledger.md` for the still-pending Batch 7 local run.
