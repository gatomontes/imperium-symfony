# Canonical mission doctrine

## Authority rule

An act is authorized only by an unexpired, unrevoked, unconsumed capability issued from an
explicit Operator mission order and bound to the same dossier identity, mission ID, action, actor,
target, validity interval, and unique nonce. A capability permits one use unless its dossier
explicitly says otherwise; this campaign defines no multi-use capability.

## Non-authorities

Readable lineage proves provenance only. Historical approval, object possession, service
construction, deterministic output, prior admission, issuer-service possession, competence, and
consumed native-transition authority do not authorize a mission act. No missing mission may be
synthesized, defaulted, inferred, or inherited from any of them.

## Mission contract

`imperium.operator.mission-dossier/v1` is an immutable canonical document. Its identity is the
SHA-256 digest of recursively key-sorted canonical JSON. It contains the mission ID, kind/version,
Operator identity, exact forty-hex commit target, requested/permitted/prohibited acts, success and
evidence requirements, time/resource budgets, issuance/expiry, terminal rules, and authorization
provenance. Exact fields and value validation are enforced by `MissionDossier`.

Changing an authority-relevant field necessarily changes the identity. A capability bound to the
old identity therefore cannot authorize the changed dossier.

## Lifecycle and transition table

| Required state | Exact capability action | Consumed | Resulting evidence | Refusal | Next state |
|---|---|---|---|---|---|
| `PROPOSED` | `authorize` | yes | Operator authorization grant | invalid/expired order | `AUTHORIZED` or `REFUSED` |
| `AUTHORIZED` | `admit` | yes | admission record | scope/target mismatch | `ADMITTED` or `REFUSED` |
| `ADMITTED` | `inspect` | yes | execution-start record | budget/target/actor mismatch | `EXECUTING` or `REFUSED` |
| `EXECUTING` | `assemble-evidence` | yes | bounded evidence ledger | adapter/budget failure | `EVIDENCE_ASSEMBLED` or `ABORTED` |
| `EVIDENCE_ASSEMBLED` | `complete` | yes | terminal receipt | unmet evidence criteria | `COMPLETED` or `ABORTED` |
| any nonterminal state after expiry | no new authority | none | expiration record | expired | `EXPIRED` |

Every command, admission, decision, grant, capability, transition, evidence item, result, receipt,
refusal, revocation, and expiration record in this mission corridor carries the same `mission_id`.

