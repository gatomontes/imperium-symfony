---
title: Bootstrap Manifest Contract
status: current-theoretical-contract
scope: launch-and-primordial-readiness
inherits:
  - /imperium-doctrine.md
---

# Bootstrap Manifest Contract

## Purpose

A Bootstrap Manifest is the single immutable launch authority that binds one exact, compatible primordial Imperium composition. It prevents the Launcher or MasterMason from discovering artifacts, selecting versions, or inferring compatibility.

Individually authentic artifacts do not form an authentic system merely because they can be collected together. No Imperium instance may launch unless one Manifest pins the whole primordial operational structure and its signature verifies under the declared launch trust policy.

## Authority and ownership

The Charter defines the authority under which a Bootstrap Manifest may be issued, signed, superseded, revoked, and used. The Manifest does not enlarge the Charter.

The Launcher may only verify a supplied Manifest and the artifacts it names. MasterMason may only consume the exact verified composition. Neither component may author, repair, complete, reinterpret, or substitute Manifest content.

## Canonical envelope

The serialized Manifest must use one canonical, machine-readable encoding. `manifest_id` is the digest of the canonical `unsigned_payload` only; the identifier and signatures are excluded from that digest. Every signature must bind `manifest_id`.

At minimum the envelope must contain:

```yaml
schema: imperium.bootstrap-manifest/v1
manifest_id: <digest-of-canonical-unsigned-payload>

unsigned_payload:
  charter_generation: <immutable-generation-id>
  instance_class: <declared-instance-class>
  issued_at: <timestamp>
  expires_at: <timestamp-or-null>

  trust:
    signature_policy: <exact-policy-id-and-digest>
    accepted_signers:
      - key_id: <stable-key-id>
        public_key_digest: <digest>
    revocation_snapshot:
      source_id: <source>
      generation: <generation>
      digest: <digest>
      valid_at: <timestamp>

  launcher:
    artifact: <artifact-reference>
    version: <version>
    digest: <digest>

  mastermason:
    artifact: <artifact-reference>
    version: <version>
    digest: <digest>
    compatible_charter_generation: <exact-generation>

  primordial:
    charter:
      artifact: <artifact-reference>
      version: <version>
      digest: <digest>

    offices:
      conscription: { artifact: <reference>, version: <version>, digest: <digest> }
      secretariat:  { artifact: <reference>, version: <version>, digest: <digest> }
      castellan:    { artifact: <reference>, version: <version>, digest: <digest> }

    seats:
      provisional_recruiter: { artifact: <reference>, version: <version>, digest: <digest> }
      ordinary_recruiter:    { artifact: <reference>, version: <version>, digest: <digest> }
      secretary: { artifact: <reference>, version: <version>, digest: <digest> }
      rector:    { artifact: <reference>, version: <version>, digest: <digest> }

    profiles:
      provisional_recruiter: { artifact: <reference>, version: <version>, digest: <digest> }
      ordinary_recruiter:    { artifact: <reference>, version: <version>, digest: <digest> }
      secretary: { artifact: <reference>, version: <version>, digest: <digest> }
      rector:    { artifact: <reference>, version: <version>, digest: <digest> }

    substrates:
      provisional_recruiter: { artifact: <reference>, version: <version>, digest: <digest> }
      ordinary_recruiter:    { artifact: <reference>, version: <version>, digest: <digest> }
      secretary: { artifact: <reference>, version: <version>, digest: <digest> }
      rector:    { artifact: <reference>, version: <version>, digest: <digest> }

    routes:
      artifact: <reference>
      version: <version>
      digest: <digest>

    bootstrap_machine:
      artifact: <reference>
      version: <version>
      digest: <digest>

    bootstrap_recovery_machine:
      artifact: <reference>
      version: <version>
      digest: <digest>

    runtime_concurrency_replay:
      artifact: <reference>
      version: <version>
      digest: <digest>

  compatibility:
    declaration: <artifact-reference>
    version: <version>
    digest: <digest>

signatures:
  - key_id: <stable-key-id>
    algorithm: <algorithm>
    signed_payload_digest: <manifest-id>
    signature: <bytes>
```

An implementation may encode repeated artifact records as arrays. It may not omit their semantic fields.

## Required pinned composition

The Manifest pins the entire primordial operational structure:

- the Charter and its exact generation
- Launcher implementation
- MasterMason implementation
- Conscription, Secretariat, and Castellan definitions
- the resident Recruiter, Secretary, and Rector Seat definitions
- distinct provisional-root and ordinary-successor Recruiter Profile artifacts, plus Secretary and Rector Profiles, including their approval/current-active attestations
- the exact authorized generic substrate for the provisional Recruiter, ordinary Recruiter, Secretary, and Rector
- the succession contract that limits the provisional Recruiter to producing and qualifying one ordinary Recruiter successor before Secretary or Rector may be commissioned
- permitted primordial routes
- the [bootstrap state-machine and transition table](bootstrap-state-machine.md), governing initial bootstrap only
- the [bootstrap forward-recovery machine](bootstrap-forward-recovery.md), governing failure cleanup and retry from durable checkpoints
- the shared [runtime concurrency and replay primitive contract](runtime-concurrency-replay.md), including its canonical encoding and validation implementation
- the compatibility declaration binding all named versions
- signature policy, trusted signer keys, and the exact revocation snapshot used at launch

References alone are insufficient. Every artifact entry must include an immutable content digest and exact version or generation. Mutable tags, branch names, unversioned paths, “latest,” and runtime discovery are forbidden.

## Validation order

Before any Imperium state is created, the Launcher must mechanically:

1. parse the canonical Manifest schema without extension-based authority
2. canonicalize `unsigned_payload`, recompute its digest, and match `manifest_id`
3. verify the required signature threshold and signer identities
4. verify that the revocation snapshot is authentic and valid for the declared launch time
5. resolve only the exact artifact references named by the Manifest
6. recompute and match every artifact digest
7. verify each artifact's declared version, approval/current-active evidence, and Charter generation
8. verify the pinned compatibility declaration
9. refuse unknown required fields, missing required fields, duplicate semantic identities, unresolved references, or conflicting versions
10. instantiate only the pinned MasterMason bound to the pinned Charter generation and Manifest identifier

Validation is all-or-nothing. No Office, Seat, manifestation, route, or persistent instance state may be created before it succeeds.

## Runtime binding

The following values become immutable identity attributes of the resulting instance:

- Manifest identifier
- Charter generation
- Launcher digest
- MasterMason digest
- compatibility-declaration digest
- primordial artifact-set digest

MasterMason must attach those values to every bootstrap event and refuse any primordial artifact or transition not pinned by them.

A later Manifest is a different proposed composition. It cannot silently alter a running instance. Upgrade, recovery, or replacement requires its own Charter-declared lifecycle procedure.

## Failure

The Launcher must refuse launch when the Manifest is absent, expired where expiry applies, malformed, incompletely signed, revoked, internally inconsistent, incompatible, or mismatched with any resolved artifact.

It may report exact validation failures. It may not search for substitutes, downgrade versions, choose another trust root, repair the Manifest, or infer that individually valid artifacts are compatible.

## Nonexistence rule

This contract defines the required artifact; it is not itself an instance-specific Bootstrap Manifest. Until an implementation produces a complete signed Manifest satisfying this contract, Imperium has no valid launch composition and must remain unbootable.

## Invariant

> One verified Manifest pins one complete primordial composition. No Manifest, no launch.
