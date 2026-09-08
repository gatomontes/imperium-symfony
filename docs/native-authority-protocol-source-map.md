# P0–P3 source and consumer map

NativeTrust owns separate enrollment/signature checks; NativeJournal owns the one-frame commit; NativeProtocol owns roster, revocation, revision and admission effects; NativeAdmission builds complete custody/disposition evidence. NativeAuthorityCommand and NativeAuthorityEnrollmentCommand use actual Symfony DI. NativeServices is the fallback factory for direct legacy service construction. Formation adapters, their trust and CF02 retained-publication verification remain unchanged.

NA-IR01/02 correction changes only NativeProtocol in production: `custody()` supplies one validated native/retained-legacy set to fresh admission and inventory; `apply`/`admit` pass one locked acceptance instant through currentness and interval checks and retained timestamps. `resolve`/`inventory` bind checks and output to one observation instant. Existing lock, command, enrollment, cryptographic verifier, journal and admission record-builder implementations remain unchanged.

All paths below acquire the shared NativeBoundary lock. StateStore locked uses the same ordering before its bootstrap lock. Canonical admission and inventory response route to the native implementation when enrolled; all other listed legacy operations refuse after enrollment. Original method bodies are retained behind the wrappers. The retired ProfileElaborationSmokeService is not a supported production entry. Dynamic Formation witness readers continue to reject the native representation.

| Source | Entry points | Enrolled behavior |
| --- | --- | --- |
| src/Bootstrap/StateStore.php | read, write, locked (shared lock, structural exporters remain allowed) | Read/write fenced; locked observation still available |
| src/Imperium/Runtime/Conscription/ArtificerConscriptionService.php | fulfill | Explicit NAT002 refusal |
| src/Imperium/Runtime/Conscription/AuthorshipResidentConscriptionService.php | fulfill | Explicit NAT002 refusal |
| src/Imperium/Runtime/Conscription/ConstableConscriptionService.php | fulfill | Explicit NAT002 refusal |
| src/Imperium/Runtime/Conscription/DelegateMissionExaminationManifestationAssemblyService.php | assemble | Explicit NAT002 refusal |
| src/Imperium/Runtime/Conscription/DelegateMissionExaminationPreparationHandoffService.php | prepare | Explicit NAT002 refusal |
| src/Imperium/Runtime/Conscription/DelegateMissionModelBindingSealingService.php | seal | Explicit NAT002 refusal |
| src/Imperium/Runtime/Conscription/DelegateMissionOperationalManifestationAssemblyService.php | assemble | Explicit NAT002 refusal |
| src/Imperium/Runtime/Conscription/DelegateMissionOperationalProfileQualificationService.php | qualify | Explicit NAT002 refusal |
| src/Imperium/Runtime/Conscription/DelegateMissionProfileCandidateIntakeDispositionService.php | decide | Explicit NAT002 refusal |
| src/Imperium/Runtime/Conscription/DelegateMissionProfileDerivationCommissionRequestService.php | decide | Explicit NAT002 refusal |
| src/Imperium/Runtime/Conscription/DelegateMissionRuntimeActivationService.php | activate | Explicit NAT002 refusal |
| src/Imperium/Runtime/Conscription/ExaminationAssemblyAuthorizationRequestService.php | request | Explicit NAT002 refusal |
| src/Imperium/Runtime/Conscription/ExaminationManifestationAssemblyService.php | assemble | Explicit NAT002 refusal |
| src/Imperium/Runtime/Conscription/GuildhallConscriptionService.php | fulfill | Explicit NAT002 refusal |
| src/Imperium/Runtime/Conscription/LaboratoriumProfileDerivationCommissionService.php | commission | Explicit NAT002 refusal |
| src/Imperium/Runtime/Conscription/LegateRuntimeActivationService.php | activate | Explicit NAT002 refusal |
| src/Imperium/Runtime/Conscription/ModelBoundOperationalManifestationAssemblyService.php | assemble | Explicit NAT002 refusal |
| src/Imperium/Runtime/Conscription/ModelBoundOperationalManifestationSeatBindingService.php | bind | Explicit NAT002 refusal |
| src/Imperium/Runtime/Conscription/ModelBoundOperationalProfileQualificationService.php | qualify | Explicit NAT002 refusal |
| src/Imperium/Runtime/Conscription/OperationalManifestationAssemblyService.php | assemble | Explicit NAT002 refusal |
| src/Imperium/Runtime/Conscription/OperationalManifestationSeatBindingService.php | bind | Explicit NAT002 refusal |
| src/Imperium/Runtime/Conscription/OperationalProfileQualificationService.php | qualify | Explicit NAT002 refusal |
| src/Imperium/Runtime/Conscription/ProfileCandidateReturnAcceptanceService.php | accept | Explicit NAT002 refusal |
| src/Imperium/Runtime/Conscription/ProfileDerivationAuthorizationAcceptanceService.php | accept | Explicit NAT002 refusal |
| src/Imperium/Runtime/Garrison/AdversarialReviewerBootstrapSeedAdmissionIntakeService.php | inspect | Explicit NAT002 refusal |
| src/Imperium/Runtime/Garrison/ConstableSeatBindingService.php | bind | Explicit NAT002 refusal |
| src/Imperium/Runtime/Garrison/DelegateMissionOperationalCustodyTransitionService.php | transition | Explicit NAT002 refusal |
| src/Imperium/Runtime/Garrison/DelegateMissionPersonaReservationDispositionService.php | decide | Explicit NAT002 refusal |
| src/Imperium/Runtime/Garrison/DelegateMissionTerminalReturnService.php | complete | Explicit NAT002 refusal |
| src/Imperium/Runtime/Garrison/GarrisonInventoryInquiryService.php | route | Explicit NAT002 refusal |
| src/Imperium/Runtime/Garrison/GarrisonInventoryResponseService.php | respond | Native protocol route |
| src/Imperium/Runtime/Garrison/OperationalCustodyTransitionService.php | transition | Explicit NAT002 refusal |
| src/Imperium/Runtime/Garrison/OperationalReturnRetirementService.php | complete | Explicit NAT002 refusal |
| src/Imperium/Runtime/Garrison/PersonaReservationDispositionService.php | decide | Explicit NAT002 refusal |
| src/Imperium/Runtime/Garrison/ProfileDerivationHandoffDispositionService.php | decide | Explicit NAT002 refusal |
| src/Imperium/Runtime/Garrison/SubordinatePersonaAdmissionIntakeService.php | inspect | Explicit NAT002 refusal |
| src/Imperium/Runtime/Garrison/SubordinatePersonaCanonicalAdmissionService.php | admit | Native protocol route |

This boundary does not authenticate unmanaged writers or old binaries. Future deployment must approve the explicit legacy fence and protected custody, quiesce unsupported processes and prevent direct administrative replacement. Native custody stays in the aggregate store; old readers do not silently read a second ACTIVE file or an overlay. Garrison succession, legacy Guildhall transport of native inventory, and broader lifecycle migration remain unsupported. The complete original-byte collector and existing A0 projection/refusal interfaces remain unchanged.
