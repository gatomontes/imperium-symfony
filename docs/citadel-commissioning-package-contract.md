# CP2 — public preparation package contract v1

Run the standard-library checker from the preparation checkout:

```powershell
python tools/citadel_commissioning.py --package E:\owner\public-package --output E:\owner\reviews\preparation-0001.json
$preparationExit = $LASTEXITCODE
```

Paths above are illustrative, owner-created external public paths. The output parent must already exist. Exit **2** means consistent public preparation with explicit blockers; exit **1** is a fixed-code refusal. No exit grants authority. Input/assessed/source roots cannot contain the output. Existing output leaves, network/reparse parents and hardlinked public inputs refuse. The checker does not create directories, start Git or PHP, boot Symfony, traverse the installation, invoke StateStore or collect public runtime records. Its sole reads are allowlisted public files in the explicit package.

The example and JSON schema describe `proposal.json`. Nested objects reject extra/missing fields; duplicate JSON keys, floats/non-finite numbers, unsupported identifiers, malformed base64, mismatched fingerprint/commit/tree, unsupported policy/effects and compatibility choices refuse. The shared parser is reused without widening `citadel_native_lineage.py`. Its bounds are 1 MiB per file and depth 64, at most five files (5 MiB); PHP's later public command input limit remains 1 MiB/depth 48. No URL/path supplied inside evidence is followed except the two exact filenames below.

`assessment.json` has exactly `{schema, installation, target, observed_at, tracked_changes, target_verified}`. Schema is `imperium.commissioning-source-assessment/v1`; installation is `{root,commit,tree}`, target is `{commit,tree}` bound to the fixed accepted runtime target. `observed_at` is a positive Unix integer from the observation; tracked_changes is the bounded list of status/name strings; target_verified must be true. This is supplied source metadata, not authenticated deployment evidence. The checker checks equality/shape only and never independently certifies Git, running processes or custody. Independently review the retained CP0 command transcript.

`evidence-index.json` is exactly `{schema:"imperium.commissioning-evidence-index/v1", records:{garrison:null,recruiter:null}}`, replacing either null only with `{file,sha256,observed_at}`. File must be exactly `garrison.json` or `recruiter.json` for that slot. The checker verifies raw-byte SHA-256, declared public schema and observation age. It does **not** validate complete native evidence semantics, signatures, historical producer execution or currentness. Real structural acceptance still requires the actual PHP inspectors and supported runtime checks at the later authorized phase. This distinction is present in machine output. Unrelated files are never read or exported.

Assessment/evidence metadata older than 24 hours or future-dated refuses as stale for a new preparation check. This is a conservative preparation freshness rule, not a protocol validity rule or owner-approved observation window. Hashing or relabelling an old collection today cannot make it current. Retained historical evidence stays separately indexed and retains its original observation time; it need not be discarded or recollected just to complete preparation. The current real-input proposal therefore leaves the fresh evidence slots null and records the retained Garrison original separately.

Null owner identity, key, time, procedure or evidence values produce named missing-input blockers and still allow a useful review result. `approved` is a self-declaration only: even true emits `SELF_DECLARED_APPROVAL_HAS_NO_AUTHORITY`. Decision strings are proposed procedure references, not verified approvals. `DEFER_ENROLLMENT` and `BOUNDED_NATIVE_ONLY` are the only compatibility proposals. Neither changes operational authority.

Output separates `structural_status`, `completeness`, `source_compatibility`, `owner_review` and `operational_authority`. The five flags deployment_approved, enrollment_authorized, live_ready, activation and execution_authority are always false, including complete synthetic proposals. The helper is intentionally a public package checker, not a production trust/admission resolver. It assumes custodian-controlled, stable parents during output creation; it does not claim an OS sandbox or administrator-proof filesystem boundary.
