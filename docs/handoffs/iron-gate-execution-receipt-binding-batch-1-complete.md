# Iron Gate Execution Authority and Receipt Binding Batch 1 complete

## Result

Batch 1 defines separately versioned declarative contracts for one deterministic execution claim
and its receipt binding:

- `DeterministicExecutionClaimContract`;
- `DeterministicReceiptBindingContract`; and
- `docs/iron-gate-execution-receipt-binding-contract.md`.

The contracts bind native source authorization, competent actor and runtime principal, exact
operation/destination/payload, credential-capability identity without secret material, one durable
winner identity, provider-safety prerequisite, effect checkpoint, truthful outcome, sealed raw
receipt, Lazaretto admission and forward-only recovery.

They grant no authority and perform no transition. No issuer or consumer was migrated. No external
I/O, credential resolution, provider invocation, effect, raw-receipt persistence or Lazaretto
admission occurred.

## Next separately bounded batch

Only Batch 2 may next be considered: prove the provider-safety prerequisite for one exact
deterministic operation and identify its native source authorization before migrating any consumer.
Batch 2 is not authorized by this handoff and requires an explicit continuation instruction.

AgentMail remains only a candidate until its idempotency or unknown-outcome contract is proved.
Sortie, Oracle, inbound Lazaretto, credential-platform, revocation, propagation, telemetry,
reassessment, containment and incident boundaries remain closed.
