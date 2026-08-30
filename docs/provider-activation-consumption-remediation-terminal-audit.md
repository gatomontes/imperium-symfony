# Provider Activation-Consumption Remediation Terminal Audit

## Result

`BATCH_7_ADVERSARIAL_PROOF_COMPLETE_TERMINAL_AUDIT_PASSED`

The Batch 10 terminal-audit refusal
`BATCH_10_TERMINAL_AUDIT_REFUSED_ACTIVATION_NOT_CONSUMED` remains part of
the historical record. Its activation-consumption defect is closed for the
corrected v2 pre-provider corridor and is superseded there by this audit.

## Corrected authority boundary

- The v2 combined admission is one immutable activation, execution-authority,
  and effect-start winner.
- Admission contention is activation-keyed. Competing execution authorities
  for one activation cannot create multiple winners.
- Lawful revocation and combined admission use the same activation-keyed lock
  and mutually exclusive winner records.
- Revocation before the first admission winner refuses admission. A completed
  combined winner cannot be displaced by later revocation.
- The v2 stationary resolver accepts only the combined admission contract and
  rejects intact v1 admission evidence.
- Expired lineage refuses before winner creation.
- A crash before the immutable put leaves no winner. Corrupt or tampered
  reconstruction refuses. A completed winner and proof reconstruct by exact,
  read-only replay.
- Missing stationary credential material refuses without creating a proof.
- Durable records exclude credential bytes, environment-variable names, and
  process-local capability identity.
- The threat model remains one trusted writer root. Filesystem access outside
  that root is not elevated into execution authority.

## Adversarial proof

Batch 7 covers combined-winner contention, admission-versus-revocation
exclusivity, lineage expiry, corrupt reconstruction, v1 refusal, missing
credential material, exact replay after process-local material disappears,
recursive secret exclusion, and an inert effect vector.

No provider was invoked. No external I/O, provider bytes, live command,
principal activation, or provider-binding activation was introduced. Iron Gate
and Lazaretto remain closed.

## Terminal posture

The activation-consumption remediation campaign and the original Provider
Execution Boundary Redesign pre-provider campaign are complete. This closure
does not authorize live adoption or provider execution.

Provider execution remains refused because the executor principal is inert,
the provider binding is inactive, no live-call contract exists, provider
assurance gaps remain open, and unknown provider outcomes retain the terminal
posture `UNKNOWN_REPLAY_PROHIBITED`.

A later provider-effect campaign requires a new, explicit campaign-ready
handoff. It cannot infer authority from this audit.
