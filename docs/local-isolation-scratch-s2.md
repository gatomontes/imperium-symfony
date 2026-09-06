# S2 — committed audit candidate

S0: 3d562b3c. S1: 3c880d7e. Exact full identities and trees are recorded in
local-isolation-scratch-audit.json after verification. S2 commits executable
and test work before the definitive focused/full PHP and Windows runs.

Changed-test attribution:

- LocalIsolationScratchTest: four new methods covering actual canonical-service
  cleanup/refusal, abandoned/missing/substituted roots, exact-workspace cleanup,
  native ACL operations, cleanup failure and real junction refusal.
- ProtectedMissionFixture and protected_mission_cli.php: provision the new sibling
  only in explicit disposable test setup; no production root creation fallback.
- ProtectedMissionAuthorityBatch5AuditTest::testPendingExpiryAndNonCanonicalSigningRefuseWithoutApproval:
  allow three seconds rather than one to prepare/export before crossing real
  expiry. The wait and every expired-submit, unchanged-journal, EXPIRED status,
  absent authority and noncanonical-signing assertion remain unchanged. Two
  precommit focused attempts failed before export on the one-second boundary.
- All LI01/LI02 adversarial cases, AM01/AM02 and receipt/generation assertions
  remain. New tests do not replace historical native or synthetic tests.

The new review assembler includes scratch sources and the pending native owner
harness, retains earlier audit sources as historical, and hashes a fresh Markdown
packet plus a derivative JSON manifest. The old assemblers/packages are untouched.
The package manifest includes the new resolver, shared ACL policy and owner-proof
tools. The scratch root is an empty administrator-provisioned directory, not a
prepopulated package with mutable authority or temporary files.

Current steps: S0 reproduced; S1 implementation and component proofs completed;
S2 committed verification/package/review preparation; equivalent native ceremony
pending owner-run disposable proof. Parent 3–5 remain conditional on independent
review and authentic owner setup, actual measurement and fresh independent approval.
No real installation, account creation, secrets, journal reset, implementation
push/main merge, provider action or target mutation is performed by this task.

Status remains SCRATCH_WORKSPACE_CORRECTION_NATIVE_PROOF_PENDING until the exact
separate-account harness passes. Neither passing PHPUnit nor the fresh manifest
changes that status. No deployment acceptance is claimed.
