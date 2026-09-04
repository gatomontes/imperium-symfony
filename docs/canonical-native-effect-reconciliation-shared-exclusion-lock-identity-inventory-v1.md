# Canonical Native Effect Reconciliation Shared-Exclusion — lock identity inventory v1

`PREPARATION_BATCH_0_LOCK_INVENTORY_ONLY`
`PRODUCTION_CORRECTION_NOT_AUTHORIZED`

## Normalization law

`AtomicTransition(root)` stores locks below the constructor's resolved project
root at `var/imperium/runtime/transition-locks/{sha256(scope)}.lock`. The scope
bytes are hashed exactly as supplied; there is no scope case folding. Accepted
scopes match `[a-z0-9][a-z0-9._:-]{2,180}`. `NativeState` resolves `root` with
`realpath`; Windows storage identity lowercases slash-normalized paths, while
Linux preserves case. `TransitionStore` instead locks the literal
`{realpath(event-directory)}/domain.lock`.

## Exact identities, stores and writers

| Owner | Exact scope / lock | Protected store and writer | Order / nesting | Classification |
| --- | --- | --- | --- | --- |
| `NativeState::locked()` | `native-provider-transition` | all native principal, activation, revocation, authority and transition writer operations | first | `SHARED_EXCLUSION_PROVED` |
| `NativeState::locked()` | `immutable:36afe4d209c502c47e3a8b0484062c09c4534bb0ede4da2a6292dc4ca34262a0` | `var/imperium/runtime/imperator-principal-versions`; `FutureInstanceImperatorPrincipalConstitutionService` | sorted position 1 | `SHARED_EXCLUSION_PROVED` |
| same | `immutable:40ad85b579790ecf34ebb7ffe50cda41e961a45d212f34148e8d2ea6ea510eed` | provider execution boundaries | sorted position 2 | `SHARED_EXCLUSION_PROVED` |
| same | `immutable:593db5d3c6667621b600b0327c10997bd1a1bbb04e7822e51244961ddc7a92ed` | assurance admissions | sorted position 3 | `SHARED_EXCLUSION_PROVED` |
| same | `immutable:87d4de4b264b786ece0b34b2d8c50da314bfd8c248573813a773eca33df31fab` | `var/imperium/evidence/imperator-principal-provenance/lifecycle-dispositions`; `ImperatorPrincipalProvenanceFixtureStore::putLifecycleDisposition()` | sorted position 4 | `SHARED_EXCLUSION_PROVED` |
| same | `immutable:8a9e27d70b6a1f05aca38c54cdb4e6dcea9512d0e45b66804cb0318c591630ec` | provider implementation bindings | sorted position 5 | `SHARED_EXCLUSION_PROVED` |
| same | `immutable:c41692d270c3520d5324e51fb5eae088eeae27b1ddf43bdd2f8a32b776463c51` | provider executor activations | sorted position 6 | `SHARED_EXCLUSION_PROVED` |
| same | `immutable:eecf16f12300a0ffe2d770a587cfa5f9faec88adbfcbbc97f4f797b21241e7f7` | provider executor attestations | sorted position 7 | `SHARED_EXCLUSION_PROVED` |
| same | `immutable:fb042ab9d8724b127294c15cc58676bd92af7678ed165964bdecdc02244c62f1` | `var/imperium/operator-root/transition-trust` | sorted position 8; no accepted runtime Root writer | `DEFERRED_BOUNDARY` |
| same | `immutable:fc16d282efc70c54a93dc3920a087dec709b4d9dc80a05b0e3896e88b22ae8cc` | principal-activation provenance productions | sorted position 9 | `SHARED_EXCLUSION_PROVED` |
| `NativeState::put()` | `{realpath(root/native-provider-transition/{kind}/{id})}/domain.lock` | one event commit through `TransitionStore`; `NativePrincipal`, `NativeAuthority`, `NativeConsumer` | after all NativeState locks | `SHARED_EXCLUSION_PROVED` |
| current reconciliation issuer | `canonical-native-effect-reconciliation-issuance:{sha256(authorityId)}` | deterministic reconciliation authority then issuance evidence | target lock, then immutable directory locks | `ORDERING_HAZARD` |
| current claim derivation | `canonical-native-effect-reconciliation-authority:{sha256(authorityId)}` | capability consume and deterministic claim publication | target lock, then claims immutable lock | `DISJOINT_LOCK_RACE_REPRODUCED` |
| generic consumption | `authority:{sha256(authorityId)}` | `authority-consumptions`, then `immutable:{sha256(directory)}` | consumer-specific outer then directory | `SHARED_EXCLUSION_PROVED` |
| forward recovery | `canonical-native-effect-continuation:{sha256(admissionId)}` then `canonical-native-effect-forward-recovery:{sha256(claimId)}` | claim inspection/consumption and receipt publication | explicit outer-to-inner pair | `SHARED_EXCLUSION_PROVED` |

`NativeState` rejects reentry on the same object with `NIR_NESTED_LOCK`;
`TransitionStore` rejects reentry with `EAT_NESTED_LOCK_REFUSED`.
`AtomicTransition` has no process-local reentrancy detector: reacquiring the same
scope through a second handle is an unsafe blocking acquisition. Target-wide
issuance/claim serialization is not shared mutation exclusion merely because it
also uses `flock`.

The observed repair boundary is one direction only: acquire the shared native
exclusion before any semantic-target lock. A path holding a target lock must not
enter `NativeState::locked()`, and no external I/O may occur under either lock.
