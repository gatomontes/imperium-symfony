---
title: Bootstrap Manifest Contract
status: current-theoretical-contract
scope: initial-bootstrap-only
---

# Bootstrap Manifest Contract

The Bootstrap Manifest is the immutable, signed composition authority for one initial Imperium bootstrap. Launcher must verify its signature, Charter generation, revocation snapshot, implementation digests, and every pinned artifact before MasterMason runs.

## Primordial composition

The manifest pins:

- Conscription and Curia Office definitions
- provisional and ordinary Recruiter artifacts plus the one-use Recruiter succession commission
- Seneschal and Chamberlain personas, Profiles, attestations, Seats, substrates, and paired assembly commissions
- Isolde’s persona and the Curial Secretary Profile, attestations, Seat, substrate, and independent assembly commission
- the Curian route declaration
- the bootstrap and forward-recovery machines

`assembly_commissions.seneschal` and `.chamberlain` must require the same attempt and name each other. `assembly_commissions.secretary` must target `curia.secretary`, require an existing Curia runtime, and remain independent from the governing pair.

The manifest must contain no Rector, Castellan, or Secretariat bootstrap constituent. Unknown primordial keys fail validation.

## Integrity

Every artifact record carries its repository-relative path, semantic version where applicable, and SHA-256 digest. `artifact_set_digest` is computed over the canonical ordered artifact records. The signature covers the complete unsigned payload. Profiles additionally preserve source persona, transformation provenance, qualification contract, stewardship, target Seat, and source-content digest.

The machine-readable schema and validator are authoritative for field shape. A missing, extra, stale, revoked, or digest-mismatched constituent refuses launch; implementations may not substitute a nearby artifact.

## Binding and replay

One verified manifest binds one Charter generation and one bootstrap transaction. Its receipt pins `instance_id`, manifest identity, artifact-set digest, Launcher digest, and MasterMason digest. A persisted transaction may resume only with those exact values.
