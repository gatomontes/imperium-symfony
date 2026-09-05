# S1 — protected scratch implementation

Dedicated fixed sibling: C:\ProgramData\Imperium\ProtectedMissionScratch.
ScratchWorkspace::root maps the trusted authority root to this sibling. No
request/environment/temp-path override exists in production. Install-ProtectedMission
provisions it with the shared ProtectedMissionScratch.ps1 policy builder.
Administrator owns the root; Runtime gets list/create-file/create-directory there
and inherited Modify on descendants. Caller receives no rights. Root delete,
delete-child, DAC and owner changes remain denied to Runtime. State/exchange
parent permissions are unchanged. Owner reference replacement protection is retained.

The journal lock serializes creation, canonical operations and cleanup. Cleanup
preflights only the exact newly created work directory, checks canonical paths and
links, and deletes children before parents. It never follows junctions/symlinks.
Failures leave incident contents and return PMA_SCRATCH_CLEANUP_FAILED before
journal publication. Unknown/busy/abandoned scratch refuses mutation; there is
no automatic removal, retry authority or journal migration. Worker staging uses
the same workspace lifecycle without changing inspection/receipt semantics.

Readiness inventories the protected empty root and validates its exact ACL policy.
There is no unbounded transient prefix exemption: external measurement while
work exists explicitly refuses as busy/abandoned. A successful internal ceremony
cleans before returning, so its nested writes never require human remeasurement.
New persistent exchange outputs still require current evidence as before.
Startup validates scratch for normal operations; only status/challenge-status
may skip scratch checks while still validating identity, code and authority state.
That recovery path cannot perform a scratch operation or mutate authority.

Native component proof performed: nested mkdir/write/rename/read/cleanup under
enforced ACL masks, wrong-owner policy refusal, actual NTFS junction refusal,
native deletion denial with preserved abandoned contents. Same-user ownership is
explicitly different from the intended installation, so this is not equivalent
ceremony proof. The complete disposable owner harness uses copies of production
installers, policy, startup, CLI, phase probes and readiness with recorded fixed
path relocation and new hashes. No checker/measurement mock is accepted there.

Native full ceremony status: NOT RUN — this session cannot provision the
administrator-owned layout and distinct standard account tokens. No account,
privileged token, password or real key was acquired. See the S2 owner runbook for
the exact executable proof; its preparation does not claim that it has passed.

Changed tests: only fixture setup adds the required scratch sibling in
ProtectedMissionFixture and the explicit test-only CLI enrollment adapter.
All existing test methods and assertions are preserved. LocalIsolationScratchTest
adds real canonical cleanup/refusal, absent/substituted roots, exact cleanup and
native component coverage. The first focused attempt exposed the unchanged
one-second expiry test's timing sensitivity (PMA_CHALLENGE_INACTIVE on export);
the dedicated rerun passed 7 tests / 36 assertions. No expiry assertion was weakened.
