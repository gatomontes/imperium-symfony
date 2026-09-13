# Provider production composition — source assessment

Source-inspected on 2026-09-13 at O5 closure tree `456b25ef205a96b3793ea9a00920714af5bf86ec`, GitHub main `427aa850237dfe5caac1b887c194be1484e07773`. This assessment used committed source and prior accepted evidence. No installation, private state, credential, account or live provider was inspected. No new runtime tests were executed for this documentation preparation.

## Observed composition and missing ports

Paths below are relative to `src/Imperium/Runtime/Onboarding/` unless otherwise stated. “Missing” describes this repository composition, not a claim that an external deployment has no such system.

| Boundary | Observed source behavior | Production work or input still required |
| --- | --- | --- |
| `Console/DeploymentGateway.php` | Default alias reconstructs an existing-store reader and passes no runtime to `FixedGateway`. | Explicit dormant deployment composition; preserve pure observation and default refusal. |
| `Console/FixedGateway.php`, `DeepSeek/Runtime.php` | Runtime must share the exact `AuthorityStore` instance. Runtime is excluded from service discovery. | One coherent infrastructure object graph; no request-selected infrastructure. |
| `DeepSeek/KeySource.php` | Public generation and callback-only key delivery interface; repository implementations found in test fixtures only. | Production custody contract/source, rotation semantics and provisioning ownership. Real key bytes remain outside public artifacts. |
| `DeepSeek/AccessAdapter.php`, `DeepSeek/EvidenceVerifier.php` | Adapter verifies a fixed grant and requires delivery to use the same key-source instance. `MissingEvidence` refuses. | Competent current account/access evidence bound to grant/configuration/credential. Signing an assertion alone is insufficient. |
| `Augur/BaseProjection.php`, `Augur/BaseEvidence.php` | Projection delegates substantive verification to a fixed port; default `MissingBaseEvidence` refuses. | Authenticated base eligibility, source facts and exact approved mapping; test pins are synthetic. |
| `Augur/FreshProducer.php`, `Augur/ConstitutionEvidence.php` | Checks root ownership/vacancy, founding terms, exact current/frozen base and constitutional artifact references. Default evidence refuses. | Competent constitution producer and verification of actual charter/persona/profile originals; no implicit appointment from composition. |
| `Augur/CognitionEvidence.php` | `resources()` derives exact-wire bounds; `response()` checks substantive claims. `MissingCognitionEvidence` refuses. | Verified resource source and claim semantics for exact originals/model/account. |
| `DeepSeek/TokenEvidence.php`, `DeepSeek/Tariff.php` | Validate bounded projections; they do not obtain authoritative tokenizer or price facts. | Supportable tokenizer/framing/context and current exact tariff evidence. These were not obtained by this preparation. |
| `Assignment/AssignmentEvidence.php` | Requires substantive Profile and current exact binding/Profile generations without dispatch or nested journal locking. Default evidence refuses. | Fixed verifier backed by competent current sources, shared with actual persistent resolution. |
| `DeepSeek/EnvelopeStore.php` | Existing immutable bounded response store; requires an existing absolute directory and rejects unsafe paths. | Deployment-owned storage, protection and lifecycle choice; no replacement store is inherently required. |
| `AuthorityAdmission/AuthorityStore.php` and existing enrollment/admission owners | Deployment supplies identity/root/clock/source; originals and current authority remain governed by existing owners. | Genuine deployment-specific authority package under separate authorization. Factory construction supplies none of this authority. |

Search of the onboarding source and support fixtures found only `Missing*` production implementations for the five evidence interfaces and synthetic support implementations for successful paths. This is the concrete basis for selecting [PPC0](next-campaign-provider-production-composition.md). It is not an exhaustive audit of every unrelated institutional subsystem; PPC0 must identify whether an existing competent producer can satisfy each port before implementing an adapter.

## Inputs that must remain unresolved until supplied

| Input | Current disposition | Needed before |
| --- | --- | --- |
| Deployment root, instance/citadel/operator identity, current source and custodian | Not observed or selected here; Windows development and Ubuntu deployment are separate environments. | Real provisioning/commissioning proposal |
| Key custody backend/path, access controls, rotation/provisioning owner | No real backend/path/key inspected. Local implementation may define an explicit bounded contract and test it with disposable secrets. | Real credential provisioning |
| Genuine source originals and competent producer identities for the five evidence ports | No production evidence supplied by this preparation. | Affirmative production verification |
| Account/model-specific tokenizer, framing and tariff evidence | Not established; existing projection types remain the constraints. | Any real cognition dispatch |
| Deployment approval, bounded operation authorization and accepted unresolved risks | Not granted by “next”; previous live deferral remains. | Real installation changes or provider activity |
| Native institutional enrollment | `DEFER_ENROLLMENT` retained from the accepted owner disposition. | Only a separately revisited owner decision can change it |

The successful O5 mock journey proves integration through synthetic ports. It does not satisfy these inputs. Conversely, missing real inputs need not prevent implementation and review of a dormant source-backed composition. Report unsupported paths explicitly rather than making every path succeed with trusted-looking fixture data.
