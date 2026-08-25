# Codex Imperii contract

The Codex Imperii is the canonical digest-bound index of every Folium constituting one Imperium instance. Its machine-readable schema is [`codex-imperii.schema.json`](codex-imperii.schema.json).

The Codex records identity and lineage, not duplicated Folium payloads. Each entry binds an exact record identity, schema, issuing Office, storage reference, canonical digest, lifecycle relation, and sequence.

## Invariants

1. `instance_id` identifies exactly one mission instance.
2. `folia` is an ordered list and every `sequence` is unique.
3. Every `folium_id` and storage reference is unique within the Codex.
4. Every referenced Folium resolves, validates against `folium_schema`, belongs to the same instance where applicable, and matches `digest`.
5. `current_checkpoint` names a checkpoint proven by the indexed Folia; it does not create that checkpoint or its authority.
6. `generation` begins at one and increments exactly once for each lawful checkpoint advance.
7. Adding or transitioning a Folium requires a new Codex digest.
8. `last_advance_fingerprint` binds the complete input of the latest transition; exact replay returns the existing Codex only when it matches.
9. Omission, substitution, reordering, conflicting replay, or digest mismatch fails stopped.

The Codex grants no approval, occupancy, deployment, cognition, provider, credential, tool, perimeter, external-action, execution, continuation, or reuse authority.
