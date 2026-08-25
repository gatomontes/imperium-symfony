# Delegate Mission authority and consumption matrix

This is the canonical high-level authority map for the terminal 69-step Delegate Mission route. Detailed field-level rules remain in the named contracts. An authority opens only the next bounded act shown here; it grants no adjacent or continuing power.

| Corridor | Issuer or custodian | Exact bounded authority | Consumed by | Resulting Folium or state | Survives? |
| --- | --- | --- | --- | --- | --- |
| Capability demand | Curia | Present mission capability demand | Guildhall | Demand intake disposition | No |
| Personnel use | Curia / Imperator | Decide and accept one exact Persona commitment | Guildhall | Personnel-use acceptance | No |
| Persona custody | Garrison | Reserve one exact admitted Persona | Constable | Reserved custody | Until lawful release |
| Profile scope | Curia / Imperator | Authorize one exact mission Profile scope | Conscription | Derivation request | No |
| Profile derivation | Conscription | Derive one custody-bound Profile candidate | Laboratorium | Sealed candidate | No |
| Senate admission | Conscription / Lord Speaker / Bailiff | Assemble, admit, and examine one exact manifestation | Senate actors | Hearing opening | No |
| Questioning | Lord Speaker | Author, authorize, dispatch, and answer one jurisdiction question | Senator, Bailiff, witness | Question and testimony Folia | No |
| Findings and disposition | Lord Speaker | Produce independent findings, reconcile, and decide | Senators / Lord Speaker | Senate disposition | No |
| Profile approval | Senate evidence / Imperator | Approve one exact Profile for operational qualification | Conscription | Profile decision | No |
| Operational construction | Imperator / Conscription | Qualify, assemble, and bind one exact Delegate | Conscription | Generation-1 Codex and inert binding | No |
| Deployment | Seneschal | Authorize one exact bounded deployment | Garrison | Deployed custody and transition Folium | No |
| Runtime activation | Garrison evidence / Conscription | Activate one exact deployed binding | Conscription | Runtime activation | No |
| Mission control | Conscription / Seneschal | Accept and construct one bounded cognition commission | Curia | Sealed commission | No |
| Model governance | Curia / Imperator / Oracle | Authorize criteria, assess, recommend, and select | Augur / Seneschal | Selection and binding Folia | No |
| Provider access | Conscription / Clavium / Imperator | Attest, authorize, lease, and activate one exact invocation | Clavium / Citadel | Consumed lease and durable invocation claim | No |
| Cognition turn | Citadel | Perform one exact bounded provider turn | Brokered invoker | Response envelope and turn Folium | No |
| Result disposition | Citadel evidence / Seneschal | Accept, stop, or fail the exact result | Curia | Result disposition | No |
| Return and retirement | Seneschal | Execute only the predeclared return contract | Garrison | Restored custody, retired binding, terminal Folium | No |

## Consumption invariants

- Consumption is single-winner where authority is mutable.
- Exact replay requires the same authoritative-input fingerprint.
- Changed source identity, digest, actor, disposition, configuration, or authority is a conflict, not replay.
- Unknown provider outcome never grants automatic provider replay.
- Recovery authority can complete a sealed response into a missing turn but cannot invoke the provider.
- Terminal completion leaves no cognition, credential, provider, tool, execution, continuation, redeployment, or reuse authority.
