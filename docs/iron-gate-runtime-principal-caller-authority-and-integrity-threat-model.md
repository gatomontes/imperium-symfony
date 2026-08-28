# Iron Gate runtime-principal caller authority and integrity threat model

## Status

`CONTRACT_DEFINED_ENFORCEMENT_NOT_IMPLEMENTED`

Batch 6 selects runtime-principal caller authority and defines three non-interchangeable, expiring,
single-use authorities:

| Principal | Exact transition | Competent source | Exact target |
| --- | --- | --- | --- |
| occupied Curia Seneschal | `REQUEST_EXACT_OUTBOUND_EMAIL_AUTHORIZATION` | intact active Seneschal occupancy generation | one canonical scope and holder digest |
| Imperator runtime principal | `DECIDE_EXACT_OUTBOUND_EMAIL_REQUEST` | intact active Imperator principal attestation | one request ID and digest |
| Imperator runtime principal | `ISSUE_EXACT_OUTBOUND_EMAIL_AUTHORIZATION` | the exact authorized decision's issuance authority plus principal attestation | one decision ID and digest |

An authority must bind principal ID, office, seat, binding, generation, source ID/digest, exact
transition, target ID/digest, issue/expiry times, single-use and non-continuing posture. The three
authorities may not substitute for one another. Consumption must occur through
`AuthorityConsumptionStore` under a transition-specific consumer name before the target record is
committed.

The contract alone authenticates nothing. Until a competent issuer produces these authorities and
the three services require their consumption, actor attribution remains `EXISTS_FRAGMENTED` and
live adoption remains prohibited.

## Integrity threat model

Current immutable records provide `TRUSTED_WRITER_CANONICAL_INTEGRITY`: canonical serialization,
digest verification, immutable conflict detection and single-authoritative-root file locks detect
accidental corruption and mutation that does not recompute the digest.

They do not provide `HOSTILE_WRITER_NON_FORGEABILITY`. A process with unrestricted write access to
the authoritative root can replace content and recompute an unkeyed digest. No document or test may
call that cryptographic authorship, signature verification or hostile-writer tamper resistance.

The deployment posture remains `SINGLE_AUTHORITATIVE_ROOT_ONLY`. Signatures/MACs, independent
append-only custody, multi-host consensus and split-brain resistance are deferred boundaries, not
requirements silently satisfied by SHA-256.

## Closed boundary

Batch 6 defines the contract and threat model only. It does not issue or consume caller authority,
change the three transition services, perform external I/O, migrate a live consumer or open Iron
Gate, Lazaretto, sortie, credential-platform, revocation, propagation, telemetry, reassessment,
containment or incidents.
