# O1-B1 — offline complete assessment-response validation

Status: SELECTED_FOR_LOCAL_IMPLEMENTATION by the owner's instruction to press forward after defining the remaining roadmap. This preparation contains no new runtime implementation. O1-B0 is complete and remains complete.

Entry must contain merge `086eb363ef43e58a29a50e7dfdecba34306c44c3` plus this campaign preparation. Record actual entry commit/tree after pulling the campaign; do not mislabel the earlier merged selector commit as the new documentation entry. Work in a fresh isolated worktree on `codex/provider-onboarding-o1-response-validation`; preserve any existing directory/branch rather than resetting it.

Read applicable AGENTS.md, docs/provider-onboarding-implementation-roadmap.md, contracts/provider-onboarding-response-validation.md, docs/provider-onboarding/o0-workloads.json, contracts/provider-onboarding-assignment-selection.md, the authority/continuation/retry contracts, docs/provider-onboarding-o1-ci-correction.md, docs/courtyard-fc-acceptance.md, and the actual merged selector/value classes and tests. Inspect existing JSON helpers before choosing a parser. CanonicalJson encodes normalized arrays; it is not a duplicate-key-safe raw decoder.

## Work and finish line

1. Implement a bounded raw JSON decoder that preserves object/list distinctions and detects duplicate decoded member names before information is lost. Reuse existing suitable infrastructure if it truly meets those guarantees. No dependency or lockfile update. Document the root-counting depth convention and test its boundary.
2. Implement strict immutable expected-context and complete W1/W2/W3 parsed-response types with the exact declared shapes. Validate complete candidate coverage, ten predicates, FIT consistency, frozen-reference membership, schema/group/input/holder/Profile equality and W2/W3 capacity coverage. Unknowns and contradictions remain retained claims.
3. Keep parsed output separate from genuinely admitted evidence and selector RoleInput. No caller-supplied boolean or method named validated may confer authority. Clearly document the later trust/fitness conversion obligation.
4. Add meaningful raw-input PHPUnit tests and run focused plus relevant regressions. Verify class-level exclusion, unchanged pinned configuration/history and absence of operational callers.

Finish when the raw-byte boundary and complete response contract are implemented, their positive/negative tests pass, and exact source/evidence is packaged for review. No base-model cost selector, actual authority admission, provider adapter, credential handling, record/ledger mutation, application or CLI work is included. Those belong to the roadmap's later rows.

Required adversarial cases: valid W1 and each W2/W3; escaped duplicate names at root and nested predicates/refs; duplicate equal-value members; malformed UTF-8/BOM/escapes/surrogates/trailing bytes; byte limit and depth boundary; `{}` versus `[]`, numeric-key objects, scalar coercion; unknown/missing keys/tags; missing/extra/duplicate candidates and predicates; same ID with changed digest/schema; wrong group/input/holder/Profile; out-of-set evidence; PASS without support; FIT with FAIL/UNKNOWN; unknown fit retained; ranked incomplete/duplicate/extra coverage; malformed rank even if permission would later select one; original bytes/digest retained; immutable output and no direct authority/application result. Test the full raw-byte path with explicit expected outcomes, not a second copy of the parser algorithm.

Relevant regressions: the existing ProviderOnboardingOfflineSelectionTest and the three source-pin tests named in the CI correction, plus the existing 86 Python specification checks. Full repository CI is the integration gate. Inspect skips/warnings and distinguish local tests executed, remote CI inspected and prior evidence inspected. Never edit historical hashes or weaken existing tests to accommodate a config change.

Use `src/Imperium/Runtime/Onboarding/ResponseValidation/` and a focused test in `tests/Imperium/Runtime/` unless actual repository conventions provide a better adjacent location. Exclude every new implementation/value class via the existing attribute. No parallel agents are selected for this bounded run.

## Exit and delivery

Update the roadmap row only with the actual disposition: implemented/pending validation, validated/pending integration, or integrated when verified. Keep other rows planned. Record exact entry/tested/final identities, changed paths, SHA-256 payload/source manifest, original commands/results, test totals and post-check changes. Commit locally and return the public packet for review. Publication/merge is a distinct integration step; no live activity follows.

Produce ONE `provider-onboarding-o1-b1-all-deliverables.zip` containing every produced public report, changed contract/source/decision, run instructions and review packet with its checksum. Include README; verify archive contents. No outer checksum. Also provide individual report and instructions because ZIP downloads have failed. Exclude credentials, private evidence and installed runtime state.

CY/FC acceptance, B1, DEFER_ENROLLMENT, empty safe-retry allowlist, persistent operator-controlled assignments and five false operational flags remain. No owner choices are reopened. Additional defects must be named and tracked explicitly rather than silently expanding this batch.
