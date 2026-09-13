# O4-B0 CI completion correction — source review

The two pure computation changes pass source review and independent selected runtime validation. Full-suite completion remains a separate gate; its final result is recorded in FINAL-DISPOSITION.md.

## Identity

- Entry: eba7efd6a97c2c2e05f88056b8d1d56c1a86661e.
- Tested: ac8c80c97161a95e6a9f84de6012149dbcc8a544.
- Uploaded final: 8fb6323e615432a949ce4bf599389d501650b2df.
- Final tree: 892afbd68ad62bdc2eba9bc14927dff2e72827d2.
- Remote equivalent: 730674d642e250edf0c489158de0c793559ecb3f in PR #798.

All 1,885 tested PHP files match final. Only report/proof documentation followed the tested source.

## Computation equivalence

StrictJson::string uses native strcspn to advance across ordinary bytes. Its quote/backslash handling, escape offset, json_decode validation, UTF-8 admission, duplicate-key detection and size/depth limits remain. The added parser oracle is exactly the entry parser apart from its namespace and class name. Differential cases cover malformed and valid strings, byte values, escapes, control characters, Unicode, long inputs and boundaries, including scoped parse reuse.

CanonicalJson::sort recurses and assigns only array-valued children. The entry function returned non-arrays unchanged. Array sorting, scalar types, json_encode flags, object serialization and error behavior remain. Differential tests cover nested values, numeric keys, scalar/array aliases, JsonSerializable side effects and encoding failures.

Only these two existing runtime files change. No new cache remains; the rejected reference-encoding cache appears only in labeled diagnostic evidence. R1's aggregate/instance/Profile/configuration checks, authority owners, credential custody, state validation, journal chain and lock implementation are unchanged. The existing bounded parse/encoding reuse policy is unchanged.

No additional blocking source defect was established in this review. This source conclusion does not replace the full acceptance gate.

## Independent hosted validation

Run: https://github.com/gatomontes/imperium-symfony/actions/runs/34736243420

Diagnostic PR #801 is closed unmerged. Only its workflow differs from the candidate; production and tests match exactly. Verified checkout ac9a8a03e3d5c3b1d11278a817a72cf77a961fb1 has tree 7a218be7b8715cb115385ef32deeb0d7c5ceb0b6. Diagnostic commit: 50a6feaf71f15f0580ddcf8766b68db58f50daec. PHP 8.4.25; PHPUnit 13.3.0.

| Selection | Tests | Assertions | PHPUnit duration |
| --- | ---: | ---: | --- |
| JSON differential and R2 scope checks | 9 | 2095 | 00:00.418 |
| Original foreign aggregate/Profile probe | 1 | 5 | 01:35.076 |
| Real ordinary/custodied success, recovery and negatives | 1 | 45 | 06:09.168 |
| Same aggregate, foreign instance | 1 | 4 | 02:00.189 |
| Credential-consumption currentness loss | 1 | 8 | 02:38.467 |

All selected cases passed: **13 tests, 2,157 assertions**. These selections are disjoint within this diagnostic run. Jobs ran concurrently; their durations are not one sequential wall-clock total. The unchanged original reviewer probe records zero transport calls and O2_SETTINGS_FOREIGN_AGGREGATE. No real provider call occurs.

## Integrity and preservation

Verified all 2,221 manifest payloads and inner ZIP integrity; all 3,456 complete source archive files match Git. All four patches reconstruct the exact final tree. All 1,885 tested/final PHP files and sixteen final before/after PHP manifests match. All 842 protected originals are unchanged. All 86 fresh static checks pass.

The current original-file comparison matches actual entry/final Git blobs for all 3,450 rows. The repaired historical R1 comparison exactly matches the independent 3,440-row correction, including the four older documents previously misreported. The metadata defect is resolved.

## Recorded profiling: what it establishes

The submitted isolated producer diagnostic improved from 171.214 to 145.461 seconds, approximately 15%. Diagnostic instrumentation changed between runs and incidental source-manifest reads are disclosed; this is not a precise hosted speed guarantee. The separate final phase diagnostic records 135.219 user CPU seconds and 2.000 kernel CPU seconds over 137.844 elapsed seconds. Lock and file-read measurements are small in that run. These are audited recorded measurements, not independently rerun benchmarks.

The computation counts are unchanged between the entry and final producer diagnostics: 10,019,483 canonical encodes, 104,865 strict parses and 113 journal latest calls. The final detailed profile also records 4,179,373 Rules::same calls, 885,468 Rules::record calls, 152,095 Act::verify calls and 722 ledger StateValidation::run calls. Timings are inclusive and overlap; do not sum them into a CPU breakdown.

These measurements establish a useful optimization increment. They do not establish completion of the complete suite or identify one function as the cause of every timeout. Further diagnosis, if needed, should attribute repeated work across the complete suite and preserve every authority/currentness boundary. A rejected reference cache is not a recommended correction.

Recorded final local selections and logs are summarized separately. The unchanged complete local command still timed out at 1,800.722 seconds with exit 124 and no complete summary. The report correctly leaves completion unresolved.

## Scope

All operational flags remain false and the actual retry allowlist remains empty. Original tests, R1 evidence, workflow, dependencies and service wiring are preserved. No merge, activation or O5 transition is performed by this review.
