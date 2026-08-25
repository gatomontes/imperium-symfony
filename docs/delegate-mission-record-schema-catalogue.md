# Delegate Mission record-schema catalogue

This is the canonical catalogue of Delegate Mission record families. Each concrete contract and PHP service remains authoritative for its exact field schema and established error vocabulary. The catalogue prevents historical handoffs from competing as current schema specifications.

| Family | Canonical purpose | Representative schema or contract |
| --- | --- | --- |
| Demand and personnel | Capability demand, profession/Persona resolution, personnel commitment, reservation | `contracts/delegate-mission-capability-demand.md` through `delegate-mission-persona-reservation.md` |
| Profile derivation | Scope authorization, commission, candidate, and intake | `contracts/delegate-mission-profile-*.md` |
| Senate examination | Stand admission, hearing, questions, testimony, findings, reconciliation, disposition | `contracts/delegate-mission-*-question-*.md`, `delegate-mission-independent-senator-findings.md`, `delegate-mission-senate-disposition.md` |
| Operational construction | Qualification, manifestation assembly, and immutable seat binding | `contracts/delegate-mission-operational-construction.md`; indexed as ordered Folia in `imperium.codex-imperii/v1` |
| Deployment and activation | Deployment authorization, recoverable custody transition, runtime activation | `contracts/delegate-mission-deployment-and-custody-transition.md`, `delegate-mission-runtime-activation.md` |
| Mission control | Intake, bounded cognition commission, and readiness | `contracts/delegate-mission-control-intake.md`, `delegate-mission-bounded-cognition-commission.md`, `delegate-mission-resource-invocation-readiness.md` |
| Model governance | Criteria commission, Oracle evidence/judgment, selection, runtime binding | `contracts/delegate-mission-model-criteria-commission.md` through `delegate-mission-model-binding.md` |
| Provider access | Model-access attestation, Imperator decision, lease, and provider activation | `contracts/delegate-mission-access-and-authorization.md` |
| Invocation claim | Atomic consumed lease/turn authority and stable idempotency identity | `imperium.clavium-provider-invocation-claim/v1` |
| Invocation journal | Mutable compare-and-swap provider boundary state | Claim-bound journal states: pre-I/O failure, I/O started, unknown outcome, or response sealed |
| Provider response | Immutable credential-free provider response envelope | Claim-bound response identity and response digest |
| Bounded turn | One exact Citadel cognition outcome or governed recovery of a sealed response | `contracts/delegate-mission-bounded-cognition-turn.md` |
| Result and return | Result disposition, return authorization, and terminal retirement | `contracts/delegate-mission-result-return-and-retirement.md` |
| Codex | Ordered digest-bound compilation of the instance's Folia | `imperium.codex-imperii/v1`, filename `codex-imperii.json` |

## Canonical record vocabulary

- One record: **Folium**.
- Multiple records: **Folia**.
- Instance compilation: **Codex Imperii**.
- `Vellum` is poetic material, not a record class.

## Universal integrity rules

Every authoritative Folium must have a stable identity, schema identity, canonical digest, issuing or responsible Office, lifecycle relation, and path-contained storage reference where referenced. Immutable identity conflicts fail stopped. Mutable state advances only by compare-and-swap. Exact references resolve both identity and digest and revalidate the referenced Folium's own digest.
