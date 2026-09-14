# PPC5 closed originals, state and reference map

The normative [v1 provision](../contracts/formation-model-preparation-v1.md) and
`FormationModelPreparation` implement these closed shapes. All native original
envelopes are exactly `{schema,id,body,record_digest}`; every native reference is
exactly `{schema,id,digest}`. Native digests are bare lower-case SHA-256; O4 H
digests keep `sha256:`. Full canonical H digest conversion is explicitly checked.

| Versioned shape | Closed contents |
| --- | --- |
| `formation-model-preparation-state/v1` | schema, initialization, bindings, lineages, delegations, authorizations, seals, consumed, nonces |
| Initialization terms | schema, instance_id, citadel_id, expected_head; retained with exact owner decision |
| Context | instance_id, citadel_id, seat, steward; steward exactly Laboratorium and Seat one of the permanent pair |
| `formation-model-specification-binding/v1` body | context, lineage_id, predecessor, specification, binding_original, configuration_original, supporting_originals, expected_head, binding_generation, binding_ref, configuration_ref |
| Specification | provider, model_id, model_version, provider_model_version, configuration, constraints, fallbacks, access_assertion_required=true |
| `formation-model-specification-line/v1` | schema, line_number=1, binding, complete specification, binding_ref, configuration_ref, binding_generation, line_digest |
| `formation-model-sealing-delegation/v1` body | terms, decision; terms contain id, context, binding, actor, public_key, not_before, expires_at, expected_head |
| `formation-model-source-profile/v1` body | envelope: complete original signed Laboratorium source evidence, exactly matched to existing personnel journal custody; ID is mechanically derived from its digest |
| `formation-model-preparation-authorization/v1` body | terms, decision; terms contain id, purpose, context, binding, source_line, complete source_profile, source_evidence (complete typed source original), delegation, not_before, expires_at, expected_head, nonce, correlation, reason |
| `formation-model-sealing-act/v1` payload | schema, domain, effect, authorization, delegation, context, binding, source_line, source_evidence (typed reference), complete source_profile, complete profile, expected_head, nonce |
| `formation-model-preparation-seal/v1` body | envelope, profile; envelope retains the complete signed Conscription act |
| Strict Profile evidence `formation-model-bound-profile-evidence/v2` | Existing signed personnel payload plus schema; content exactly seat, artifact, correspondence |
| Correspondence | seal, binding_ref, configuration_ref, binding_generation |

All schema names above have the `imperium.` prefix. Owner envelopes retain the
existing closed `imperium.citadel-owner-decision/v1` schema. H wrappers retain
the entire closed `imperium.bootstrap-source/v1` H envelope; their body is exactly
kind, content, content_digest, limitations. Configuration kind is
`request-configuration`, with the unchanged exact selected-policy shape.

Supporting H originals are supplied in dependency order, followed by configuration
and binding originals. Every source reference must resolve exactly to an already
loaded complete original; missing, forward and duplicate entries refuse. The
typed source original embeds the exact signed journal evidence, preserving its
legacy custody and signature while giving PPC5 a complete schema-qualified node.

The dependency graph is acyclic: complete H ancestry → native candidate binding →
numbered line → distinct sealer delegation → independently identified owner
authorization over exact source Profile → resulting Profile and signed seal →
Laboratorium v2 evidence → its four findings → reconciliation → exact approval
→ qualification. Neither authorization nor binding hashes reference a future
Profile. Profile authorization ID and source-line identity resolve to retained
originals; no Profile counter supplies a binding generation.

Lineage ID is mechanically derived from context/provider/model ID. Vacancy gives
generation 1; an exact predecessor advances by one, never resets via a new name.
Old bindings remain immutable facts, while current use requires the latest native
lineage entry. Original IDs/nonces and Profile ID/version pairs cannot be reused.
Each state map has at most 256 entries; binding generations are 1–256; native IDs
are 8–80 lower-case identifier characters. Text is bounded, H content is at most
65,536 bytes, each complete H wrapper at most 131,072 canonical bytes with at most
64 source references. Supporting originals are at most 64 entries and 1,048,576
canonical bytes; the complete typed source original is at most 262,144 canonical
bytes. Source Profile is at most 131,072 canonical bytes, constraints/fallbacks
at most 64 unique entries each, and validity at most 3,600 seconds. Profile numeric
version parts have finite six-digit bounds. These limits grant no operational
budget or live permission.
