# O4-B0 R1: bind the actual formation consumer

Baseline: `d3fe18700188970bf41117d4cbc6d9a00d802e1d`, tree `3ec05806baf1f379c7f29322816671dda2e93bb2`. The R1 review supersedes the earlier consumer-completeness claim. Comparing signed terms with a resolved tuple did not establish which aggregate or Profile was executing. The supplied regression remains byte-for-byte unchanged.

## Typed original mapping

The O4 policy target `profile_ref` is an H reference to an admitted `imperium.bootstrap-source/v1` of kind `formation-profile-mapping`. Its canonical JSON content has exactly:

| Field | Meaning and comparison |
| --- | --- |
| `schema` | `imperium.formation-profile-mapping/v1` |
| `instance_id` | O4 deployment instance, equal to the native parent instance established by genuine institutional delegation |
| `formation_citadel_id` | Native formation scope in the shared frame; explicitly bridges the O4 H namespace and native scope namespace |
| `seat` | Exact assigned role and executing appointment seat |
| `holder_generation` | Exact native appointment generation, equal to the selected Profile generation |
| `profile_evidence_digest` | Native FormationJournal digest of the retained signed DERIVED_PROFILE envelope, and the actual holder's candidate Profile reference |
| `profile_artifact` | Complete original native Profile artifact, including its own content digest; equal to both the retained DERIVED_PROFILE content and actual executing holder artifact |

The H reference digest is never equated with an artifact content digest. Policy admission binds the mapping original before W2/W3; those assessments and assignment evidence retain that exact Profile reference. Current settings still reconstruct and validate their authority, originals, assessment outcomes, model binding and generations. A mapping string copied into session terms has no independent authority.

`FormationEffectiveConfiguration` is a deployment infrastructure contract, like the existing bounded transport and wire-adapter contracts. It projects all actual request options, including defaults, from the implementation that executes the operation. Its value must equal the complete admitted `request-configuration` content referenced by the current tuple. A prepared adapter validates and projects the exact retained wire bytes. An ordinary adapter must use the same options in invoke. No request callback, supplied validator, boolean, inferred digest equality or model-settings label implements this contract. Missing implementations refuse settings-bound execution. This check relies on the fixed adapter being truthful, just as bounded invocation relies on its transport honoring maximums and destinations.

## Owners and checkpoints

FormationCognition requires a SettingsBoundTransport whenever an independently signed session carries model_settings. It checks the native request derived by FormationSessionAuthority before preparation, inside claim reservation, at the durable start fence, and in a fresh journal inspection immediately before delivery through invokeForFormation. The wrapper's existing resolve checks remain. FormationSettingsBinding verifies the configured settings journal has the same canonical deployment owner as the actual cognition journal, and verifies the actual frame's parent instance before examining copied terms.

The custodied route preserves PreparedFormationTransport. CustodiedFormationTransport exposes its fixed adapter's effective configuration. FormationClaimCustodyBroker accepts an optional deployment binding, required for settings-bearing sessions. Its existing validate path checks the binding before credential issue, before consumption, and in the credential callback immediately before the durable dispatch fence. It still independently reconstructs the signed session, holder, lease, exact claim, request, operation, exposure and credential scope. A direct custody call cannot omit settings validation.

Frame verifiers follow the existing FormationSessionAuthority internal-owner convention: the actual owner supplies its authoritative frame under its existing lock. These are pure synchronous validation methods, not ingress handlers or authority-producing APIs. PersistentSettings' original reconstruction stays private; its new frame verifier returns no capability and acquires no journal lock. This is the narrowly declared extension to the previous design's public-inspection-only settings API. No validity/currentness cache is introduced. No public journal call is nested beneath another journal lock, and provider/credential I/O remains outside it.

Schema v4 now participates in the existing SharedExposure formation guard; the prior v2/v3-only branch accidentally allowed v4 to skip it. Genuine formation session/resource decisions must appear in the existing signed budget association. No new budget setter, budget size, source exception, retry, fallback or operational flag is introduced.

Historical completed attempt recognition remains historical: recomposition/recovery returns the retained result without spending another credential or making another call. Fresh attempts use current settings; stale settings cannot create a new claim. Custody uncertainty remains non-retryable.

## Proof composition and preservation

New additive fixtures reuse the real admission, assessment, assignment, institutional installation, personnel evidence, Profile approval, appointment, intake, signed session, lease, claim, custody, envelope and recovery producers. Original fixtures and tests are unchanged. Infrastructure uses only synthetic credentials and in-process wire handling. Options are serialized into the exact wire consumed by the offline transport.

O4 and every native Profile, appointment, session, claim and custody publication use one temporary FormationJournal. The existing FormationInstitution adapter reads separately scoped synthetic parent public artifacts produced by OperatorRootPersonnelInstallationService under that temporary directory. Its native publication guard reads an empty frame but publishes no formation journal generations. This preserves the FRESH root exclusion: institutional artifacts are prerequisites, not a competing native Augur installation in the FRESH root. The parent instance must equal the O4 instance. This is explicit fixture composition through existing constructors, not reflection, seeded journal state, changed institution validation, or global wiring. No user's installed institution is accessed.

The proof suite covers ordinary and custodied successes, effective-option mutation, separate foreign aggregate and instance cases, newly appointed wrong Profile, actual authorized replacement leaving an old signed tuple stale, unmapped shared-budget refusal, current evidence loss inside credential consumption, and recovery through recomposed owners. Each negative asserts no additional underlying dispatch and no unauthorized onboarding changes. The independent combined reviewer probe is retained separately.

Final results, exact source identities, source hashes and limitations belong to the correction report and evidence manifests. The unchanged full command and 30-minute allowance remain required; a timeout is incomplete evidence. Source review and fresh full hosted CI on the corrected source remain integration gates. No push, merge, O5 or live commissioning is part of this correction.
