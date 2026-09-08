# Bounded public institutional evidence collection

This allowlist belongs to the [native lineage campaign](next-campaign-citadel-native-institutional-lineage.md).
It permits read-only collection of existing public institutional records, not new
institutional acts or private runtime inspection. The last supplied root is
`E:\htdocs\imperium`; confirm source identity and preserve the installed tree.
Reuse earlier packets where available; never reset the installation to their date.

## Exact starting records

| Artifact | Source-defined public path relative to the installation | Expected retained digest |
| --- | --- | --- |
| Garrison occupancy | `var/imperium/offices/garrison/occupancy/garrison-constable-binding-37b4c4192f137e2601ea.json` | `40c824cf0537a432c2968cbe38b775c5c4181bb4981b4458977b468f45e55079` |
| Garrison delivery | `var/imperium/mastermason/qualified-manifestations/qualified-delivery-9ff8f5c10d594146f436.json` | `304a310b24b5ec8d62fe3acffa4561704635472a45328d5a73f3eae6a6507e64` |
| Guildhall cohort | `var/imperium/offices/guildhall/occupancy/guildhall-binding-10626add6873c1abf6d9.json` | `29c99f3b0e2c5c6b0ff75efc7e6456ff9940d17dd05607f47a68e92da260d9b6` |
| Guildhall summons | Unique `var/imperium/curia/proceedings/*.summons.guildhall-summons-552602ca89e9b8a072b3.json` | `6d94c8373febdd31fef69f771c78dbc040c98bd7552c90fbd717ec7e9beb42f9` |

The four exact Guildhall deliveries are under the same qualified-manifestations
directory. Match each to its retained cohort reference:

| Delivery ID | Expected digest |
| --- | --- |
| `qualified-delivery-e9cf6b75f377a5d6ac2d` | `23cfae75a61453105ce091eeb78c918aceb4d2584b176054344fbbab71079633` |
| `qualified-delivery-48fa0e6ee99ce58fa05e` | `bbef5da564c41f308a24587054f0a2dca5d9045ec5d61cecdee629819833bb26` |
| `qualified-delivery-ea075145d740b86e2b1f` | `ea7c9fd18c13ab493c7114a8abc692600fd5ba237b22520f46f1e86fabb9f221` |
| `qualified-delivery-046a924ffb9fbe0cb886` | `351b615753c1597ea67543f0f3801b14f487a1980df8fcc2e70f7c9bf58abc6e` |

A mismatch is evidence to retain and classify, not permission to repair a record.
These digests bind supplied historical bytes; they do not attest custody or currentness.

## Bounded reference expansion

Follow only actual, validated IDs in these records to source-defined public
Conscription `inbox/<commission_id>.json` and MasterMason
`activation-cases/<case_id>.json` records when the producer schema establishes
their public institutional nature. Check field names against the exact installed
source; do not guess IDs. If a needed source contains private content, request
its existing public export/projection instead; classify the boundary and continue
other work. Do not alter the source to redact it.

For Guildhall acceptance, use an exact existing public acceptance ID/index supplied
by the custodian under `var/imperium/offices/guildhall/acceptances/`. Require this
cohort's binding ID and digest, then the actual commission/inbox envelope and
summons references. Do not scan unrelated mission acceptances or export the whole
proceedings directory to find a match. A missing supplied ID remains UNAVAILABLE.
The original pending cohort is immutable even when a later acceptance exists.

Other seats: bounded public occupancy paths under offices/laboratorium, senate
and conscription; only exact relevant seat records and their genuine referenced
operator-root installations under `var/imperium/operator-root/installations/`.
Supersession/currentness evidence must come from its actual public producer or
custodian export. Missing directory or source does not justify bootstrap.

The ordinary Recruiter reader uses the T04 SUCCESS event in private bootstrap
state. Do not open/export `var/imperium/bootstrap-state.json` or journals. Request
an existing public successor receipt/projection with instance, manifestation,
generation, powers, provenance and currentness. If no safe exporter exists,
document/design its public contract; do not manufacture its output or read the
private backing state in this campaign.

## Custody and authority requests

Ask for existing public evidence, not passwords, private keys or credential values:

- Custodian's installation declaration, observation time, public custody references
  and existing public signing interface. Owner root confirmation is not independent custody proof.
- Formation trust: Citadel ID, raw Ed25519 public key, fingerprint independently
  confirmed, CITADEL_MISSION_FORMATION competence, validity/revocation and enrollment
  provenance. Do not read the formation journal or reuse ProtectedMission competence.
- Exact Castellan/formation Locksmith public appointment terms/signatures and
  candidate admission, suitability, Profile, Senate examination, owner approval
  and qualification closure. Loco must not originate institutional judgments.
- Any actual authority-upgrade/currentness records addressing the missing Garrison
  powers and Guildhall's formation scope. Do not infer them from today's producer defaults.

Write collected evidence to a fresh directory outside all source trees; retain
only necessary public artifacts in the private review packet. Do not commit raw
installed records, private missions or credential-adjacent material. Public
projections need their own hashes and an explicit original/projection provenance
qualification. Log bounded paths/counts, source identity, failures and before/after
observations without dumping sensitive contents. Prepare a complete ZIP and manifest.
