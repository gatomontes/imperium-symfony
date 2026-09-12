# O3-B1 R2 source review

The submitted optimization passes source review. Hosted full-CI acceptance is recorded separately; this source conclusion does not treat the local timeout as a pass.

Only six existing production PHP files change from R2 entry, plus one new regression test file. The source changes wrap existing validation in a bounded parsing/encoding scope. The bodies of source traversal, state validation, base projection and Augur context resolution remain in place. R1 MappingLimits, its tests and the original full-CI workflow are byte-identical.

StrictJson retains only successful parses of exact raw input bytes. A SHA-256 index must also match the complete raw string. Input size checks precede hashing; the underlying parser and its malformed UTF-8/BOM/duplicate-key/depth/integer checks are unchanged. At most 128 input strings totaling 2 MiB are retained per outer scope. Overflow and collisions fall back to parsing; failed parses are not stored.

Canonical encoding reuse requires complete strict equality with an internally retained, object-free parsed record, or its exact record_digest-removed form. Schema and ID are candidate indexes only. Changed content/digests, different records with reused identities and arbitrary objects use the original encoder. Canonical storage has its own 2 MiB limit. The code retains neither a successful comparison nor a record/signature/authority decision. The full-value comparison is repeated on every lookup; returned PHP values cannot mutate private parsed snapshots through ordinary copy/reference modifications.

The outer scope clears all retained values in finally. Nested calls share only this pure bounded computation. Source graph visits and depth/cycle checks still execute, including propagation of the visits reference through the new wrapper. Current clocks, issuer/policy/act revocation, exact retained bytes, signature verification, root/holder/source identity and KeySource generation remain checked at the existing boundaries. No journal format, history traversal, migration interpretation, lock ownership or lock/I/O boundary changes.

The new tests cover successful/malformed input under warm scopes, nested and exceptional cleanup, size/count limits, fallback parsing, returned-value/reference mutation, modified stored raw bytes, changed record content/digests, canonical encoding errors and object side effects, clock/revocation changes and graph bounds. Existing campaign tests and fixtures are unchanged. No source correctness blocker was found in the reviewed delta.

Package verification independently checks 318 manifest entries, 63 canonical changed files, all 1853 tested PHP byte streams against both tested and final Git, both exact patch reconstructions, inner ZIP checksum and all protected-original comparisons. Static specification: 86 passed. Diff check passed. The tested/final PHP identity is e913a83dc1073be8e7fa236acafb2d05fa993c27 / 8045884d62391cfa6ce55180bd439ac2104384cd; final tree f7e10635a7d6992ea50de4a47ef2654e943e1ff7.

Supplied before/after profiling was checked against all 11 original source hashes at each recorded commit. The transformer wraps methods with timing entry/finally instrumentation and does not seed fixture state. Measured authority/traversal call counts, frame counts and journal byte totals match. W1 falls from 252.4654816 to 41.5745369 seconds (6.07x); readiness improves 4.35x. These are local instrumented Windows measurements, not hosted runtime predictions. They support reduction of redundant computation without fewer checked authority transitions; they are not a substitute for source review or CI.

Supplied selected local evidence totals 883 tests / 8615 assertions. The final complete local command hit its 1800-second ceiling, exit 124, and has no complete test/assertion total. The last full progress row was 1037 / 3664 followed by additional dots; it is not the exact executed count at termination. The generated config/reference.php PHPDoc reconciliation is documented; the final tracked PHP snapshot matches Git. Local PHP is unavailable in this review workspace; independent runtime execution uses hosted CI.

Published implementation head: c15c8e37802cefd5fec07a106f4626a7ce17d8ab.
PR: https://github.com/gatomontes/imperium-symfony/pull/794
Fresh full run: https://github.com/gatomontes/imperium-symfony/actions/runs/34703567912
Actual CI checkout: 1fb168aadb30f6048602353bfd0609af765331b9; GitHub tree independently verified equal to f7e10635a7d6992ea50de4a47ef2654e943e1ff7. The final job log confirms this actual checkout.

All flags remain false, retries empty, and no actual provider or installed-state operation is authorized. Review publication does not merge the implementation.
