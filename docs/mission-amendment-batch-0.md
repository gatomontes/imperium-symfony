# Amendment correction Batch 0

Implementation base: 7deb84b9f687ecb09c93f568464a606f0a602a89.
Documentation selection: 5b01f7d8a020daff2950ea408e5025438ef92196; only docs/ and todo/ differ from 0099c5f7e4008fe06cafc9c5dfa3458dc68f4db9.
Integration: 3d5a40f3b1918675f92ecdc848aea287d31fc7ab, parents exactly the implementation and selection above.
Only documentation overlap was delegate-mission-flow.md. Current amendment selection precedes retained old audit history. No AGENTS.md found in repository or E:/, E:/htdocs ancestors.

Measured against unchanged production sources at integration: public prepare makes A inactive (AM01); A admit/inspect followed by signed B with different paths completes using A findings (AM02). No journal mutation constructs either counterexample. Fresh disposable fixtures and test keys for each scenario. Reproduction: tools/reproduce-mission-amendment.php; sanitized output: docs/mission-amendment-reproduction.json. This historical exploit script is outside normal acceptance tests and is expected to fail after correction. Run at this batch only, never against real state.
Focused: php vendor/bin/phpunit --filter ProtectedMissionAuthority tests; 16 tests / 221 assertions, zero skips. Transcript var/mission-amendment-evidence/batch-0-focused.txt. This run uses the integration production tree plus the newly added reproduction script.

Policy frozen: unsigned proposals only append pending records, no mission-wide pending pointer. Exact signed activation binds predecessor ID/digest or explicit absence; canonical derivation and predecessor comparison share the journal lock with consume/control. Atomic journal frame publication is the linearization point. B starts AUTHORIZED; no state/evidence transfer. Terminal mission IDs cannot reopen. Bound pending storage, refuse capacity without eviction. Old schema refuses use and requires future owner-reviewed migration, never automatic reinterpretation.

Read set: runner, campaign, review; contracts mission-planning/mission-authorization and their La Cortine/seneschal references; protected owner, Ceremony, PublicTrust, CLI, InstalledRuntime, inspectors; canonical assembly/review/derivation; all five protected PHPUnit classes and fixture; previous audit handoff, audit/test disposition, ledger, runbook, current flow and checklist. Exact source blobs are recorded in the campaign reading ledger.

Affected expectations: Batch3's unsigned preparation supersedes a pending challenge must become continued challenge usability. Batch4's explicit corruption fixture must address the new authorization generation; preserve missing-evidence refusal. Other useful tests retained. Prior 2665 / 52515 at f12524f2b25942b8149e8c455a39da5d26217a9b and later documentation 7deb84b remain historical, not corrected-head evidence. Main and all three preserved candidate/quarantine refs remain untouched.

DEPLOYMENT_ISOLATION_UNPROVED_OPERATOR_SETUP_REQUIRED. No deployment, installer, real trust/key/mission or publication.
