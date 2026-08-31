# Provider Effect Principal and Binding Activation Resumption Batch 5 adversarial audit

## Result

RESUMPTION_BATCH_5_ADVERSARIAL_AUDIT_COMPLETE

Batch 5 adversarially audits the canonical principal-activation entry point and
its immutable combined winner. It introduces no production runtime path.

## Findings

- Same-root contention convergence produces one immutable combined winner.
- Changed-root and changed-evidence refusal occur before a winner.
- Expiry and revocation refusal occur before activation.
- Exact replay returns the same immutable winner.
- A crash before the combined commit leaves no activation and no consumed authority.
- A crash after the combined commit reconstructs the exact winner; it cannot
  create a second winner when process-local material disappears.
- Generation and binding substitution refusal occur before activation.
- Durable records prove secret exclusion.
- Authority consumption exists only inside the principal-activation winner:
  there is no consumption-only state.
- The winner grants no durable authority to activate a provider binding, resolve
  credentials, invoke a provider, start external I/O, or retry an effect.

## Closed downstream boundary

The exact principal generation is ACTIVE and its single-use activation authority
is consumed without continuing authority. The provider binding remains BOUND_INACTIVE.

No credential or process-local capability is persisted or handled. No provider
is invoked, no external I/O or provider effect is started, and no live consumer
is migrated. Iron Gate and Lazaretto remain closed.
UNKNOWN_REPLAY_PROHIBITED remains binding.
