# O3-B1 local implementation report

Implemented locally and committed. Independent source review and fresh full hosted CI remain pending. No push, merge, real provider request, live enrollment/installation, real credential access or later-batch action was performed. All five operational flags remain false; the actual retry allowlist remains empty.

## Source identities

- Preparation: `02ce09621c63822eaff9ffd23151d5d3aed593a4`, tree `2fc0e9075ce199312797533d237652fa15b82865`.
- Continuation entry: `8ebee96cbdcd43e5e89e8b1d222d83cd285b2e2d`, tree `97e4c675ac75fadc0f59bb6c7c8e67c37a65420e`. Tracked state was clean; two existing untracked handoff copies were preserved.
- Tested source: `22072aa1936d3f4fc0d97cbdd484dc322a4b421c`, tree `db9194c29575ac320e7d8344ae8482575e0fb64b`; 1846 tracked PHP files captured and individually compared with their Git blobs.
- Final documentation identity is recorded in the package's `identities.json`, avoiding a self-referential commit hash. Later changes contain no PHP.

## Implemented behavior

The new explicit v3 migration retains the exact quiescent v2 subtree, original v1/v2 receipt and prior maps. No read implicitly migrates or downgrades. A constructor-selected FRESH producer resolves the real completed O1 base proposal and exact approved mapping, derives the finite eligible-binding terms, authenticates constitutional originals, and publishes the typed holder and root consumption with the O2 command/completion in one FormationJournal generation.

Native installation, sealing, legacy founding-model assignment and Augur occupancy publication share that owner. Native files keep their schemas/serialization; FRESH refuses native partials, installation, seal or occupancy, and native producers refuse a FRESH-owned root. Global root ownership cannot be reset by policy/sequence labels. Tests exercise changed command/native instance labels, concurrent contenders and abrupt process exits. Standing/existing-installation cutover remains unsupported.

The Augur adapter reconstructs a separate commission, current holder, exact Profile/model/configuration/credential lineage, original public workload/evidence and prior successful results. Contextual preparation runs at reservation and every existing custody revalidation. Runtime keeps private credential ports and exact KeySource identity, admits only the fixed POST destination, authenticates resources again before dispatch, and checks response usage against both the reservation and the authenticated exact-wire token bound.

ProviderResponse and the unchanged O1 semantic parser run before group completion. W1 then W2 then W3 complete through actual production producers/resolvers with a mock HTTP client. Predecessor outcomes and complete envelopes are frozen. A valid no-fit result remains an assessment result; malformed semantics are terminal, while absent/contradictory trustworthy usage and post-dispatch revocation retain uncertainty and exposure. Applications and assessment_views remain empty.

Production defaults require independent BaseEvidence, ConstitutionEvidence and CognitionEvidence verification and otherwise refuse. The test-only provider uses an explicitly fictional four-octet tokenizer and signed synthetic account/tariff/factual originals; it grants no real account or provider authority.

## Necessary compatibility seams

| Seam | Necessity and preservation |
| --- | --- |
| Native install/seal and legacy Augur assignment/occupancy | Common root fence closes competing publication; existing schemas, bytes and legacy validation remain intact. No nested aggregate or second file lock. |
| FormationJournal | Fixed owner identity comparison only; original journal format, lock and publication point preserved. |
| AuthorityStore, Admission, StateMigration, StateValidation, SharedExposure | Explicit v3 decoding/migration/current writes and exposure accounting; original v2 interpretation and migration receipt preserved. |
| CommandLedger, CurrentAuthority, CompletionResolver | Typed FRESH producer and finite original-backed derivation replace only the selected missing seam; unsupported founding without that producer and all O4/standing paths still refuse. |
| PreparedOperation/CustodyCoordinator and Runtime | State-aware preparation resolves produced holders/prior outcomes at the same existing checkpoints; no public dispatch, alternate key source, endpoint or retry path. |
| BaseProjection | Optional historical evaluation/snapshot reconstruction; original default projection and tests retained. |
| Inventory supplement | One additive row identifies the real FRESH publication boundary; frozen baselines were not regenerated. |

## Recorded final validation

860 selected local tests / 8145 assertions, all selected commands exit 0. PHP >=8.4 and locked Composer dependencies were verified. Changed PHP lint, non-debug kernel smoke, diff checks and all 86 specification checks passed.

| Selection | Tests | Assertions | Exit |
| --- | ---: | ---: | ---: |
| new-o3-b1-bridge | 1 | 31 | 0 |
| new-o3-b1-owner | 7 | 106 | 0 |
| new-o3-b1-base | 11 | 53 | 0 |
| credential-correction | 8 | 60 | 0 |
| reviewer | 4 | 10 | 0 |
| native-root-legacy | 4 | 291 | 0 |
| o3-b0 | 68 | 304 | 0 |
| new-o3-b1-refusals | 10 | 57 | 0 |
| o2-b1-b0 | 211 | 952 | 0 |
| o1-pins | 380 | 1519 | 0 |
| inventory | 16 | 4002 | 0 |
| fc | 140 | 760 | 0 |

`final-commands.json` records exact argv, cwd, times and exits; `evidence/final/` contains complete public logs. `tested-php-source.zip` and hashes bind every tested project PHP byte stream. Dependencies have separate identities. Failed/superseded development runs, including the aborted initial final round, remain separately labelled under `evidence/development/`; none is claimed as final or full CI.

The supplied review's claims remain review input only. Both preparation-to-final and continuation-to-final patches/bundles reconstruct the final tree. Protected-original comparisons include every tracked entry file and enforce unchanged original tests/fixtures, approved originals, dependency locks, service configuration and frozen inventories, with the documented source seams/additive supplement isolated.

## Review boundary

This is local selected proof, not fresh full hosted CI. The unchanged hosted workflow uses PHP 8.4, runs `vendor/bin/phpunit tests` and retains its 30-minute allowance. Source review and that fresh full run must precede integration. No implementation blocker is asserted from the previously missing owner: that owner protocol is now implemented and tested. No O4 assignment application or O5 CLI is included.
