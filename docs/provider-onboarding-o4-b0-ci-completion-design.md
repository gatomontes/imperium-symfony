# O4-B0 CI completion correction

Entry: eba7efd6a97c2c2e05f88056b8d1d56c1a86661e, tree adb7562369490a754aa01943254c0da12ebf7fe0. The accepted R1 consumer binding is unchanged.

## Measured scope

The isolated unchanged AssignmentAssessmentResolverTest passed in 171.214 seconds with diagnostic method timers: CanonicalJson::encode ran 10,019,483 times (45.728 seconds inclusive), StrictJson::parse ran 104,865 times (21.695 seconds), and FormationJournal::latest ran 113 times (42.606 seconds). The candidate passed in 145.461 seconds; the same counts remain. Final canonical encoding took 35.949 seconds, strict parsing 11.886 seconds, and journal traversal 36.547 seconds. Times overlap; instrumented timings are not acceptance or hosted predictions. The final diagnostic included incidental source-manifest file reads, disclosed rather than treated as a controlled benchmark.

A bounded reference-encoding cache prototype was rejected: it reduced encoder calls but increased measured elapsed time from 157.421 seconds (scanner-only) to 164.832 seconds. Its sources and logs remain diagnostic evidence; it is absent from the candidate.

## Two equivalent computations

StrictJson string scanning now uses native strcspn to find the next quote/backslash instead of walking ordinary bytes in PHP. Escape skipping, offsets, JSON string decoding, UTF-8, duplicate-key checks, numeric/depth/input bounds and exceptions retain entry behavior. Existing R2 storage and synchronous lifetime are untouched. An additive differential test uses the entry parser as an oracle for valid/malformed strings, all byte values, escapes, controls, Unicode, large strings and bounds.

CanonicalJson recurses and assigns only array-valued children. The entry recursive function returns every non-array unchanged. Top-level non-array handling, lexicographic key sorting, json_encode flags, object serialization and errors are unchanged. Additive differential tests cover typed scalars, nested arrays, numeric keys, object side effects, aliases and encoding failures. No canonical bytes or journal formats intentionally change.

## Boundaries

No validation result, signature, time, currentness, revocation, authority, Profile, configuration, generation, permission or journal-chain result is cached or removed. No new cache is retained. FormationJournal, locks, resource/custody/irreversible fences, original signed bytes, all existing tests/fixtures, service configuration, workflow and dependency locks are unchanged. The unchanged full command and 30-minute allowance remain required. All five operational flags remain false and actual retries remain disabled. This remains offline synthetic work; source review and fresh hosted CI are required before integration.
