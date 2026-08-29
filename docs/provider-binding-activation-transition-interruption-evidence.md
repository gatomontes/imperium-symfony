# Provider Binding Activation transition interruption evidence

## Status

`BATCH_2_OFFLINE_INTERRUPTION_DEMONSTRATIONS_COMPLETE`

The offline demonstration exercises both exact activation transition names through the canonical
caller-authority consumer and immutable target store on isolated temporary roots. Synthetic
principals and authorities are marked offline-only and confer no runtime authority.

For each decision and issuance transition it observes three cuts:

- before authority consumption;
- after consumption but before target commit; and
- after target commit.

Restart with the same consumer converges on the same durable consumption. Exact target replay
converges, changed target replay refuses, another consumer loses contention, and an expired
authority refuses before target creation. Six sealed
`imperium.imperator.activation-transition-interruption-evidence/v1` records are produced together
with private and sanitized demonstration files.

## Limits

The harness proves the shared caller-consumption and immutable-target ordering used by the Batch 2
transitions. It does not create an operational activation decision or authority, install an
activation-capable principal, exercise credential behavior, or prove recovery from arbitrary host
or filesystem failure. It grants no retry authority. Production adoption remains subject to the
stranded-artifact disposition and terminal custody refusal.

No credential reference or secret is read or persisted. No provider is invoked, no external I/O
occurs, and Iron Gate and Lazaretto remain closed. Provider Execution Assurance remains paused.
