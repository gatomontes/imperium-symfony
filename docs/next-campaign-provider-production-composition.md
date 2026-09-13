# PPC0 — provider production composition

Disposition: **SELECTED_OFFLINE_IMPLEMENTATION**. The owner requested “next” after O5 closure. Prepare production composition and supported evidence adapters in one isolated local run. This selects new engineering work outside the closed O0–O5 campaign; it does not authorize live commissioning or installation changes.

## Entry and concrete problem

Start from this preparation on main, descending from O5 documentation closure `427aa850237dfe5caac1b887c194be1484e07773`, tree `456b25ef205a96b3793ea9a00920714af5bf86ec`. The accepted runtime tree is `cea21ff41ea7b74ff08a1901b104c523c013fe5a`, integrated through [PR #806](https://github.com/gatomontes/imperium-symfony/pull/806). Its complete CI passed 3,705 tests / 63,941 assertions / four skips across all 608 files. These are historical baseline results, not tests of PPC0.

O5 exposes the complete offline CLI, but `DeploymentGateway` constructs `FixedGateway` without a runtime. `Runtime` and the concrete adapters are excluded from automatic service discovery. The source has no concrete production `KeySource`; the five evidence interfaces have refusing defaults and synthetic test implementations. Attaching a runtime alone cannot supply genuine evidence. See the [source assessment](provider-production-composition-source-map.md), [O5 closure](handoffs/provider-onboarding-o5-complete.md) and [local handoff](handoffs/provider-production-composition-ready.md).

The deliverable is a dormant, explicit deployment composition with evidence adapters only where substantive source verification can be supported, an offline integration proof, and an exact list of unresolved production inputs. Do not turn the absence of a competent source into a successful verifier.

## Scope and preserved decisions

- Preserve DeepSeek/API key, FRESH, D2-A, approved P1–P9, medium target capacity, least-cost eligible initial Augur base and persistent operator-controlled whole-set settings. Preserve the frozen onboarding contracts and the empty actual retry allowlist.
- Keep `DEFER_ENROLLMENT`. The accepted [native commissioning disposition](citadel-commissioning-preparation-acceptance.md) remains separate. Neither an onboarding composition nor a deployment credential source authorizes native enrollment, appointments, provider requests, installation cutover, activation or mission execution.
- Work in a fresh Windows development/test worktree. Describe the intended Ubuntu VPS deployment separately; no installed root, account, custodian, path or operating-system permission has been observed by this preparation. Do not access either real installation.
- Production composition must be fixed by deployment infrastructure, dormant by default and unavailable through request-selected roots, class names, verifiers, credentials or provider destinations. Keep the three canonical commands and their exact public input/output shapes.
- Preview and status must retain their existing physically read-only behavior. They must not instantiate a credential-bearing composition or invoke even credential-generation inspection. Evidence-only resume must never dispatch.
- No live HTTP, real key generation/use, private-state copying, dependency update, service reconfiguration or installation mutation. All execution uses generated temporary roots, ephemeral fixture keys, synthetic institutional originals and exact mock HTTP. Test facts remain labelled synthetic.

## One local implementation run

| Work | Required output |
| --- | --- |
| Resolve source ownership | Extend the supplied source assessment with each candidate native producer/consumer, exact authentication/currentness predicates and whether the existing contract can represent its evidence. Do this as part of implementation, without restarting a separate planning campaign. |
| Fixed composition | Implement the smallest dormant infrastructure factory/composition that supplies one identity-matched `AuthorityStore`, one `KeySource`, the existing adapters, `FreshProducer`, `EnvelopeStore`, `AssignmentEvidence` and `Runtime` to the existing gateway boundary. Preserve the public API and existing default reader. |
| Credential custody | Implement a bounded infrastructure-owned source only against a documented custody contract. Separate public rotation generation from private key bytes; ensure the same source instance verifies and delivers, and that rotation invalidates outstanding capability use. Real storage location/provisioning and custodian approval remain deployment inputs. |
| Evidence adapters | Reuse competent repository owners where their actual outputs satisfy the existing interfaces. Authenticate substantive account/access, base eligibility, constitutional provenance/resident artifacts, exact cognition resources/claims and current assignment/profile predicates. No hash-only, signature-only, boolean approval or copied fixture implementation may stand in for factual competence. |
| Integration and operator handoff | Exercise the supported composition offline, publish a dependency/blocker matrix and write the exact future provisioning/commissioning order. Distinguish implemented components, simulated evidence, missing owner decisions and genuinely unsupported producers. |

Do not manufacture an authority domain to close a missing port. If an existing source cannot support a predicate, retain a named refusal and document the smallest missing producer/interface with a concrete counterexample. Complete the independently useful composition and supported adapters; report **PARTIAL_BLOCKED** for the unsupported path. Never claim **PRODUCTION_READY** merely because the mock journey passes. Do not broaden this run into personnel, native enrollment or general lifecycle migration.

The constructor graphs and prior tests are integration references. In particular, `AssignmentFixture`, `AugurFreshFixture`, `SyntheticAugurBaseEvidence` and `SyntheticAssignmentCognitionEvidence` are test-only evidence, not implementations to promote to production. New production classes must not depend on `tests/`, fixtures or a synthetic-success switch.

## Exact evidence requirements

The [source assessment](provider-production-composition-source-map.md) identifies each port. For every affirmative predicate, report the original producer, authority scope, retained original, identity/account/model/credential binding, validity/current generation, and how conflicting, stale or missing evidence refuses. Existing admission authenticates originals within its own scope; it does not establish every external fact asserted in their bodies.

For cognition, `TokenEvidence` and `Tariff` are verifier projections. Require supportable bounds for the exact request bytes, exact model/account, tokenizer and framing, context capacity, output ceiling, price rates, rounding and fees. A character estimator, historic public price or self-declared token count is insufficient. Consult current primary provider documentation only if selecting a real provider-specific implementation; retain dated public references and do not treat documentation as account-specific proof. If no supported resource evidence exists, keep dispatch blocked.

Constitutional evidence must establish the competent founding provenance and exact resident charter/persona/profile artifacts. Assignment evidence must establish substantive Profile predicates and current exact binding/Profile generations. Preserve FRESH root vacancy/ownership, current-authority checks and persistent-settings revalidation; an applied receipt is historical evidence, not continuing permission.

Response storage uses the existing `EnvelopeStore`; document its directory ownership, publication/locking assumptions, confidentiality and honest crash/durability limits. Do not add silent cleanup or recovery of unknown effects. Unknown dispatch keeps maximum exposure; resume recognizes retained evidence without retry/refund.

## Validation and review packet

Run meaningful focused tests for the changed custody/composition/evidence boundaries and the existing O5 journey. Include missing sources, foreign owner/source identity, credential rotation between checkpoints, forged-but-well-formed evidence, stale/conflicting originals, unsupported tokenizer/tariff, profile change, unknown dispatch and fresh-process recovery. Prove preview/status touch no key/generation/network and create no files; preserve exact envelopes and reason-code closure. Test Windows path/locking behavior and record the actual host used; a Linux result does not prove Windows behavior.

Do not weaken provenance pins, frozen contracts or accepted ledgers to make tests pass. `AtomicTransition.php` and `config/services.yaml` have historical pins; prefer an additive composition with the existing owner paths. Any necessary incompatible change must be reported as a concrete contract conflict, not hidden in a test adjustment.

Commit executable changes before their final gates. Use the unchanged complete eight-partition PHPUnit gate, aggregate exact-source/case coverage and 11 guard checks before integration. Do not revive the replaced 1,800-second serial gate. Retain native exits, UTC times, counts, warnings/skips, diagnostic failures and source identity. Documentation-only follow-up changes must be identified separately from tested code.

Return `docs/handoffs/provider-production-composition-report.md`, a component/evidence matrix, focused test results and an exact future operator runbook. Package public-only source/evidence with tested/final commit/tree, exact diffs, source ZIP and path/byte/hash manifest, a bounded Git bundle with prerequisites and SHA-256 verification instructions. Never package real credentials, private installation state or raw sensitive environment values. Stop at local commits for source review and complete CI before implementation publication/integration.

Exit is **OFFLINE_COMPOSITION_REVIEWABLE** if supported paths are complete, or **PARTIAL_BLOCKED** with precise unsupported predicates and owner inputs. Neither means live readiness. Keep `deployment_approved`, `enrollment_authorized`, `live_ready`, `activation` and `execution_authority` false.

## Subsequent flow

PPC0 local implementation → source review and complete CI → accepted integration → resolve exact deployment/evidence inputs → separately authorized bounded commissioning → first bounded Castellan interview when its own authority and prerequisites are met. Existing-installation cutover remains a separate decision. O0–O5 have zero remaining planned batches; PPC0 is one newly selected implementation run, not a reopening of O5.
