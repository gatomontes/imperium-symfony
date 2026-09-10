> Current onboarding status: [validated O1 offline selector and owner-authorized integration](provider-onboarding-o1-current-status.md).
> Earlier PHP-validation-pending and local-only instructions below are historical for this batch.
> Author-review provenance and all live deferrals remain explicit.

# Provider Onboarding O1 — offline selection core, first batch

Status: OWNER_SELECTED_IMPLEMENTATION; PHP_VALIDATION_PENDING.

Owner instruction: “Proceed with campaign”, following the prepared O1 offline-core proposal. This selects only the bounded selector implementation. It advances beyond O0's prior implementation deferral without representing the author's O0 self-review as an independent acceptance. The O0 review qualification remains open and is carried into this batch's review. No parallel agents were requested or launched.

Entry: `3a5dfc1d4f341a12e5c3269c8f3c55f79807a342`, tree `e0f64d6ea48c19b306761099cc09a71d72353cd3`. Branch: `codex/provider-onboarding-o1-offline-core`, isolated from the previous worktree and installed state.

Read docs/courtyard-fc-acceptance.md, contracts/provider-onboarding-assignment-selection.md, docs/provider-onboarding-medium-capacity-decision.md, the O0 authority/continuation contracts, and docs/handoffs/provider-onboarding-o1-offline-core-ready.md.

Selected scope: strict immutable decoded-value projections for rule, D2 references, candidate identity and the v2 capacity_order field; pure role-specific medium-capacity selector; complete-pair permission validation; focused adversarial PHPUnit tests. Symfony discovery excludes the new namespace. The base-model policy is unchanged and is not implemented in this batch.

The APIs consume decoded arrays, not raw JSON. A later boundary must detect duplicate JSON member names before decoding, validate complete response schemas and source authenticity, resolve original H records, and establish actual fitness/permissions/currentness. Structural parsing here does not perform any of that admission work. Identifiers remain exact opaque nonempty UTF-8 values in this internal projection; later canonical admission must enforce the applicable bounded identifier grammar. No wire format is widened by this projection.

RoleInput requires one exact Profile digest among fitting candidates, exact binding refs and unique identities, and syntactic membership of capacity evidence in the supplied frozen reference set. This checks consistency of supplied inputs, not authenticity, evidence quality or frozen custody. Distinct references claiming an identical binding identity refuse rather than introduce an unapproved tie-break. All malformed structures throw InvalidArgumentException; valid but unselectable inputs return explicit typed refusals. Later admission must map these exceptions to its fail-closed protocol without consuming application slots.

Flow: selected offline core → local PHP lint/focused tests → review of exact commit and retained O0 qualifications → separately selected authority/admission and custody implementation → separately selected provider/CLI wiring. No later stage is authorized by this first batch. O2–O5 and live commissioning remain deferred.

Preserve CY/FC offline acceptance and historical source evidence, one Citadel custody/budget domain, DEFER_ENROLLMENT, unresolved B1, all five false operational flags, the empty safe-retry allowlist and persistent operator-controlled assignments. No credential handling, account/provider calls, appointment, enrollment, installation mutation, push or merge.

Deliver every produced public file in one convenience ZIP with README and the hashed review packet. No outer checksum. Supply individual report, handoff and patch files as well because ZIP downloads have failed. Stop at a local commit with exact identity, validation results and post-check change record.
