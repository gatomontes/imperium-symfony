# Citadel correction: independent review handoff

Status: `LOCAL_CORRECTION_COMPLETE_PENDING_INDEPENDENT_REVIEW`.
CF01 and CF02: CLOSED_LOCAL with fresh failing and passing evidence.

Review [the correction report](../citadel-formation-correction-report.md),
[changed-test map](../citadel-formation-correction-changed-tests.md) and
[runtime contract](../../contracts/citadel-formation-runtime.md).
The preserved original candidate is e0386e75ce7619fbbeaac450af078d2d606df012.
The local correction branch is `codex/citadel-session-handoff-correction`.

Review CF01 terminal control/admission refusal and unchanged budget accounting.
Review CF02's real child rename interruption, current-versus-historical authority
split, exact retained fence and source/target content, native publication witnesses,
revocation ordering, concurrent recognition and expired-new-effect refusals.
The historical manually inserted receipt test now correctly expects a fence;
its original source and passing transcript remain historical evidence.

The ZIP contains failing C0 traces, focused/full JUnit and command transcripts,
normal formation and correction command/DI demonstrations, a public signature
cross-check and byte-preservation results. Verify both the ZIP hash in the outer
manifest and every entry in `MANIFEST.sha256`. Use the recorded exact tested Git
commit and locked dependencies for a full-suite rerun. The allowlisted source
packet is not a replacement for every historical repository fixture.

No permission to integrate, commission, activate, invoke live providers or execute
a mission follows from this packet. Corrected commits remain local. Missing
historical custody evidence stays fenced. Independent review remains required.

## Exact verified candidate

Executable commit: `1a978ae42fbeaab55437768b88818fb40ba88676`.
Executable tree: `7ef164554789ca46c51dd00f0d846d2b8b82c967`.
Full suite: 2,729 tests / 53,069 assertions, exit 0, no
failures/errors/skips; four unchanged historical `.git/HEAD` warnings.
Focused suite: 39/274 on ff650b88; final affected-path recheck: 6/55 with warnings
fatal on the executable commit above. All 40 formation/correction cases are in
the final full-suite run. Both command/DI demonstrations and container lint pass.

The outer manifest and `REVIEW-IDENTITY.json` name the separate final documentation
commit/tree and list its documentation-only delta. The package rejects executable
or contract changes after testing. Corrected commits have not been pushed.

The original packet remains byte-identical at SHA-256
`9f2f8196246edb9c11721ac7f6f890c7503ddd06ef695e14ccd4a9be372773da`.
CF01 refusal is terminal without budget reset. CF02 recovery preserves exact
historical effects without new authority; missing provenance remains fenced.
The remaining gate is independent review, not another owner ceremony or live test.
