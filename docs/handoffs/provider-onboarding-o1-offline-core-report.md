# Provider Onboarding O1 offline core — implementation report

Disposition: IMPLEMENTED_LOCALLY; PHP_VALIDATION_PENDING; NOT_ACCEPTED_FOR_INTEGRATION.

This batch implements the selected pure selector at O0 entry `3a5dfc1d4f341a12e5c3269c8f3c55f79807a342`. Final commit/tree and changed-source manifest are in the accompanying identities.json and review packet. The owner selected this bounded batch with “Proceed with campaign”. No independent O0 acceptance is claimed; the author-review qualification remains.

Nine small classes in src/Imperium/Runtime/Onboarding/Selection provide immutable rule/reference/candidate/order/input/result projections and a pure selector. The rule decoder accepts only the exact v1 body; the capacity decoder accepts the closed ranked/unknown variants of the v2 capacity_order field. Unknown fields/tags and malformed types refuse. Ranked references must cover all supplied fitting candidates before any permission filtering, including cases leaving zero or one candidate. Selection uses filtered capacity tiers, lower middle on even counts and exact UTF-8 identity ties. Complete-pair constraints refuse without alternate search or partial output. Neither the result nor structural decoding grants authority.

Supplied capacity evidence must belong to the supplied frozen reference set; genuine evidence/trust/currentness and Profile fitness remain upstream obligations. Decoders are intentionally not raw JSON parsers or complete assessment admission consumers. The campaign document specifies this boundary and duplicate-member handling owed by future admission. No runtime record, receipt or assignment application is created.

The only existing runtime-configuration edit excludes the new namespace from Symfony discovery. No command, adapter or operational consumer invokes it. The existing Augur base policy, historical approvals/reviews, CY/FC acceptance, runtime consumers, dependencies and installed state are unchanged.

Verification performed here: the existing 86 static/abstract specification checks pass after implementation; Git diff whitespace and structural scope checks pass; packet/source identities are recorded after the local commit. Source inspection traced coverage-before-filtering, exact comparisons, pair validation, refusal paths and service exclusion. The new PHPUnit tests are written with explicit expected outcomes and malformed-input cases.

Concrete verification blocker: this environment has no PHP executable and no vendor dependencies. PHP lint, PHPUnit and Symfony container compilation were NOT run. The Python specification suite does not execute or validate the new PHP code. Do not call this a passing executable implementation until the supplied local commands pass on the locked project toolchain. No dependency change or installation into the project was attempted to hide this gap.

Next executable action: import the incremental bundle into a fresh local worktree with the entry commit available, run the handoff's PHP lint and focused PHPUnit commands, and return their actual outputs for review. Any corrections must get a new exact commit and rerun evidence. This batch's review should also retain the unresolved independent O0 qualification. No push or merge is needed to run the tests.

CY/FC remain accepted and integrated within their offline scope. DEFER_ENROLLMENT, unresolved B1, three-retry ceiling with empty safe-retry allowlist, persistent consent and five false operational flags remain intact. No provider call, activation, push or merge occurred.
