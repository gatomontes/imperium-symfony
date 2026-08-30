# Principal Activation Decision Authority Provenance Remediation — Batch 3 interruption evidence

## Result

BATCH_3_OFFLINE_INTERRUPTION_REPLAY_AND_CONTENTION_PROOF_COMPLETE

A disposable-root demonstration now exercises the scope grant, scope successor
and decision-issuance authorization fixture paths at two exact cuts: absent
before commit and present after the immutable fixture commit.

## Proven behavior

Across all six cases:

- absent before commit leaves no fixture;
- recovery creates one immutable winner;
- exact replay converges on the same digest;
- two same-root contenders converge for identical evidence;
- changed evidence and a changed contender refuse;
- expiry and revocation refuse before fixture admission;
- read-only recovery changes no file and performs no repair; and
- private evidence and a sanitized summary are written outside the runtime
  fixture paths.

The demonstration uses only disposable roots and the existing immutable-record
primitive. It does not use an authority-consumption store. Its synthetic expiry
and revocation fields are offline refusal evidence, not live authority.

## Preserved perimeter

No live Operator Root or principal is identified. No scope, successor,
activation decision or authority is produced. No authority is issued or
consumed, no principal or binding is activated, no credential or capability is
handled, no provider is invoked, and no external I/O occurs.

Iron Gate and Lazaretto remain closed. Provider Effect Principal and Binding
Activation remains paused. UNKNOWN_REPLAY_PROHIBITED remains binding.
