# Profile Artifact Contract

## Purpose

A Profile is an immutable, Persona-derived cognitive artifact that declares the
capacity in which a manifestation is to be assembled and the contract against
which that manifestation may be qualified or examined. A Profile is not a
Persona, live agent, qualification, authority grant, or Seat occupancy.

The canonical machine-readable envelope is defined by
[`profile-artifact.schema.json`](profile-artifact.schema.json).

## Immutable envelope

Every Profile binds:

- `profile_id` and immutable `profile_version`;
- `artifact_class`: `examination_only`, `officer`, or `operative`;
- exact source Persona identity, version, digest, and admission state;
- destination steward and exact Seat, mission role, or examination target;
- Laboratorium transformation case, specification version, and Alchemist
  disposition;
- the cognitive payload and qualification or examination contract;
- limitations, `DERIVED_FROM`, and optional `SUPERSEDES` lineage; and
- `content_digest`, computed over the canonical envelope with the
  `content_digest` value omitted.

The digest algorithm and canonicalization method must be declared. A change to
any bound field creates a new Profile version and digest; mutation in place is
forbidden.

## Persona lineage

Every Profile derives from one exact Persona version.

- `officer` and `operative` Profiles require an `admitted` Persona and its
  Garrison admission record.
- `examination_only` Profiles require a `pending_admission` Persona, Foundry
  approval evidence, and the exact Senate examination identity.

An `examination_only` Profile may target only its named Senate proceeding. It
cannot be approved, designated current/active, installed for ordinary
qualification, occupy a Seat, perform a mission, or be converted into another
artifact class. Closure of the proceeding or rejection of the Persona expires
it and its assembly packet.

## Lifecycle attestations

Lifecycle state is not written into the immutable envelope. Each transition is
an append-only attestation referencing the exact `profile_id`,
`profile_version`, and `content_digest`. This prevents a status update from
mutating the authenticated artifact.

The canonical record shape is defined by
[`profile-attestation.schema.json`](profile-attestation.schema.json).

Ordinary Profiles may progress only through:

```text
candidate
→ under_examination
→ approved
→ current_active
→ superseded | revoked | expired
```

An approval attestation must name the exact competent authority established by
the Charter and the exact examination disposition on which approval depends.
No administrative identity, including Imperator, is an approval authority
unless a separate Charter provision explicitly assigns that exact
artifact-class jurisdiction.

A `current_active` designation is issued by the destination steward only after
approval. At most one Profile version may be current/active for a given steward
and target. Designating a successor current/active requires a `SUPERSEDES`
relation and a supersession attestation for the predecessor.

Approval authority for each artifact class must be constitutionally assigned.
An artifact remains non-installable until its class-specific competent
authority has issued a structurally and cryptographically valid attestation.
The authenticated initial bootstrap corpus may identify predeclared Profiles
and approval attestations required to establish the primordial runtime; their
validity derives from that corpus, not from a runtime SuperAdmin signature.

`examination_only` Profiles use a closed lifecycle:

```text
created → packaged → instantiated → consumed → expired
```

Every transition must preserve actor or mechanic identity, timestamp,
correlation, prior-state reference, reason, and record digest. Missing,
ambiguous, out-of-order, or invalidly signed attestations fail closed.

## Installability

Conscription may install an ordinary Profile only when all of the following are
structurally and cryptographically valid:

- immutable envelope and content digest;
- source Persona lineage and admission evidence;
- favorable Laboratorium disposition;
- required examination and competent-approval attestations;
- destination-steward `current_active` designation;
- target correlation; and
- no later supersession, revocation, or expiration attestation.

MasterMason must validate the complete installability chain before
commissioning Conscription. Conscription independently verifies the supplied
chain before installation.

For a Senate assembly, Conscription may package an `examination_only` Profile
only for its exact MasterMason commission and originating Senate case. It is not
an installable ordinary Profile and does not enter the ordinary approval
lifecycle.
