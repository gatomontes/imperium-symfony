# Activation Disposition Vocabulary Tripwire Correction Batch 2 Blackquill audit

## Verdict

`BATCH_2_TERMINAL_BLACKQUILL_AUDIT_PASSED_LITERAL_VOCABULARY_CLAIM`

Campaign result:
`ACTIVATION_DISPOSITION_VOCABULARY_TRIPWIRE_CORRECTION_COMPLETE`.

The corrected closure is accepted for the explicit literal-value boundary:
`FROZEN_RUNTIME_COVERAGE_TRIPWIRE_RESTORATION_CORRECTED_CLOSURE_ACCEPTED_LITERAL_VOCABULARY_ONLY`.
This supersedes the rejected restoration closure as an active claim only at
that narrowed vocabulary boundary. It does not rehabilitate its broader wording
about all producers, independently re-audit unrelated runtime properties, or
alter the retained historical audit.

The controlling evidence posture remains
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`.

## Sequence and source

Audit entry was clean local main at
`76d0803ae9931c4470b4f49af216a56a14c111c3`, after the Batch 1 commit was
fast-forward merged. The audit branch and tests were created afterward. No audit
artifact was bundled in that implementation commit. This is a separately
sequenced local audit by the same agent, not a claim of independent human review,
remote PR approval or remote CI. The operator authorized uninterrupted completion;
the original prohibition on external I/O remains binding.

Sources reviewed: the merged Batch 1 detector, its complete test file and
versioned contract, both lexical matrices, the preparation inventory and
occurrence TSV, six-role admission TSV, Batch 1 handoff, campaign sequencing,
and the retained restoration audit and terminal test. Source comparison against
`d7da12722f5862147dcf7adbac7a400b0e77ef25` confirmed no changes to runtime source,
the six-role admission TSV, or the prior restoration audit/handoff/test.

## Claim and weak point

The defensible claim is exact path coverage for complete decoded occurrences of
two literal values in current runtime PHP source. The tempting, indefensible
claim is that every possible disposition producer has been discovered.

Those are different properties. The actual decision producer forwards an
authorized value without spelling either literal. Split construction and
indirection can also produce a governed value while remaining invisible to this
alarm. The implementation contract acknowledges that boundary rather than
passing off source vocabulary coverage as dataflow proof.

## Adversarial evidence

Fresh tests call
`ProviderBindingActivationIntegrityRemediationBatch6Test::testActivationDispositionVocabularyIsLimitedToExactClassifiedRoles`
on complete disposable copies of the current runtime and the six-role TSV.
They do not import the Batch 1 case matrices or a replacement detector. Expected
rejections are checked for exact array-equality failure, so an unrelated parse
failure cannot masquerade as a successful vocabulary rejection.

| Attack or control | Observed |
| --- | --- |
| Both governed values: single/double/binary quotes, complete hex encoding, mixed Unicode prefix, indented nowdoc, CRLF/indented escaped heredoc | Unclassified paths rejected |
| Exact literals nested inside interpolation expressions, match arms and array keys | Unclassified paths rejected; actual nested literals remain occurrences |
| Comments/docblocks, quoted vocabulary in larger strings, encoded larger value, single-quoted escape text | Accepted as non-matching text |
| Interpolated text/heredoc, split concatenation, constant-only reference, function assembly | Accepted under explicit out-of-contract limits; not proved safe |
| Quoted vocabulary after `__halt_compiler` | Accepted as non-executable data |
| Same file path, length and timestamp changed between exact and lowercase value | Each result follows current source bytes |
| Previously rejected source explicitly added to the disposable admission TSV | Passes |
| Same cached source after inventory admission is removed | Rejects again |
| Each of the six admitted paths replaced by a vocabulary-bearing comment | Rejects; restoring each original passes |

There are 40 fresh lexical cases plus baseline, cache/admission transitions and
12 individual comment-replacement/restoration checks across all six paths.
The fresh behavioral run passed: 2 tests, 1931 assertions on PHP 8.4.14 /
PHPUnit 13.3.0. The audit PHP file passed lint. The final combined regression
run is recorded in the completion handoff.

The Batch 1 proof also reruns 114 lexical cases, literal removals, all six quote
conversions, same-count path substitution, explicit admission, duplicate/stale
rows, malformed syntax diagnostics, repository fallback and override restoration.
The retained preparation test verifies all 16 current literal occurrences.

## Residual limits and closure adjudication

OUT_OF_CONTRACT_CONCAT, OUT_OF_CONTRACT_REFERENCE and OUT_OF_CONTRACT_DYNAMIC
remain explicit. No arbitrary expression folding, constant resolution, value
flow, semantic-role verification, hostile-inventory defense, symlink defense,
or generated/out-of-tree source coverage is claimed. Exact literals in dead
code or documentation messages count; larger decoded strings do not. This is
the governed literal-value contract, not a promise about execution intent.

The original double-quote escape and comment/larger-string false positives are
closed within that contract. No detector correction was needed during this
audit. The original rejection
`FROZEN_RUNTIME_COVERAGE_TRIPWIRE_RESTORATION_CLOSURE_REJECTED_WITH_MATERIAL_VOCABULARY_TRIPWIRE_GAP`
remains preserved as historical evidence; its replacement is the narrowed
corrected closure above, not an unconditional revival of the old prose.

No mission, provider, external I/O, live credential or capability, live runtime
state, Iron Gate or Lazaretto was exercised. No historical operational-evidence
audit was repaired. No transactional correctness, authority safety, operational
evidence authenticity or qualification removal is proved. A complete runtime
test-suite run and remote verification are not claimed.
