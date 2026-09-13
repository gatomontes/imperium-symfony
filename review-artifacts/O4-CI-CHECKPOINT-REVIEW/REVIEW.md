# O4-B0 CI checkpoint review

Disposition: HOLD. The upload is an internally consistent diagnostic checkpoint. It contains no production correction and no new complete full-suite pass. PR #798 remains open, draft and unmerged at `730674d642e250edf0c489158de0c793559ecb3f`.

## Independent checks

- All 69 manifest payload sizes and SHA-256 hashes pass, with no unlisted payloads. Inner review ZIP checksum and both ZIP integrity checks pass.
- All 3,456 source archive files match Git blobs fetched from the current candidate. Candidate tree: `892afbd68ad62bdc2eba9bc14927dff2e72827d2`. Uploaded local entry/final identity remains `8fb6323e615432a949ce4bf599389d501650b2df`.
- All 3,456 entry/final Git comparison rows agree with those blobs.
- The owning-boundary diagnostic's 1,859 src/tests PHP hashes match before and after. Only AtomicTransition.php and Act.php differ from production, and their bytes match the supplied instrumented-file archive. This manifest covers src/tests; it is not the broader 1,885-file production PHP inventory.
- Recomputed all 30 caller groups from 113 raw boundaries. They agree with the supplied summary: 152,095 act verification calls, none outside the measured callbacks. Independently summed caller-stack counters reproduce 113 journal reads, 722 StateValidation calls and 152,095 act verifications.

This review checks source bytes, instrumentation and recorded evidence. It does not claim to have independently executed these PHP diagnostics. The uploaded fresh production selection records 9 tests / 2,095 assertions and 86 static checks passing. Four diagnostic producer runs repeat the same 1-test / 55-assertion selection; they are not disjoint coverage.

## What the evidence establishes

The leading two custody-checkpoint groups total 20 boundaries, 58,625 act verifications and 47.8875405 seconds, out of 147.1162842 measured callback seconds. This is approximately 32.6% of measured callback time in one producer test. It is not a measured pure-computation saving or a full-suite percentage. The callback includes journal loading, transition work, validation and publication; it excludes lock acquisition. Inclusive nested timings must not be added as exclusive costs.

Source inspection confirms that CustodyCoordinator::checkpoint invokes check, records the next custody stage and validates the resulting ledger within changeAtHead. Act::verify observes current trust/time/revocation, verifies object bytes and the signature, and can recursively check policy sources. High invocation counts do not make these observations redundant. Preserve the stage and authority boundaries.

The reference encoder experiment covers 23 value/error cases and only a small isolated operation. The transition-scope experiment changes reuse lifetime and adds an onboarding dependency to shared persistence. Neither has a controlled production benchmark or the regression evidence needed for promotion. Keeping both outside production is appropriate.

Even eliminating the measured custody groups entirely could remove at most their recorded 47.9 seconds from this particular diagnostic run; elimination is neither permissible nor proposed. A realistic correction saves only part of that time. The remaining 99.2 callback seconds and the other slow tests therefore still matter. A one-producer improvement alone cannot establish completion.

## Gate and next action

The inherited local full run timed out after 1,800.722 seconds. Hosted run [34736218407](https://github.com/gatomontes/imperium-symfony/actions/runs/34736218407) was canceled at the unchanged 30-minute allowance without a complete PHPUnit summary. This upload changes none of that evidence. No repeated full CI was launched on unchanged source.

Continue with NEXT-INSTRUCTIONS.md. No merge, gate weakening, activation or O5 transition is justified by this checkpoint.
