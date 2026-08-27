# Internal cognition lease controls

## Version

`imperium.internal-cognition-lease-controls/v1`

## Purpose

Normalize control metadata on the existing operational and governance cognition leases without
creating a new lease family, widening scope, changing the native issuer or consumer, or
implementing revocation.

The metadata binds freshness evidence, exact scope, supersession conditions, invalidation
conditions, stop conditions, and the current revocation posture. Claim-time validation rereads
the same native decision and request and rejects changed metadata when it is present.

## Revocation posture

`revocation.status` is `UNASSIGNED_DEFERRED_BOUNDARY`. `authority_reference` is `null` and both
`propagation_implemented` and `lease_closure_implemented` are false. This records the gap; it
does not appoint a revoker, revoke a lease, close authority, or create a kill switch.

## Compatibility

Historical immutable leases without this metadata remain valid under their existing native
contracts. They are never rewritten or retrofitted. Newly issued leases carry the metadata and
must match it exactly at claim time.

## Absolute exclusions

The metadata grants no cognition, provider, credential, tool, network, perimeter,
external-action, execution, continuation, revocation, containment, or incident authority.
