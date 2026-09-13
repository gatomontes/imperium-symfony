# O5-B0 provider onboarding CLI

This implements the offline CLI journey on accepted O4 (`b4e6e8c57f729c1e1e49284207f34f8909276e0b`). It supersedes the implementation hold in the earlier O5 preparation: the operator accepted O4's complete parallel gate and then instructed O5 to proceed. The canonical [onboarding](../contracts/provider-onboarding.md) and [continuation](../contracts/provider-onboarding-continuation.md) schemas remain unchanged.

```sh
php bin/console imperium:provider:onboard public-request.json
php bin/console imperium:provider:onboard public-request.json --format=json
php bin/console imperium:provider:status sequence-test --format=json
php bin/console imperium:provider:resume public-resume.json --format=json
```

`mode` in the exact v2 onboarding request chooses preview or one advance. There is no `--preview` switch. Human output is the default; JSON stdout contains one exact status envelope. Diagnostics use a closed public reason vocabulary on stderr. Formatting is outside request identity. The existing domain-specific validator remains authoritative for IDs, including its lowercase, 8–80 character identity rule; O5 does not broaden it to the older generic contract paragraph.

The deployment supplies root, clock and infrastructure. Public files cannot supply keys, root overrides, arbitrary adapter choices, enrollment, activation, force, reset or retry switches. The repository's default `DeploymentGateway` observes the existing project root and refuses advances without a separately commissioned producer composition. `FixedGateway` accepts an identity-matched existing `AuthorityStore` and `DeepSeek\Runtime`; the runtime now takes optional existing `AssignmentEvidence` so all advances, including assignment publication, go through one ledger/custody composition. This is an offline integration boundary, not a production account/tokenizer/constitution implementation or live commissioning.

## Observations and effects

Status and preview take a shared lock on the **existing** formation transition lock and validate the journal chain. They never create the root, directories, locks, generations or migrations. The original exclusive writer path remains unchanged. Neither observation path has an adapter, key source, envelope store or producer port. Preview validates public head, predecessor, identity, policy, frozen evidence, graph readiness and authority through existing owners; dynamic custody, native producer and resource checks still occur during an explicit advance.

The seven fact dimensions are projected independently from retained originals. An opaque credential binding is not proof of credential presence. Presence is shown only as a historical observation backed by a consumed custody checkpoint; status does not call even `KeySource::generation()`. Existing public owners do not retain a separate affirmative missing-key observation, so O5 does not manufacture `missing` from an unobserved key. Authentication requires original settled access evidence. Assessment results require original group lineage; assignment proposals use the O4 whole-set derivation and application facts use validated receipt history.

All reported facts are observations, not invocation authority. An applied receipt survives expiry in `facts.assignment`, while expired policy, public completion or holder prerequisites prevent a fresh-use success claim. Actual use still goes through `PersistentSettings` and its current producer, credential-generation and profile checks. The initial Augur binding is explained separately from proposed Courtthane/formation Locksmith settings.

Effects contain original claim and application references. Exposure is settled usage plus maximum unresolved reservations; unverifiable state yields null, never a fabricated zero. Preview, status, identical replay and resume report no new effects. A delivery exception after claim publication is re-observed as a retained outcome, preserving its command identity and exposure. A validated response awaiting retained completion is `RESULT_PENDING`; uncertain dispatch is `OUTCOME_UNKNOWN`. Resume delegates to the existing recognition/reconciliation owner and never calls advance or dispatch.

| Status | Exit |
| --- | --- |
| ASSIGNMENT_APPLIED | 0 |
| REFUSED | 1 |
| CONFIGURED, MISSING_CREDENTIAL, AUTHENTICATION_PENDING, BASE_SELECTED, MISSING_AUGUR_AUTHORITY, ASSESSMENT_AUTHORIZED, RESULT_PENDING | 2 |
| OUTCOME_UNKNOWN | 3 |

Integrity/conflict/terminal failures take precedence, followed by unknown effects, valid application history, and unmet prerequisites. An absent sequence is `REFUSED / SEQUENCE_NOT_FOUND`. Unknown exception payloads are replaced with a bounded reason, and PHP warnings cannot spill into JSON stdout. All five operational flags remain false; the retry allowlist remains empty.

## Offline acceptance

`ProviderOnboardingConsoleTest` exercises actual command parsers and renderers with the accepted O1–O4 owners and explicitly synthetic provider/constitution/evidence ports. It covers registration; configuration and evidence; access; base selection and mapping; FRESH Augur founding; W1/W2/W3; preview of the whole assignment set; atomic application; persistent resolution; explicit authorized replacement; identical replay; stale prerequisites; interrupted dispatch; evidence-only resume; exact JSON/human exit behavior; secret redaction; and absent-store/preview purity.

The fresh-process worker reconstructs the deployment reader without credential or adapter dependencies and observes the same durable status without changing any file bytes. Test-only fixture extensions add a credential-generation sentinel and optional assignment-capable runtime composition; their prior defaults remain intact. The standard complete eight-partition CI and aggregate enumeration/source guard are unchanged. Acceptance is recorded in the O5 handoff after that gate finishes.

Live commissioning, existing-installation cutover, personnel appointment and invocation remain separately deferred.
