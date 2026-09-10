# Provider onboarding — bounded implementation roadmap

Baseline: integration `086eb363ef43e58a29a50e7dfdecba34306c44c3`, tree `9d0ff3afeddab544a65ab1b56be05f306928d087`. O0 contracts and O1-B0 offline target selection are integrated through PR #782. Its corrected full CI passed 3,015 tests / 54,564 assertions with four skips. Author-review provenance remains explicit; integration was owner-directed, not fabricated independent acceptance. CY/FC acceptance stays intact.

The owner asked how much remains and then directed “Ok. Let us press forward.” This document fixes the planned implementation boundaries. The labels below organize future work; they do not claim an earlier approved O1–O5 breakdown existed.

**O1 has no planned implementation batch remaining. Five implementation batches remain across O2–O5.** O1-B0, O1-B1 and O1-B2 are reviewed/integrated within their offline scope. These counts exclude this documentation preparation, completed batches and live commissioning; concrete corrective defects must be named and tracked rather than silently expanding completed work.

| Stage / batch | Deliverable and finish line | State |
| --- | --- | --- |
| O0 | Source-bound contracts, authority map, selected provider/policy and medium-capacity rule | Integrated preparation; historical review qualifications retained |
| O1-B0 | Pure target selector, strict input projections, coupled whole-set refusal and tests | Complete; integrated |
| O1-B1 | Complete W1/W2/W3 response parsing and frozen-context consistency validation; no admission authority | Complete; reviewed and integrated in PR #784 |
| O1-B2 | Least-cost eligible Augur base proposal using exact comparison arithmetic, fixed workload, freshness and deterministic exclusions/ties | Complete; reviewed and integrated in PR #786. [Review](reviews/provider-onboarding-o1-b2-source-evidence-review.md); original [implementation report](handoffs/provider-onboarding-o1-b2-report.md) preserved. |
| O2-B0 | Original policy/act/reference admission and current authority checks for the selected bootstrap route | Complete; corrected, reviewed and integrated in PR #788. [Review](reviews/provider-onboarding-o2-b0-correction-source-evidence-review.md), [integration identity](provider-onboarding/o2-b0-reviewed-integration.json). O2 remains open for B1. |
| O2-B1 | Shared Citadel sequence/command/step ledger, bounded claims, atomic consumption, replay and unknown-outcome fences | Implemented locally after reviewed/integrated preparation; source review/full CI/integration pending. [Report](handoffs/provider-onboarding-o2-b1-report.md) |
| O3-B0 | DeepSeek API-key custody/adapter boundary, exact request/response/usage mapping and typed unknown failures, tested offline | Planned |
| O3-B1 | Legitimate Augur binding and bounded cognition bridge, selected FRESH route with explicit refusal of unsupported existing-installation transitions | Planned |
| O4-B0 | Atomic whole-set assignment application, persistent resolver and explicit operator change/revalidation, with crash/replay proof | Planned |
| O5-B0 | CLI preview/status/resume and complete offline journey/regression evidence through all preceding components | Planned |
| Live commissioning | Genuine installed trust, authority, access and provider evidence; separate owner selection and unresolved B1 disposition | Deferred; outside these five remaining batches |

The remaining executable sequence is O2-B1 → O3-B0 → O3-B1 → O4-B0 → O5-B0. An earlier component can be tested with clearly synthetic fixtures without claiming the later component exists. Every batch's exit is working bounded code plus meaningful tests, exact source/evidence identity and reviewed disposition; compilation or passing a structural parser does not grant institutional authority.

O1 is complete following verified B2 integration alongside B0 and B1; this is offline implementation acceptance only. O2 completes when authority and ledger rules have negative/replay/contention/crash evidence. O3 completes when offline adapters and Augur routing use those gates and refuse missing genuine authority. O4 completes when one authorized whole-set application persists and survives the declared recovery boundary. O5 completes when the offline CLI journey exercises the actual integrated paths without fake operational authority.

The O3-B1 batch implements the selected FRESH bootstrap corridor. Existing-installation cutover remains an explicit refusal unless its separately required governed transition is selected; it must not be smuggled in as a prerequisite for finishing FRESH. Future production trust enrolment, actual founding evidence, credentials and remote billing/cancellation proof remain commissioning work, not synthetic acceptance claims.

O1-B1 is [reviewed and integrated](reviews/provider-onboarding-o1-b1-source-evidence-review.md) as merge `cc9a1882e88de55412d1428e2a01e5ac85bb92c5`, tree `d8d3a385b50008c5ae56d3f6a2f384bbd93fd230`; exact [integration metadata](provider-onboarding/o1-b1-reviewed-integration.json) retains the source/CI identities. The review records 3,162 tests / 54,780 assertions / four platform skips on the matching remote tree. Its reviewer provenance is retained; the author's incomplete Windows full suite remains incomplete.

O1-B2 is [reviewed and integrated](reviews/provider-onboarding-o1-b2-source-evidence-review.md) as merge `b60f16a8af003408e84b68d1ac3462fb06a9479d`, tree `34a51ba57463419dbf1eb8165756f00d46e121c1`. [Integration metadata](provider-onboarding/o1-b2-reviewed-integration.json) records fresh full CI: 3,321 tests / 55,231 assertions / four platform skips. This preparation verified the supplied Git/source/CI-log identities; it did not rerun that PHP CI. Original review provenance and submission-time reports remain intact.

O2-B0 is accepted offline after R1-R4 correction, hosted CI and PR #788 integration: merge `cafa93f9665f0e5734f26491a092b28995e1cf14`, tree `e2070c7a2184c7481882f27297c976d248b2c2be`. Supplied CI records 3,463 tests / 55,572 assertions / four skips. This preparation verified its evidence/Git identity, not a new PHP run. Original author/HOLD reports remain unchanged.

O2-B1 preparation was accepted and integrated in PR #789, merge `0399b581e8e61b5306813479df6a8af363c35c0a`, tree `bc010341a934706e2c75b8d8b6e203882473bedf`. Its [attributed review](reviews/provider-onboarding-o2-b1-preparation-source-evidence-review.md) and [integration metadata](provider-onboarding/o2-b1-preparation-reviewed-integration.json) preserve source/CI provenance.

Only O2-B1 has been implemented locally in this pass. Its [report](handoffs/provider-onboarding-o2-b1-report.md) distinguishes runtime checks from inherited preparation CI. Stop for source/integration review; do not start another batch. Five implementation batches remain unaccepted across O2-O5 until this code passes its review/full-CI gate. Settled decisions and all live deferrals remain unchanged.

Every handoff contains all produced public files in one all-deliverables ZIP, including README and the inner review packet/checksum. No outer checksum; also provide individual report/instructions. Record fixes as fixes and preserve prior evidence. No private runtime material.
