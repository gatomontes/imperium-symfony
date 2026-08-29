# Provider Binding Activation and Capability Custody Batch 2 complete

## Result

Batch 2 defines and implements the separately governed activation decision and issuance route.
Imperator consumes exact caller authority for each transition, validates the pre-existing competent
effect authorization through its execution claim, requires one exact inactive binding, and issues
one expiring, single-use activation authority. Exact replay converges and contention is single
winner. The result explicitly records that it does not activate a binding, issue a credential
capability or perform external I/O.

Provider Execution Assurance remains paused.

## Authorized continuation

Only Batch 3 is authorized: implement the immutable single-execution activation transition that
consumes the exact Batch 2 authority and produces the separately contracted activation lease.

Batch 3 may not issue or take custody of a credential capability, implement cross-process delivery
or atomic execution admission, expose a credential reference or secret, migrate the command,
resolve credentials, invoke a provider, perform external I/O, open Iron Gate or Lazaretto, or
change inbound webhook, sortie, credential-platform, revocation, propagation, telemetry,
reassessment, containment or incident behavior.
