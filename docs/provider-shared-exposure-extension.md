# PPC10 source-extension disposition

The owner approved the additional `SharedExposure::formation()` v5 dispatch edit on 2026-09-16 after the single-file approval question and plain-language explanation. The [approval record](handoffs/provider-shared-exposure-extension-approval.json) adds exactly one source exception to the unchanged C1/C2 proposal and inventory. It does not modify their historical bytes or approve runtime acceptance.

## Current evidence

The uploaded packet SHA-256 is `cde573decd3831cb653c38831fbff971746f3d5c5bf792707ced9a78fe20e675`; final candidate `995aca15329bacfd4891f53b0743b86a6b71996a`, tested candidate `045b6f1142349193144fb68b244378bb54875a16`. Receiving reran its verifier successfully: 3,705 source files, 587 evidence files, 82 baseline observations, 60 positive fitness signatures, 18 alternate-negative signatures, two invalid controls and 312 linked frames. Retained suite coverage is 4,107 cases / 69,616 assertions, three skips. This is evidence verification, not a PHP rerun or full source acceptance.

On receiving Linux, the unchanged Python guard suite ran 11 tests: nine passed, including the previously unavailable changed-file-type symlink test; two PHP-tokenizer tests skipped because PHP is unavailable. Fresh complete hosted CI and full native v5 proof remain outstanding.

## Additional source findings and subsequent approval

Two further readers explicitly accept only v3/v4. `AugurMigration::validate()` rejects v5 while checking the retained original migration history; `FoundingRule::derive()` rejects v5 before deriving the exact original founding tuple. `StateValidation` calls the former for Augur-bearing state. Retaining these originals requires explicit v5 compatibility when the new validator consumes them. Changing a schema label for a probe does not prove a supported v5 owner.

The [two-reader proposal](provider-v5-reader-extension-proposal.json) records exact source hashes and a narrow proposed delta: append v5 to the allowlist in each named method. All remaining method logic stays exact, including migration originals and founding selection. `AugurMigration::migrate()` is excluded. These two files were not included in the first single-file approval. The owner subsequently approved the exact two-reader proposal and documentation push/merge; see the [separate approval record](handoffs/provider-v5-reader-extension-approval.json). The proposal retains its historical pending bytes; this new record supersedes only its authorization status. No runtime source is changed by this documentation handoff.

## Continuation flow

1. Preserve the verified component candidate and its failed/passing evidence. Components remain unaccepted; do not merge them into main as a completed R3 outcome.
2. Apply the recorded SharedExposure source exception only with the approved closed v5 validator and integrated proof. Continue independent C1/C2 work within the original inventory.
3. Implement the two additional reader allowlist entries under the separate exact approval, preserving migration-history validation and founding derivation. Do not project v5 as v4, disable history validation, or introduce new founding authority.
4. Complete authentic scope admission/migration, W1/W2/W3 custody and settlement, initial A/B application, each Seat replacement and fresh-process whole-pair protected use, followed by every original adversarial/race/crash row.
5. Return local commits and the complete public review packet for receiving source review and fresh unchanged hosted CI.

Use the [isolated-worktree handoff](handoffs/provider-v5-extension-ready.md). The [local continuation prompt](handoffs/provider-shared-exposure-continuation-prompt.txt) preserves the original proof plan and source boundaries. Working estimate is **4–5**, revised from 3–4 because the implementation/receiving batch split; decrement zero. R1/R2/R4 remain accepted; R3 and R5 open; R6 separately authorized and deferred. All five flags false; `DEFER_ENROLLMENT`; retry allowlist `[]`.
