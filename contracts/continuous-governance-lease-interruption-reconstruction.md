# Continuous-governance lease interruption reconstruction

## Version

`imperium.continuous-governance-lease-interruption-reconstruction/v1`

## Purpose

Provide one read-only, fail-closed reconstruction of the completed interruption of an exact
unclaimed internal governance-cognition lease. The view begins with the immutable source lease
and verifies the separately sealed disposition, enforcement authority, and enforcement result.

## Included evidence

The reconstruction verifies exactly four native artifacts:

1. the unconsumed, unmodified, and unclosed governance-cognition lease;
2. the exact Seneschal `INTERRUPT` disposition;
3. the exact, expiring, single-use Locksmith enforcement authority; and
4. the immutable Locksmith enforcement result.

It also proves mechanically that no durable governance invocation claim consumes the lease.
Every included artifact and reference must remain digest-intact and agree on exact identity,
instance, scope, actor, enforcer, and permitted transition. Missing, substituted, duplicated,
scope-divergent, or claim-bearing evidence fails stopped.

## Authority boundary

Reconstruction is read-only. It invokes no cognition, creates no claim, mutates or closes no
lease, consumes no credential, performs no propagation, and grants no continuing, perimeter,
external-action, telemetry, containment, incident, or execution authority.
