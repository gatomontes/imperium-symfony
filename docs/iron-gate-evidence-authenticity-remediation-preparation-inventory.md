# Iron Gate Evidence Authenticity Remediation preparation inventory

## Result

`PREPARATION_BATCH_0_COMPLETE`

The completed receipt-binding corridor controls record order and replay posture, but provider truth
is not yet an authoritative input. Invocation and result sealing are separate calls; therefore an
internal caller holding an admission ID can nominate accepted response bytes that were never
returned by the callback. Lazaretto then validates those bytes against values extracted from the
same bytes. That proves shape, not provider provenance.

Read-only reconstruction also omits the effect-start journal and provider invocation admission—the
two records surrounding the external-effect boundary—and does not reconstruct the source request,
decision or Curia occupancy. Current digest checks prove canonical integrity under a trusted-writer
filesystem model, not hostile-writer non-forgeability.

The authoritative classifications, consumer postures, stop conditions and smallest sequence are in
`docs/next-campaign-iron-gate-evidence-authenticity-remediation.md`. This inventory opens no runtime
or provider boundary.

## Batch 1 eligibility

The smallest next artifact is a versioned callback-bound response-envelope contract defining:

- exact invocation-admission, journal, claim and request references;
- provider, operation, destination, idempotency identity and payload digest;
- observed status, headers, exact bytes digest and opaque sealed-content reference;
- callback start, response observation and receipt times;
- one producer posture owned by the journal-gated invocation boundary;
- one consumer posture that rejects caller-supplied status or response bytes; and
- unknown-outcome behavior when no envelope exists.

Batch 1 may define and test that contract only. It may not implement capture, call a provider or
migrate a live consumer.
