# Principal Activation Decision Authority Provenance Remediation — Batch 4 read-only aggregate reconstruction

## Result

BATCH_4_READ_ONLY_AGGREGATE_RECONSTRUCTION_COMPLETE

The reconstruction service accepts only exact caller-supplied evidence for the
source principal, scope grant, pending successor, effective activation
disposition, principal attestation, provider-assurance admission, execution
boundary, decision-issuance authorization and all six interruption cases.

## Classifications

- ELIGIBLE means the entire offline chain validates exactly and all interruption
  coverage is present and convergent.
- INCOMPLETE means required evidence or interruption coverage is absent.
- CONFLICTED means evidence types, exact digests, lineage, coverage or
  interruption semantics disagree.
- REFUSED means the chain is revoked, consumed, expired, not yet effective or
  lifecycle-ineligible.

The service delegates exact contract validation to the Batch 2 validator. It
writes no record, performs no read repair and does not repair corrupt,
incomplete or conflicting evidence. Its result is transient and read only.

## Preserved perimeter

Reconstruction creates no record, scope, successor, principal, authority,
activation decision or binding activation. It issues and consumes nothing,
mutates no source artifact, handles no credential or capability, invokes no
provider and performs no external I/O.

Iron Gate and Lazaretto remain closed. Provider Effect Principal and Binding
Activation remains paused. UNKNOWN_REPLAY_PROHIBITED remains binding.
