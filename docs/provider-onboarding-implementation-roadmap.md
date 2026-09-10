# Provider onboarding — bounded implementation roadmap

Baseline: integration `086eb363ef43e58a29a50e7dfdecba34306c44c3`, tree `9d0ff3afeddab544a65ab1b56be05f306928d087`. O0 contracts and O1-B0 offline target selection are integrated through PR #782. Its corrected full CI passed 3,015 tests / 54,564 assertions with four skips. Author-review provenance remains explicit; integration was owner-directed, not fabricated independent acceptance. CY/FC acceptance stays intact.

The owner asked how much remains and then directed “Ok. Let us press forward.” This document fixes the planned implementation boundaries. The labels below organize future work; they do not claim an earlier approved O1–O5 breakdown existed.

**O1 has two batches awaiting integration or implementation: O1-B1 is validated locally and O1-B2 is planned. Eight batches await integration or implementation across O1–O5.** These counts exclude the completed O1-B0, documentation publication and later live commissioning. They are a scoped plan, not a time estimate or proof that undiscovered defects cannot require correction. Any additional corrective batch must identify its concrete defect and update this table explicitly; do not silently expand completed work.

| Stage / batch | Deliverable and finish line | State |
| --- | --- | --- |
| O0 | Source-bound contracts, authority map, selected provider/policy and medium-capacity rule | Integrated preparation; historical review qualifications retained |
| O1-B0 | Pure target selector, strict input projections, coupled whole-set refusal and tests | Complete; integrated |
| O1-B1 | Complete W1/W2/W3 response parsing and frozen-context consistency validation; no admission authority | [Validated locally; pending source/integration review](handoffs/provider-onboarding-o1-b1-report.md) |
| O1-B2 | Least-cost eligible Augur base proposal using exact comparison arithmetic, fixed workload, freshness and deterministic exclusions/ties | Planned |
| O2-B0 | Original policy/act/reference admission and current authority checks for the selected bootstrap route | Planned |
| O2-B1 | Shared Citadel sequence/command/step ledger, bounded claims, atomic consumption, replay and unknown-outcome fences | Planned |
| O3-B0 | DeepSeek API-key custody/adapter boundary, exact request/response/usage mapping and typed unknown failures, tested offline | Planned |
| O3-B1 | Legitimate Augur binding and bounded cognition bridge, selected FRESH route with explicit refusal of unsupported existing-installation transitions | Planned |
| O4-B0 | Atomic whole-set assignment application, persistent resolver and explicit operator change/revalidation, with crash/replay proof | Planned |
| O5-B0 | CLI preview/status/resume and complete offline journey/regression evidence through all preceding components | Planned |
| Live commissioning | Genuine installed trust, authority, access and provider evidence; separate owner selection and unresolved B1 disposition | Deferred; outside these eight batches |

The executable sequence is O1-B1 → O1-B2 → O2-B0 → O2-B1 → O3-B0 → O3-B1 → O4-B0 → O5-B0. An earlier component can be tested with clearly synthetic fixtures without claiming the later component exists. Every batch's exit is working bounded code plus meaningful tests, exact source/evidence identity and reviewed disposition; compilation or passing a structural parser does not grant institutional authority.

O1 completes when both remaining deterministic components pass their contract tests and are integrated alongside B0. O2 completes when authority and ledger rules have negative/replay/contention/crash evidence. O3 completes when offline adapters and Augur routing use those gates and refuse missing genuine authority. O4 completes when one authorized whole-set application persists and survives the declared recovery boundary. O5 completes when the offline CLI journey exercises the actual integrated paths without fake operational authority.

The O3-B1 batch implements the selected FRESH bootstrap corridor. Existing-installation cutover remains an explicit refusal unless its separately required governed transition is selected; it must not be smuggled in as a prerequisite for finishing FRESH. Future production trust enrolment, actual founding evidence, credentials and remote billing/cancellation proof remain commissioning work, not synthetic acceptance claims.

O1-B1 is implemented and locally validated, pending source/integration review; later rows define scope and order rather than authorize their execution. [O1-B1 campaign](next-campaign-provider-onboarding-o1-response-validation.md) and [local launch](handoffs/provider-onboarding-o1-response-validation-ready.md) contain its concrete task. No settled DeepSeek/API-key/FRESH/D2-A/P1–P9/medium-capacity/retry decision is reopened.

Every handoff contains all produced public files in one all-deliverables ZIP, including README and the inner review packet/checksum. No outer checksum; also provide individual report/instructions. Record fixes as fixes and preserve prior evidence. No private runtime material.
