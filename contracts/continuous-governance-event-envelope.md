# Continuous-governance event envelope

## Version

`imperium.continuous-governance-event-envelope/v1`

## Purpose

Reference one governance-relevant native Folium as an attributable event without replacing,
rewriting, interpreting, or authorizing that Folium. The envelope is emitted during the native
transition, sealed independently, and carries the exact native identity and digest.

## Event semantics

The canonical vocabulary distinguishes:

- `OBSERVATION`;
- `INFERENCE`;
- `RECOMMENDATION`;
- `DECISION`;
- `AUTHORIZATION_REQUEST`;
- `AUTHORIZATION`;
- `ACTION_ATTEMPT`;
- `EFFECT_COMPLETION`; and
- `EVIDENCE_RETURN`.

An `AUTHORIZATION_REQUEST` is expressly not an authorization. Batch 2 adopts only that event
kind for the existing governance-cognition request.

## Required evidence

Every envelope binds its event identity, instance, event kind, semantic class, exact native
Folium schema/identity/digest, native authority identity/digest, occurrence and recording time,
consequence classification, and existing runtime-principal references.

The envelope records whether an action was attempted or an effect completed. Both are false
for an authorization request.

## Absolute exclusions

The envelope is not telemetry, a policy evaluation, a decision, an authority, a lease, a
receipt, or a substitute for the native Folium. It grants and consumes no authority and cannot
open cognition, provider, credential, tool, network, perimeter, external-action, execution,
continuation, revocation, containment, or incident authority.
