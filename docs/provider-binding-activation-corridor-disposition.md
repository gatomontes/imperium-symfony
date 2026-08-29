# Provider Binding Activation Integrity Remediation — activation-corridor disposition

## Result

`CORRIDOR_DISPOSITION_REFUSED_PRINCIPAL_PROVENANCE_ABSENT`

The completed evidence chain does not permit a competent corridor decision. Preparation proved
that no runtime producer installs an Imperator principal bearing
`provider_binding_activation_authority`, and no later batch created that missing provenance.
Interruption recovery now converges, expired unused artifacts can be quarantined exactly, clear
credential-reference exposure is reduced, and real process loss proves process-local possession is
not transferable custody. None of those facts appoints a disposition owner.

Creating `QUARANTINED_PENDING_REMEDIATION` or `RETIRE_CORRIDOR` through a deterministic service would
therefore make the service impersonate Imperator. Batch 6 refuses that move. No new disposition
record is sealed, no existing disposition or activation artifact is mutated, and no successor
authority is created. The corridor remains quarantined by campaign policy and operationally
unusable under `REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE`.

## Evidence disposition

| Evidence | Established result | What it cannot authorize |
| --- | --- | --- |
| Principal provenance | `ABSENT` | No competent runtime disposition owner exists. |
| Transition interruption | Six cuts converge or refuse safely. | Recovery cannot appoint Imperator. |
| Stranded artifacts | Exact expired-unused artifacts may be quarantined immutably. | Expiry cannot retire the corridor or create a successor. |
| Credential-reference boundary | Generic readers and records retain only the digest. | Secret exclusion cannot create custody. |
| Process loss | `POSSESSION_LOST` across distinct processes. | Failure evidence cannot select a credential platform. |

## Terminal perimeter

This campaign ends without activating a binding, mutating or consuming an activation artifact,
issuing or reconstructing a capability, persisting or disclosing a credential reference or secret,
selecting a credential platform, migrating the command, resolving credentials, invoking a
provider, performing external I/O, or opening Iron Gate or Lazaretto. Provider Execution Assurance
remains paused.

A principal-provenance remediation or an operator-governed retirement route requires a separately
selected campaign. This result grants neither one.
