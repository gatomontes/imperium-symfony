# Provider Binding Activation Integrity Remediation contracts

## Status

`BATCH_1_AUTHORITY_EMPTY_CONTRACTS_COMPLETE_NO_IMPLEMENTATION`

Five separately versioned contracts define evidence and disposition vocabulary without producing,
consuming, repairing, quarantining or retiring any runtime artifact:

1. `ActivationPrincipalProvenanceEvidenceContract` records whether the exact activation-capable
   Imperator principal has a competent installation source and lifecycle.
2. `ActivationTransitionInterruptionEvidenceContract` distinguishes three exact crash cuts for the
   Batch 2 decision and issuance transitions and records convergence, refusal, conflict or absence
   of proof.
3. `StrandedActivationArtifactDispositionContract` permits a future competent decision to describe
   quarantine or corridor retirement without mutating, consuming or retroactively revoking the
   immutable authority or lease.
4. `CredentialReferenceExposureObservationContract` observes authorized readers, lifetime,
   copying, logs, exceptions, dumps and durable persistence without storing a clear credential
   reference or secret.
5. `ProcessLossCapabilityCustodyEvidenceContract` records an offline issuer-process cut and restart
   observation without reconstructing a capability, persisting its credential reference, resolving
   credentials or performing external I/O.

## Exact postures

| Contract | Producer posture | Consumers | Authority-empty invariant |
| --- | --- | --- | --- |
| Principal provenance evidence | `imperator.activation-principal-provenance-observer` | Activation-corridor disposition | Cannot install a principal, grant activation authority, issue caller authority or activate a binding. |
| Interruption evidence | `imperator.offline-activation-transition-interruption-demonstration` | Disposition and read-only reconstruction | Cannot consume live authority, create a live decision, issue activation authority or authorize retry. |
| Stranded-artifact disposition | `imperator.activation-corridor-disposition` | Artifact reconstruction and future platform selection gate | Cannot mutate, consume or retroactively revoke an artifact or create successor authority. |
| Credential-reference observation | `clavium.offline-credential-reference-exposure-observer` | Boundary hardening and disposition | Cannot read a live credential, resolve it, change the boundary or issue a capability. |
| Process-loss custody evidence | `clavium.offline-process-loss-custody-demonstration` | Disposition and future platform selection gate | Cannot issue, transfer or reconstruct a capability, resolve credentials or authorize a platform. |

Every `NON_AUTHORITIES` value is false. Contract existence grants no authority. No producer,
consumer, principal installer, recovery service, quarantine transition, credential hardening change
or subprocess harness is implemented.

## Preserved perimeter

The terminal custody refusal remains authoritative. No activation artifact is consumed, replaced,
mutated, quarantined or retired. No capability is issued, reconstructed, transferred, delivered,
consumed or resolved. No credential reference or secret is persisted or disclosed. No credential
platform is selected, no command is migrated, no provider is invoked, no external I/O occurs, and
Iron Gate and Lazaretto remain closed. Provider Execution Assurance remains paused.
