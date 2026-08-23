# Profile Definition Contract

A Profile Definition is an immutable, versioned, Office-owned specification for one exact destination. It is not a Profile, Persona, manifestation, qualification, authority grant, or Seat occupancy.

Every definition binds its steward, target Seat, exact source document and digest, qualification contract, limitations, semantic version, and content digest. Any change produces a new version. Prior versions remain available for lineage and replay.

Definition lifecycle state is append-only:

```text
candidate → approved → current → superseded | revoked
```

Imperator approves the initial development definition version under the explicit development authority surface. The destination Office's authenticated profile-registry mechanic may designate that approved version current even while its cognitive Seats are vacant; this is mechanical stewardship, not Office cognition or Seat authority. At most one definition may be current for each target.

A current definition is an input to Laboratorium. It becomes part of an installable Profile only after Laboratorium binds it to one exact admitted Persona and issues the required transformation disposition under the ordinary Profile Artifact Contract.
