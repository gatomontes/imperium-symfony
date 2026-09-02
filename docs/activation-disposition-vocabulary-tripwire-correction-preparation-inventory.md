# Activation Disposition Vocabulary Tripwire Correction Preparation Batch 0

Result: `PREPARATION_BATCH_0_COMPLETE_VOCABULARY_DETECTOR_SEMANTICS_CLASSIFIED`.

Source baseline: clean local `main` at
`d7da12722f5862147dcf7adbac7a400b0e77ef25`, 2026-09-02. No fetch or pull was
performed: the operator's external-I/O prohibition overrides the older local
handoff's synchronization recipe. Remote freshness is not claimed.

The controlling posture remains
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`.
The prior closure remains
`FROZEN_RUNTIME_COVERAGE_TRIPWIRE_RESTORATION_CLOSURE_REJECTED_WITH_MATERIAL_VOCABULARY_TRIPWIRE_GAP`.
This is preparation evidence, not detector correction or a terminal audit.

## Sources read

- `docs/handoffs/activation-disposition-vocabulary-tripwire-correction-preparation-batch-0-local-ready.md`
- `docs/handoffs/activation-disposition-vocabulary-tripwire-correction-campaign-ready.md`
- `docs/next-campaign-activation-disposition-vocabulary-tripwire-correction.md`
- `docs/frozen-runtime-coverage-tripwire-restoration-batch-3-blackquill-audit.md`
- `docs/handoffs/frozen-runtime-coverage-tripwire-restoration-campaign-complete.md`
- `docs/frozen-runtime-coverage-tripwire-restoration-activation-disposition-exceptions-v1.tsv`
- `tests/Imperium/Runtime/ProviderBindingActivationIntegrityRemediationBatch6Test.php`
- `tests/Imperium/Runtime/FrozenRuntimeCoverageTripwireRestorationBatch3TerminalAuditTest.php`
- `docs/delegate-mission-flow.md`
- `todo/blackquill-todos.md`

The six source files below were also inspected, together with the current
disposition producer and constant-reference consumers. Historical statements
of completion in the retained audit and handoff do not supersede the rejection.

## Governed values and complete direct occurrence inventory

The byte-exact, case-sensitive values are `QUARANTINED_PENDING_REMEDIATION`
and `RETIRE_CORRIDOR`. `QUARANTINED_EXPIRED_UNUSED` is not governed by this
tripwire. There are 16 direct occurrences (nine quarantine, seven retirement)
at nine source lines in six PHP files. Every occurrence is currently an ordinary
single-quoted `T_CONSTANT_ENCAPSED_STRING`; none is a comment, double quote,
larger string, heredoc, interpolation or split construction.

`activation-disposition-vocabulary-tripwire-correction-preparation-occurrences-v1.tsv`
records every occurrence by repository path, line and value, including the two
quarantine occurrences on demonstration line 54. Preparation tests compare the
full current runtime scan with that inventory. This is a direct textual/token
inventory; it does not prove absence of arbitrarily encoded or computed values.

All paths below are relative to `src/Imperium/Runtime/`.

| Path | Lines; Q/R counts | Existing classified role and actual use |
| --- | --- | --- |
| `LaCortine/StrandedActivationArtifactDispositionContract.php` | 14; 1/1 | HISTORICAL_STRANDED_ARTIFACT_VOCABULARY: array includes the separate expired-unused value; not a current corridor decision producer |
| `Evidence/ActivationCorridorDispositionInterruptionDemonstration.php` | 15, 54; 3/2 | OFFLINE_INTERRUPTION_EVIDENCE: array drives offline cases, ternary chooses competing outcome; this class is not run in preparation |
| `Imperator/ActivationCorridorDispositionCallerAuthorityIssuanceAuthorizationContract.php` | 25; 1/1 | AUTHORITY_ISSUANCE_CONSTRAINT: constrains proposed values, explicitly does not select or seal |
| `Imperator/ActivationCorridorDispositionContract.php` | 11; 1/1 | CANONICAL_DISPOSITION_CONTRACT: canonical two-outcome array and non-authority perimeter |
| `Imperator/ActivationCorridorDispositionContractValidator.php` | 66, 80, 83; 2/1 | FAIL_CLOSED_DISPOSITION_VALIDATOR: exact comparisons choose/check consequences; strict membership uses the eligibility constant |
| `Imperator/ActivationCorridorDispositionEligibilityContract.php` | 25; 1/1 | ELIGIBILITY_CONTRACT: candidate vocabulary, retains selects_disposition=false |

“Producer” in the old test means a token-bearing file. It does not establish
that each of these six files seals decisions, nor that these are every runtime
consumer or record producer.

## Indirection and current semantic roles outside literal discovery

The existing `Imperator/ActivationCorridorDispositionProducer.php::decide`
constructs its record's `disposition` from the authority's `proposed_disposition`
after comparing it with eligibility and invoking validation. It contains neither
literal. Its `DISPOSITIONS` constant is a storage path, not the outcome array.
This source reading grants no permission to instantiate or execute it.

`Imperator/CorridorDispositionPrincipalAuthorityRemediationProducer.php` also
copies the issuance authorization's proposed value into a caller-authority
record (line 66), without either direct literal. It is a value-forwarding
authority producer, not a new vocabulary definition or disposition selector.

Direct outcome-array references found in the runtime are:

| File under `Imperator/` | Reference/use |
| --- | --- |
| `ActivationCorridorDispositionContractValidator.php` | Eligibility DISPOSITIONS at lines 74 and 110: strict membership |
| `ActivationCorridorDispositionReadOnlyReconstructionService.php` | Eligibility DISPOSITIONS at line 29: validates supplied candidate, later propagates it into a result |
| `ActivationCorridorDispositionTerminalAudit.php` | Canonical DISPOSITIONS at line 24: validates a read record |
| `CorridorDispositionPrincipalAuthorityRemediationContractValidator.php` | Issuance-authorization DISPOSITIONS at line 92: constrains proposed value |

A new literal definition in an unclassified PHP path is in the intended detector
contract. Referencing an existing constant from a new path is not discovered by
literal scanning. Local aliases, class constants, `use const`, array indexing,
variable forwarding, caller-supplied records and cross-file value flow require
semantic analysis if a later campaign wants an exhaustive producer claim. They
must not be silently called covered by this correction.

## Lexical and value semantics

The versioned JSON case matrix contains inert PHP source templates, current
detector expectations, PHP token categories and proposed Batch 1 classifications.
Each of its 27 cases is parsed and passed to the actual detector for each value.
No fixture is included, evaluated, autoloaded or executed.

| Form | Current behavior for an unclassified path | Batch 1 contract |
| --- | --- | --- |
| Ordinary single quote or binary-prefixed single quote | Rejects | Detect complete literal value |
| Double quote or binary-prefixed double quote | Accepts: demonstrated escape | Detect complete literal value |
| Double-quoted hexadecimal, octal or Unicode escape encoding | Accepts: demonstrated lexical escape | Decode PHP literal escapes deterministically; detect exact bytes |
| Single-quoted backslash-x text | Accepts | Different bytes, not a match; do not apply double-quote unescaping |
| Static heredoc/nowdoc | Accepts: demonstrated lexical escape | Detect complete static value with PHP delimiter/indentation semantics |
| Interpolation, including an exact-looking literal segment plus variable | Accepts | OUT_OF_CONTRACT_DYNAMIC; do not treat a segment as a complete value |
| Split-literal concatenation | Accepts | OUT_OF_CONTRACT_CONCAT; no constant folding in the minimum correction |
| Exact literal concatenated with a suffix | Rejects | Literal component is detected even if the resulting expression is larger |
| Constant definition containing an exact literal | Rejects | Definition is detected; uses do not become new literal-bearing paths |
| Constant reference only | Accepts | OUT_OF_CONTRACT_REFERENCE; no symbol or cross-file resolution |
| Function-assembled value | Accepts | OUT_OF_CONTRACT_DYNAMIC; no execution or speculative evaluation |
| Comment/docblock with single-quoted value | Rejects: false positive | Exclude T_COMMENT and T_DOC_COMMENT |
| Bare-token comment, identifier or different case | Accepts | Non-producer or different value |
| Prefix/suffix in one complete larger string | Accepts | Exact-value contract excludes substrings |
| Larger documentation string embedding single-quoted value | Rejects: false positive | Exclude as a larger decoded value |
| Inline HTML in a PHP file containing quoted value | Rejects: false positive | Exclude T_INLINE_HTML |
| Exact exception message or unreachable literal | Rejects | Still a literal occurrence; not proof of decision production |

The selected minimum is a complete **literal-value occurrence** alarm, not a
disposition-expression evaluator. An exact string used for documentation inside
executable PHP still requires path classification; comments and larger strings
do not. Do not infer intent from a variable name, method name or surrounding prose.
Do not trim decoded values, case-fold, normalize Unicode, or accept substrings.
Actual trailing whitespace/newline bytes make a different value. Heredoc syntax
newlines and indentation must be handled as PHP syntax, not generic trimming.

Out-of-contract does not mean safe or approved. Concatenation, interpolation,
indirection and runtime assembly remain explicit residual acceptance escapes
under the narrow claim. Batch 1 must show those exclusions in executable cases
and documentation, not claim they fail closed. If its implementation cannot
classify a promised literal encoding, it must fail with path/line diagnostics
rather than silently ignore it or downgrade the claim.

## Inventory coupling and disposable roots

The actual detector is
`ProviderBindingActivationIntegrityRemediationBatch6Test::testActivationDispositionVocabularyIsLimitedToExactClassifiedRoles`.
It reads the runtime and the six-role TSV from the same
`IMPERIUM_FROZEN_COVERAGE_ROOT`, falling back to the repository root for an absent
or empty override. It recursively visits files whose extension is exactly `php`,
normalizes relative path separators, sorts and compares exact path arrays.
There is no line, occurrence-count, per-value, role-semantics or content-digest
comparison. Duplicate inventory paths fail. Classification and role must only
be nonempty; the batch must be the historical Batch 2 string and the focused
test must contain the method name. The synthetic admission proof must preserve
that parser contract without changing the real inventory.

Removing any of the six token-bearing paths fails; a stale inventory row also
fails. An explicitly classified synthetic literal passes. Replacing all code in
an admitted file with a quoted-token comment passes today. Arbitrary nonempty
role/classification text passes. Extra literals or changed behavior within an
already admitted file do not change path equality. A quote-only conversion of
all matching literals in an admitted file instead causes a false disappearance.

The old terminal mutation helper copies the complete runtime and three TSVs,
sets the process environment, directly calls the actual coverage method and
catches AssertionFailedError. Its private helpers cannot be reused directly;
its teardown unsets rather than restores a previous override. The preparation
harness copies the full runtime and only the disposition TSV, modifies only its
fresh temporary root, restores the previous override, and checks the resolved
temporary parent and allocated basename before deleting its own tree. It never
copies var, local environment files, credentials or private evidence. Existing
runtime classes are read as source text only.

Batch 1 should reuse this actual-method invocation pattern (or a shared test-only
helper), not create a second detector for mutation proof. Keep source and TSV
root selection inseparable, restore overrides on failure, and prove baseline,
negative and explicit admission cases all use that root. An override remains a
trusted test injection seam, not an authenticity or hostile-filesystem boundary.
Non-PHP files, alternate-case extensions, generated/include sources outside the
runtime tree, symlink traversal and malicious inventory writers are not proved.

## Existing coverage and remaining proof work

Before preparation, the Batch 6 test checked the current path set, the old
terminal test added one single-quoted RETIRE_CORRIDOR producer and expected
failure, and its positive addition was an authority candidate, not a vocabulary
producer. Campaign-ready tests retained wording and sequencing only.

Preparation adds characterization of both values across 54 lexical mutations,
all 16 direct occurrences, all six path removals, baseline, unclassified and
classified vocabulary addition, duplicate and stale rows, comment-only admitted
path and arbitrary role metadata. A green preparation suite intentionally proves
the defect is still present; it does not establish corrected acceptance.

The Batch 1 proof must reverse defective expectations against the same actual
detector, including both values, all admitted paths, and explicit synthetic
admission. Add focused cases for mixed quoting, partial/mixed escapes, escaped
quotes and backslashes, unknown escapes, NUL/newline suffixes, indented and CRLF
heredoc/nowdoc, dynamic heredoc, comments containing every quoting form, and
malformed/unsupported syntax. Test literal removal, same-count path substitution,
duplicate/stale inventory rows, a repository-root fallback and restoration of a
pre-existing environment override. The current matrix verifies parsing/token
shape and current path acceptance; it does not implement or validate a future
literal decoder. These cases are proof obligations, not completed Batch 1 work.

## Smallest safe Batch 1 boundary

1. Change test detection mechanics and versioned detector contracts only.
   Preserve the exact six-role TSV path equality and role distinctions.
2. Use PHP token inspection without include, eval, runtime boot, providers or
   external processes executing source. Decode complete single/double/binary
   literals and static heredoc/nowdoc correctly; exclude comments and inline
   HTML; compare exact decoded bytes. Refuse unclassifiable promised forms.
3. State the expression/dynamic/reference exclusions above. No arbitrary
   constant folding, dataflow analysis, production source edits or widening of
   the inventory to conceal a failed baseline.
4. Make adversarial mutations call the actual corrected detector with coupled
   disposable source/inventory roots. Replace or retire preparation assertions
   that deliberately expect the old defect, keeping this versioned historical
   matrix clearly labeled as baseline evidence.
5. Stop after mechanical correction and proof. Batch 2 is a separate terminal
   Blackquill audit after Batch 1 merge, starting from the resulting main.

No mission, provider, external I/O, live credential or capability, live runtime
mutation, Iron Gate or Lazaretto action, historical-audit repair, terminal audit,
closure supersession, qualification removal or transactional-correctness claim
is authorized by this inventory.
