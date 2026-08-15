# Operator-root founding installation

Operator-root installation is the temporary pre-operational mechanism for supplying Imperium's initial internal personnel. It applies to every Office, not to a privileged list of founding Seats.

The Operator may supply arbitrary initial Persona, Profile, and Officer artifacts. An `OFFICER` is bound to an initial Seat. An `OPERATIVE` is placed on an inactive internal roster and receives no deployment or execution authority from installation.

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

Operationalization permanently closes operator-root installation and creates the complete first-order upgrade docket. Every root-installed constituent is then reassessed, upgraded, replaced, restricted, or retired through ordinary Imperium governance with its original versions, digests, placement, and `OPERATOR_ROOT_INSTALLATION` lineage preserved.

Installation and operationalization do not grant mission execution, external-action authority, credentials, tool access, or spending authority.
