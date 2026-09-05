# Mission amendment Batch 3 process proof

Batch2 full suite passed on df3ee368bd6bad0c971ea23cd15422d3fb11f77a: 2669 / 52599, zero skips, 7m30.504s. Symfony regenerated config/reference.php doc comments during the full run (HTTP client default and sortie environment references); the exact diff is retained locally and the generated comments restored. No runtime behavior changed and no generated change is included in this campaign.

Batch3 adds real-process before-dispatch barriers with PID readiness and explicit release order. Inspection-before-activation remains A history; activation-before-inspection rejects A. Two released competing signed proposals have one winner and one PMA_STALE_PREDECESSOR. Both revocation/activation orders are forced. Completion before activation closes the same mission identity. A dying process publishes derivation without responding: restart challenge-status finds the exact successor and replay refuses without a new frame. Existing consumption crash-tail/lost-response tests remain.

These barriers control transaction order through the actual public owner, with both workers alive; they are not instruction-level instrumentation inside the lock. Source review confirms one common journal lock and full-frame publication for all cooperating operations. Hardware power-loss and hostile writers remain unproved/outside this boundary.

Final added proofs cap pending records at 64 while preserving authority and approved challenge, then successfully consume and activate despite capacity refusal. Cancellation prevents re-entry. FAILED-state defense is explicitly a disposable corruption test because no public FAILED producer exists in this three-transition protocol. Mixed schema refuses. Activation now validates predecessor lifecycle binding and terminal state as well as tombstone, and status gives a precise terminal-new-identity instruction.

Focused before commit: 26 tests / 404 assertions, zero skips. Earlier 24 / 390 run and initial PHPUnit reserved helper-name collision are preserved. PowerShell initially refused an unordered request object's field order; the harness now uses [ordered] maps rather than loosening protocol validation. It passed an actual Git A-inspect/B-amend/B-complete rejection followed by fresh B execution, checking exact committed bytes and unchanged whole target.

Script: tests/Imperium/Runtime/Support/mission_amendment_powershell.ps1.
No installer was run (including dry-run), no actual identity/ACL provisioned, no real trust/private key/mission and no publication. Commit this batch before the separate exact-head audit; this note does not claim that later audit already passed.
