# Record taxonomy

Imperium's authoritative record vocabulary is:

| Term | Meaning |
| --- | --- |
| **Folium** | One immutable or explicitly stateful authoritative JSON record. |
| **Folia** | Two or more Folium records. This is the canonical plural of Folium. |
| **Codex Imperii** | The canonical, digest-bound compilation and index of the Folia belonging to one Imperium instance. |

English prose may explain *Folia* as records, but new Imperium contracts and doctrine must not use *folio*, *folios*, or *vellum* as competing canonical entity names.

## Folium

A Folium remains authoritative in its own right. It owns its schema, identity, contents, digest, lifecycle status, and issuing Office. Inclusion in the Codex does not transfer ownership or authority to the Codex.

Every indexed Folium must expose or be bound to:

- one stable record identity;
- one exact schema identity;
- one issuing Office;
- one canonical content digest;
- one lifecycle relation and sequence within the instance; and
- one resolvable storage reference.

## Codex Imperii

The Codex Imperii is named `codex-imperii.json` and declares schema `imperium.codex-imperii/v1`.

It carries the whole governed instance by compiling the identities, schemas, digests, relationships, sequence, storage references, and current checkpoint of its Folia. It does not copy their full payloads. The Folia remain separately verifiable evidence; the Codex binds them into one authoritative whole.

The Codex:

- is scoped to exactly one Imperium mission instance;
- is ordered and append-only except for an explicit checkpoint transition;
- cannot silently omit, replace, reorder, or mutate an indexed Folium;
- grants no authority merely by indexing a Folium;
- must fail stopped when a referenced Folium is absent, malformed, digest-mismatched, stale, or belongs to another instance; and
- must itself carry a canonical digest after every lawful transition.

The Codex is not a database dump, evidence archive, or substitute for Office custody. It is the authoritative map that proves which Folia constitute the instance and how they relate.

## Naming rule

- Singular: `Folium`
- Plural: `Folia`
- Compilation: `Codex Imperii`
- Filename: `codex-imperii.json`
- Schema: `imperium.codex-imperii/v1`

`Vellum` may be used poetically for the material on which something is written, but it is not an Imperium record class.
