# Activation-disposition literal vocabulary detector contract v1

Batch 1 replaces quote-sensitive text discovery in
`ProviderBindingActivationIntegrityRemediationBatch6Test::testActivationDispositionVocabularyIsLimitedToExactClassifiedRoles`.
The contract is a path inventory of complete literal values, not an exhaustive
inventory of runtime value flow or decision producers.

## Detection

Read every current lowercase `.php` file recursively under
`src/Imperium/Runtime`. Parse source without executing it using the installed
`nikic/php-parser` v5.8.0 dependency already pinned in `composer.lock`, with
`ParserFactory::createForHostVersion`. Inspect parsed `Scalar\String_` nodes and
compare their decoded values strictly against `QUARANTINED_PENDING_REMEDIATION`
and `RETIRE_CORRIDOR`. Do not trim, case-fold or compare substrings.

The PHP parser handles single/double quotes, binary prefixes, PHP escapes,
static heredoc and nowdoc, CRLF delimiters and flexible indentation. Comments,
docblocks, inline HTML and interpolated text parts are not string-scalar nodes.
Parse failures stop the test with source path and line; there is no fallback to
text matching. Source is never evaluated, included or used to instantiate a
runtime class. This is not a PHP semantic-validity or executable-safety gate.

Each scan rereads the current runtime files and the versioned six-role TSV from
the same `IMPERIUM_FROZEN_COVERAGE_ROOT`. An absent or empty override uses the
repository root. Compare sorted, normalized relative path arrays exactly.
Retain duplicate-path rejection and the existing nonempty classification/role,
historical authorizing-batch and focused-test checks. The real six-role TSV is
unchanged. A test-only cache stores syntax-analysis booleans keyed by exact
source bytes, never filename, timestamp or inventory. Changed source bytes,
path moves and changed inventory admission are still checked on every scan.

## Exact boundary and exclusions

- Exact literals count wherever they occur: arrays, constants, enum values,
  attributes, exception messages, dead code or an exact documentation message.
  A literal's presence says nothing about whether its code executes.
- One larger decoded string containing the vocabulary does not count, even
  when the embedded text includes quote characters. Whitespace, newline, NUL,
  backslash and unknown-escape suffixes are real bytes, not decoration.
- An exact literal component in a concatenation counts as that literal's
  occurrence. Split literals whose concatenation would form a value do not:
  OUT_OF_CONTRACT_CONCAT. No expression folding is performed.
- A literal constant definition counts; aliases and references alone do not:
  OUT_OF_CONTRACT_REFERENCE. No constant, array-index or cross-file resolution
  is performed.
- Interpolated text and function-/variable-assembled values are
  OUT_OF_CONTRACT_DYNAMIC. A nested executable expression can itself contain a
  separate exact string literal, which still counts. The parser does not
  confuse a text part with that nested literal.
- Non-PHP sources, alternate-case extensions, generated code outside the tree,
  hostile filesystem/symlink behavior, malicious inventory authors and semantic
  role changes in already admitted files remain outside the proof.

Excluded forms are not approved producers and are not proved fail-closed. A
claim that all possible disposition-producing expressions are inventoried would
remain false. The permitted corrected claim is quote-independent exact literal
coverage with the existing six path-specific classifications.

## Batch 1 evidence

The preparation JSON remains unchanged historical evidence of old behavior.
The active Batch 1 test uses its intended classifications to exercise the
corrected detector for both values: 54 lexical mutations. The additional
Batch 1 JSON supplies 60 mutations for escapes, larger values, comments,
heredoc/nowdoc edge cases, enums, attributes, aliases and concatenation.

Additional tests exercise every admitted path's removal and quote conversion,
same-count path substitution, source restoration, explicit vocabulary admission,
duplicate/stale rows, comment-only replacement, malformed syntax diagnostics,
repository fallback and override restoration. A role-only change deliberately
still passes, documenting the semantic limit. Every mutation invokes the actual
coverage method, never a replica detector.

The old preparation assertions expecting successful escapes have been retired
from the active suite, with the immutable baseline JSON and source-occurrence
test retained. The old restoration audit and its tests are not repaired or
rewritten. Their vocabulary mutation continues to invoke the corrected method.

## Continuing qualification

`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`
remains controlling. This detector proves no transactional correctness,
operational evidence authenticity, authority safety, provider behavior or live
state property. Batch 1 cannot supersede the rejected restoration closure.
Only the separately sequenced Batch 2 audit after Batch 1 merge may adjudicate
the narrowed corrected claim.
