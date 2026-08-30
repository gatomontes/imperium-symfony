# Provider Binding Activation Corridor Disposition interruption evidence

## Status

`BATCH_4_OFFLINE_REPLAY_CONTENTION_AND_INTERRUPTION_EVIDENCE_COMPLETE`

`ActivationCorridorDispositionInterruptionDemonstration` exercises both candidate outcomes on six
disposable-root cases. It uses offline fixtures only; it is not a disposition producer or caller-
authority issuer/consumer.

## Interruption matrix

For each of `QUARANTINED_PENDING_REMEDIATION` and `RETIRE_CORRIDOR`, the demonstration cuts at
`BEFORE_AUTHORITY_CONSUMPTION`, `AFTER_CONSUMPTION_BEFORE_DISPOSITION_COMMIT` and
`AFTER_DISPOSITION_COMMIT`. Restart reopens the disposable immutable stores and converges on the
same exact consumption and offline disposition fixture.

The evidence proves exact replay, changed-evidence refusal, expiry and revocation refusal, one
consumer/outcome winner, read-only recovery, and no activation artifact mutation. Changed dossier
evidence conflicts with the immutable record. A competing consumer/outcome conflicts with the
single authority consumption. Expired or revoked fixtures refuse before consumption.

## Evidence interpretation

The committed records exist only below a temporary offline evidence root. `live_authority_issued`,
`live_authority_consumed` and `live_disposition_sealed` are false. The activation artifact is an
immutable in-memory reference whose canonical digest is identical before and after every case.
Recovery reads the converged consumption and disposition without repairing evidence or creating
successor authority.

`REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE` remains authoritative. A successful offline case proves
only storage and recovery mechanics; it does not prove that an instance-specific active Imperator
principal with corridor-disposition scope or an explicit caller authority exists.

## Preserved perimeter

No principal or binding is activated. No live target, dossier, eligibility, authority or disposition
is created, issued, consumed, selected or sealed. No activation artifact is mutated, consumed,
revoked, repaired or reinterpreted. No capability or credential is handled; no provider is invoked;
no external I/O occurs; Iron Gate and Lazaretto remain closed; and Provider Execution Assurance
remains paused.
