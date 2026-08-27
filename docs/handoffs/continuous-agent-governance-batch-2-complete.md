# Continuous Agent Governance Controls Batch 2 complete

## Result

Imperium now has the versioned, authority-empty
`imperium.continuous-governance-event-envelope/v1` contract. One existing internal
governance-cognition request emits an independently sealed envelope during its native
transition.

The envelope binds the exact native Folium identity and digest, native authority, occurrence
and recording time, Batch 1 consequence classification, and existing runtime-principal
references. Its event semantics are `AUTHORIZATION_REQUEST` /
`REQUEST_NOT_AUTHORIZATION`; action-attempt and effect-completion are both false.

Exact replay returns the existing event. A tampered or conflicting event fails stopped. If the
native request survives an interruption before event persistence, exact request replay emits
the missing deterministic envelope without changing the native request identity.

## Preserved boundary

The event is not telemetry, a decision, an authorization, a lease, an execution receipt, or a
replacement for the native Folium. Every authority-bearing field is false. No historical
record is rewritten and no Iron Gate, Lazaretto, sortie, external action, revocation,
telemetry, containment, incident, or credential-platform boundary opens.

## Next bounded batch

Batch 3 may construct one read-only internal mission reconstruction by composing references to
the existing decision bundle, native Folia, authority consumptions, cognition claims, provider
journal, custody, and retirement evidence. It must state exclusions mechanically and may not
replay effects or manufacture missing evidence.
