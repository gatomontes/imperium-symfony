# Amendment correction Batch 1

Inert prepare now appends only its own pending record, capped at 64 retained proposals with PMA_PROPOSAL_CAPACITY and no eviction. Existing challenge bytes and authority/progress survive unsigned and invalid submissions. Export discloses activation operation and expected predecessor authorization ID/digest (explicit null for first approval). Canonical authenticated review binds those bytes. Derive re-verifies signature, compares exact current predecessor and publishes retirement plus successor in one owner journal transaction. Competing signed proposals remain independently usable until activation; stale derive refuses without a partial successor. Cancellation/revocation remain signed control.

Focused command: php vendor/bin/phpunit --filter 'ProtectedMissionAuthority|MissionAmendment' tests.
Result before this commit: 18 tests / 240 assertions; no skips. Transcript: var/mission-amendment-evidence/batch-1-focused.txt. Batch2 generation binding is still outstanding.

Changed-test map: ProtectedMissionAuthorityBatch3Test::testBadSignaturesAmendmentCancellationAndInvalidPlansLeaveNoAuthorityResidue formerly required unsigned preparation to supersede another challenge. AM01 explicitly supersedes that policy. It now asserts exact prior payload equality and successful authenticated submission while retaining all signature, cancellation, invalid-plan and no-residue assertions. No tests removed.
