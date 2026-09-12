# O3-B0 correction review instructions

This is a local correction handoff, not acceptance or permission to activate.

1. Read the correction report, correction design note and `identities.json`. Start from the declared entry commit in a clean isolated checkout; do not reset an existing dirty worktree.
2. Verify `payload-manifest.json`, the inner review ZIP SHA-256, `review-manifest.json`, and `git bundle verify implementation.bundle`. Fetch the bundle's named branch into a new local review branch or apply `implementation.patch` after `git apply --check`. Check the reconstructed final tree against `identities.json`.
3. Check canonical source copies, tested-PHP hashes/byte snapshot and protected-original comparisons. The only modified originals are AccessAdapter.php, Runtime.php and the explicitly justified CI workflow. The attached reviewer PHP must remain byte-identical to the supplied original.
4. Review strict source-object identity before construction effects, readonly lifetime binding, and the unchanged original-backed currentness checks at every custody boundary. Review new failure/recovery cases alongside existing positive access/replay and O2 reviewer coverage. No caller flag or generation captured from an unchecked source is authority.
5. Reproduce `final-commands.json` with PHP >=8.4 and locked dependencies. Use a temporary fixture environment and mock HTTP only. Read failed development evidence separately from final results.
6. Obtain independent source review and a fresh completed hosted run on the corrected source. The focused reviewer step must pass, followed by the entire unchanged `vendor/bin/phpunit tests` command. Verify hosted checkout commit/tree, dependency lock, completion summary and numeric exit. The proposed 30-minute job budget needs confirmation; incomplete/cancelled runs are not passes.
7. Stop within O3-B0 until acceptance. This local handoff did not push or merge. O3-B1, founding/cognition authority, activation, assignments, CLI, OAuth, enrollment, real provider calls and installed-state operations remain outside scope.
