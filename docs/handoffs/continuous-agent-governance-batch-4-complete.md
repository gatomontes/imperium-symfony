# Continuous Agent Governance Controls Batch 4 complete

## Result

New operational and governance cognition leases now carry versioned common control metadata
for freshness, exact scope, supersession, invalidation, and stop conditions. Durable claim
creation validates the metadata against the exact native decision, request, issue time, and
expiry when the metadata is present.

Historical immutable leases without the Batch 4 metadata remain valid under their original
contracts. They are not rewritten or retrofitted.

## Revocation boundary

The metadata records `UNASSIGNED_DEFERRED_BOUNDARY`, `authority_reference=null`,
`propagation_implemented=false`, and `lease_closure_implemented=false`. This makes the gap
explicit without silently appointing an actor or implementing revocation.

No live lease is revoked or closed. No revocation disposition, propagation, acknowledgement,
kill switch, telemetry, containment, incident, Iron Gate, Lazaretto, sortie, or external
execution boundary opens.

## Next boundary

The internal identity, event, reconstruction, and lease-metadata foundations are now present.
The next work is a separately bounded revocation-disposition and propagation design. It must
identify competent authority and scopes before any runtime implementation begins; Batch 4
itself grants no permission to start propagation.
