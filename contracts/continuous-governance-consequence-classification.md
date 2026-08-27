# Continuous-governance consequence classification

## Version

`imperium.continuous-governance-consequence-classification/v1`

## Purpose

Classify the consequence of one already-authorized native act without granting, widening,
consuming, revoking, or replacing its authority. The classification accompanies a native
Folium; it never becomes the source of permission for that Folium.

## Canonical tiers

| Tier | Meaning | Minimum consequence class |
| --- | --- | --- |
| `ADVISORY_COGNITION` | Produces internal analysis or proposed content without deciding or acting externally | `INTERNAL_REVERSIBLE` |
| `INTERNAL_MISSION_DECISION` | Produces or consumes an internal institutional judgment | `INTERNAL_CONSEQUENTIAL` |
| `RESOURCE_USE` | Commits a bounded internal provider, credential, compute, or other resource | `INTERNAL_RESOURCE_COMMITMENT` |
| `DELEGATED_EXTERNAL_ACTION` | Would authorize a bounded external act | `EXTERNAL_REVERSIBLE` |
| `IRREVERSIBLE_EXTERNAL_EFFECT` | Would authorize an effect that cannot be mechanically undone | `EXTERNAL_IRREVERSIBLE` |

Only `ADVISORY_COGNITION` is adopted in Batch 1. The external tiers are vocabulary, not an
opened Iron Gate, sortie, tool, credential, network, execution, or effect boundary.

## Required bindings

The classification binds the exact native record type, source authority identity and digest,
instance, competent Office, target Seat, purpose, and ordered input digest. Its
`runtime_principal_references` preserve those existing identities without manufacturing an
owner, Persona, Profile, Officer process, Manifestation, represented human, credential holder,
or sortie identity that the native source does not provide.

Every principal reference states its role and lifecycle evidence. `REFERENCE_ONLY` means the
identifier is carried for attribution but the classification neither creates nor changes its
lifecycle state.

## Absolute exclusions

The classification has all authority-bearing flags false. It grants no decision, approval,
occupancy, assembly, activation, cognition, provider, credential, tool, network, perimeter,
external-action, execution, continuation, revocation, or incident authority. A higher tier
cannot authorize anything merely by being recorded.
