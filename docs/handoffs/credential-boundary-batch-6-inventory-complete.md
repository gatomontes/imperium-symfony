# Credential-boundary Batch 6 inventory complete

The remaining credential-bearing Symfony AI surface is now exhaustively classified in `docs/credential-boundary-agent-inventory.json` and guarded by `CredentialBoundaryAgentInventoryTest`.

## Exact inventory

| Cluster | Definitions | Direct gateways | Existing authority source | Migration decision |
| --- | ---: | ---: | --- | --- |
| Foundry | 2 | 4 | Exact construction case, specification/revision authority, review authority | New governance invocation activation bound to each existing stage; common broker mechanics |
| Resident requirements | 2 | 1 | Accepted Hagiography/Studium commission and resident occupancy | One claim per office resolution; no cross-office substitution |
| Section authorship | 2 | 1 | Exact accepted commission, assignment, office, and section contract | One claim per attributed section operation |
| Laboratorium | 1 | 1 | Profile-derivation acceptance and Imperator authorization | One derivation claim; preserve Persona identity and immutable scope |
| Senate profile examination | 9 | 5 | Stage-specific question, testimony, finding, reconciliation, and disposition authorities | Separate claim per jurisdiction and stage; never one omnibus Senate grant |
| Senate Persona confirmation | 10 | 3 | Proceeding question, witness, finding, and Lord Speaker disposition authorities | Separate claim per Seat/stage; preserve sterile-witness separation |
| Guildhall | 4 | 1 | Accepted commission, exact occupancy, committee checkpoint, and synthesis stage | One claim per committee Seat and one for Guildmaster; checkpoint-safe resume |
| Curia | 1 | 1 | Exact audience/deliberation state and mission input | Migrate last among internal clusters because it is the broadest planning entry point |
| La Cortine sortie | 1 | 1 | Exact sortie manifest, tool/capability scope, destination, and return contract | Separate perimeter lifecycle; do not reuse an internal governance claim as external authority |
| **Total** | **32** | **18 unique gateway classes / 34 definition-to-gateway bindings** |  |  |

Two credential-bearing platform definitions remain: `config/packages/ai.yaml` and the isolated `config/packages/sortie/ai.yaml`. The latter was not included in the earlier 31-agent main-runtime count. The system-wide number is therefore 32 remaining agents across nine clusters.

## Ordered batches

7. **Governance invocation substrate:** implement only the common mechanical request/Imperator decision/Clavium lease/durable claim/journal/envelope contracts. Each cluster must supply an exact existing authority reference and purpose; the substrate may not create or infer that authority.
8. **Foundry:** migrate specification, revision, ordinary review, and adversarial review; remove two definitions.
9. **Resident requirements:** migrate Sanctographer and Chancellor requirement resolution; remove two definitions.
10. **Section authorship:** migrate exact Sanctographer/Chancellor section authorship; remove two definitions.
11. **Laboratorium:** migrate Alchemist Profile elaboration; remove one definition.
12. **Senate profile examination:** migrate nine question/testimony/finding/reconciliation/disposition definitions with stage- and jurisdiction-specific claims.
13. **Senate Persona confirmation:** migrate ten question/witness/finding/disposition definitions without collapsing Seats or sterile-witness separation.
14. **Guildhall:** migrate three committee Seats and Guildmaster synthesis with checkpoint-safe at-most-once claims; remove four definitions.
15. **Curia:** migrate Seneschal cognition against the exact audience/deliberation state; remove one definition.
16. **La Cortine sortie:** separately broker sortie cognition and its external authority boundary; remove the sortie agent and isolated credential-bearing platform.
17. **Global removal and proof:** prove the inventory is empty, remove the main `%env(DEEPSEEK_API_KEY)%` platform, run the repeatable bypass demonstration, and retain private evidence plus a sanitized summary.

Every migration batch includes its hostile proof. No batch may remove a platform still referenced by another agent, reuse an operational/Delegate/Legate claim under a false semantic label, or claim the system-wide gate closed early.

## Next batch

Batch 7 creates the common governance invocation substrate only. It does not migrate a governance cluster. Its proof must show that absent, malformed, expired, mismatched, replayed, or cross-cluster authority fails before credential resolution and that the substrate cannot author its own activation authority.
