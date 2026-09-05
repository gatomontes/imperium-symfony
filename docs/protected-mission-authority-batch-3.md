# Batch 3 — executable canonical ceremony

Batch 2 full suite tested exact commit `d7901c8ae768353fc1cdeac1c57ab207667d3969`:
**2656 tests / 52392 assertions**, no skips, 7m23.399s. Transcript:
`var/protected-mission-evidence/batch-2-full.txt` (local, ignored).

Canonical review now supports a non-persisting preview, with exact actor, time, response and IDs.
Authenticated review verifies exact pending bytes and canonical dossier before persisting signature
provenance. The owner publishes challenge consumption and the review in one journal frame.
Derivation uses the existing canonical derivation service. Scratch canonical files are internal
transaction workspaces and are removed before publication; they are not independent authority.
Crash residue under scratch is never consumed. The protected owner never imports project-local
approvals. The legacy trusted-internal review API remains for existing applications/tests; its
unsigned records cannot pass the protected consumption boundary.

Focused result: **12 tests / 227 assertions**, no skips, covering Batches 1–3 plus unchanged
canonical assembly/review tests. The CLI test executes each stage in separate PHP processes,
including a separately held disposable signing key over stdin. It verifies stable exact bytes,
numbered rendering, no authority before submission, signature persistence, replay refusal and
read-only verification. PowerShell 7.6.5 helper round trip also passed; sanitized transcript:
`C:/Users/gatom/AppData/Local/Temp/imperium-protected-powershell-24ffdf47b8a24574b9c4bcb8caa03d77/sanitized-powershell-transcript.json`.

Fixture adaptation: Batch 2 now obtains its fresh authority via prepare/export/sign/submit/derive;
it no longer fabricates its initial canonical journal. Its terminal assertion adds the inspection
state and now requires three transitions/history entries rather than two. No rejection or historical
assertion was removed to obtain green. The explicit corruption helper remains test-only.

Human commands and installation limits: `docs/protected-mission-operator-runbook.md`.
No real key, trust, account, ACL or mission was used. Runtime installation/account isolation is
unmeasured. The actual mission execution/evidence proof remains Batch 4 work.
