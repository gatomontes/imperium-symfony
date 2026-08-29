# Provider Binding stranded activation-artifact disposition

## Status

`BATCH_3_EXPIRED_UNUSED_ARTIFACT_DISPOSITION_COMPLETE`

La Cortine can now seal `QUARANTINED_EXPIRED_UNUSED` for one exact Batch 2 activation authority or
Batch 3 activation lease only after the artifact is expired and still unused. The disposition binds
the terminal custody-refusal document digest and all six intact Batch 2 interruption-evidence
records. It stores an exact reference to the source artifact and never mutates that source.

The other contract dispositions remain deliberately unimplemented. An unexpired artifact cannot be
mechanically declared `QUARANTINED_PENDING_REMEDIATION`, and the corridor cannot be declared
`RETIRE_CORRIDOR`, because both require a competent disposition owner whose principal provenance is
still absent. Rejecting that temptation prevents a deterministic service from impersonating
Imperator.

## Preserved perimeter

Disposition is not revocation, consumption, replacement or successor authority. It grants no
credential-platform authority, credential use, retry, provider invocation or execution authority.
No credential reference or secret is accessed. No capability is issued or reconstructed, no
provider is invoked, no external I/O occurs, and Iron Gate and Lazaretto remain closed. Provider
Execution Assurance remains paused and the terminal custody refusal remains authoritative.
