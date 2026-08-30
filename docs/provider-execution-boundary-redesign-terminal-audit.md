# Provider Execution Boundary Redesign — Batch 10 terminal audit

## Result

`BATCH_10_TERMINAL_AUDIT_REFUSED_ACTIVATION_NOT_CONSUMED`

The campaign is not terminally closed. The audit confirms the redesign corrected credential custody,
defined the exact same-process boundary and principal, issued durable execution authority, committed
an admission winner before credential resolution, proved stationary secret exclusion, and resumed
Provider Execution Assurance at the pre-provider boundary.

It also finds one authoritative defect: the artifact named as a single-operation provider-binding
activation is never consumed as a single-operation winner.

## Exact defect

Batch 4 issues a `SingleOperationProviderBindingActivationContract` record with:

- `status: ACTIVATED_UNCONSUMED`;
- `single_operation: true`; and
- an exact boundary, principal, binding, request, tool, effect, provider, adapter and expiry.

Batch 5 validates that activation while issuing durable execution authority. Its atomic transition
consumes only the authority-issuance permission. It does not consume the activation artifact.

Batch 6 validates the same `ACTIVATED_UNCONSUMED` activation while admitting durable execution
authority. Its winner records only execution-authority consumption. It does not consume the
activation artifact or bind an activation-consumption winner into the admission.

The activation therefore remains durably `ACTIVATED_UNCONSUMED` after one authority is issued and
after one admission wins.

## Why this blocks terminal closure

The activation is declared single-operation, but the authoritative root has no activation-keyed
consumption record. Separately authorized durable execution authorities can reference the same intact
activation artifact. Each authority can have its own admission lock and winner. Authority-level
single use therefore does not prove activation-level single use.

Exact request equality narrows what can be repeated; it does not make repeated use impossible.
Competent issuance decisions also do not substitute for atomic consumption of the artifact whose
contract claims one operation.

This is not a speculative distributed threat. It exists within the declared one-root
`TRUSTED_WRITER_CANONICAL_INTEGRITY` posture.

## Evidence that remains valid

The following results remain valid and are not rolled back:

1. credential possession is separate from execution authority;
2. the selected credential-owning boundary is same-process and stationary;
3. executor-principal identity, generation and competence are explicit;
4. provider, adapter, credential family, tool, effect, request and destination lineage are exact;
5. durable execution authority is single-use at its own identity;
6. authority admission and local effect-start precede credential resolution and I/O;
7. credential resolution is callback-local and secret-free in durable state;
8. replay, crash, contention, expiry, revocation and corrupt reconstruction fail closed for the
   tested pre-provider corridor;
9. `REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE` remains scoped to actual cross-process transfer;
10. provider execution remains refused; and
11. `UNKNOWN_REPLAY_PROHIBITED` remains mandatory after any future provider-effect start.

The defect prevents a terminal claim about single-operation binding activation. It does not erase
these narrower proofs.

## Required remediation boundary

A future separately authorized remediation must:

1. define the activation-consumption winner identity and its reference in governed admission;
2. atomically arbitrate the exact activation and durable execution authority under one authoritative
   transition or a proven lock order;
3. make same-activation competing authorities converge or refuse before credential resolution;
4. preserve exact replay of the one winner;
5. refuse expired or revoked activation before a first winner;
6. reconstruct activation consumption without reactivation;
7. prove crash cuts before and after the combined winner;
8. preserve secret exclusion and zero provider effects; and
9. repeat terminal audit.

No choice between a combined lock and an explicit ordered pair is made by this audit.

## Non-authorities and closed perimeter

This audit is evidence only. It defines no remediation runtime contract and changes no runtime
behavior. It does not activate or consume the activation, activate a principal or binding, issue or
consume authority, handle a credential or capability, invoke a provider, perform external I/O, send
an outbound byte, authorize retry, migrate a live command, open Iron Gate or Lazaretto, or claim a
provider outcome.

No live adoption may proceed from this result.
