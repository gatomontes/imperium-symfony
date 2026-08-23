# Operator-root founding installation

Operator-root installation is the temporary pre-operational mechanism for occupying Imperium's initial required Seats. It applies to every Office, not to a privileged list of founding Seats.

The normal founding path uses generic `v0` placeholders. They have no Persona, Profile, or Officer artifact because they are explicit scaffolding, not counterfeit mature personnel.

```console
bin/console imperium:operator:install-required-v0 imperium-instance
```

This deterministically occupies every required founding Seat. The Imperator Seat is excluded because it is held directly by the human Operator. Mission-specific and dynamically commissioned Seats are excluded because they are not required merely to bring the instance online.

The founding registry currently contains 20 Seats:

- Conscription: Recruiter;
- Curia: Seneschal, Chamberlain, and Secretary;
- Foundry: Artificer and Adversarial Reviewer;
- Garrison: Constable;
- Guildhall: Guildmaster and the Disciplinary Fit, Composition, and Boundary Challenge committee Seats;
- Hagiography: Sanctographer;
- Laboratorium: Alchemist;
- Senate: Lord Speaker, Bailiff, and the Consistency, Governance, Practice, and Security committee Seats; and
- Studium: Chancellor.

The lower-level package command remains available for an exceptional artifact-backed founding occupant or an inactive non-Seat operative. An `OFFICER` is bound to an initial Seat. An `OPERATIVE` is placed on an inactive internal roster and receives no deployment or execution authority from installation.

```json
{
  "schema": "imperium.operator-root-personnel-package/v2",
  "instance_id": "imperium-instance",
  "personnel": [
    {
      "personnel_type": "OFFICER",
      "office": "curia",
      "role": "seneschal",
      "seat": "curia.seneschal",
      "persona": { "id": "seneschal-persona", "version": "1.0.0" },
      "profile": { "id": "seneschal-profile", "version": "1.0.0" },
      "officer": { "id": "seneschal-officer", "version": "1.0.0" }
    },
    {
      "personnel_type": "OPERATIVE",
      "office": "hagiography",
      "role": "research-operative",
      "assignment_id": "founding-research-operative-1",
      "persona": { "id": "research-persona", "version": "1.0.0" },
      "profile": { "id": "research-profile", "version": "1.0.0" },
      "officer": { "id": "research-operative", "version": "1.0.0" }
    }
  ]
}
```

Install one or more packages while the instance remains pre-operational:

```console
bin/console imperium:operator:install-founding-personnel founding-personnel.json
```

When all necessary founding personnel are present, the Operator declares the instance operational:

```console
bin/console imperium:operator:declare-operational imperium-instance
```

Operationalization permanently closes operator-root installation and preserves the complete upgrade inventory. It does not force upgrades to begin. The Operator may test-drive the v0 instance first.

MasterMason remains Imperium's non-cognitive mechanical activation runtime. The primary activation command directs MasterMason to perform required-v0 installation, operationalization, and `CURIA_READY` runtime binding in one flow:

```console
bin/console imperium:activate imperium-instance
```

To prepare—but not start—the dependency-ordered upgrade plan during activation:

```console
bin/console imperium:activate imperium-instance --prepare-upgrades
```

Calling activation later with `--prepare-upgrades` prepares the same plan without reactivating or altering the running v0 instance. The first five planned upgrades are Adversarial Reviewer, Artificer, Constable, Alchemist, and Recruiter; remaining occupants follow deterministically. Plan preparation grants no upgrade execution authority.

Only MasterMason's obsolete self-constructing personnel sequence is retired. MasterMason itself and its legitimate mechanical lifecycle, verification, binding, recovery, and routing duties remain active.

Installation and operationalization do not grant mission execution, external-action authority, credentials, tool access, or spending authority.
