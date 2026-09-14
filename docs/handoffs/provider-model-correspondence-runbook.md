# PPC5 public reproduction runbook

1. Verify the separate ZIP SHA-256, then run `verify-public-review.py` from the
   packet against that ZIP and a repository containing prerequisite
   `c93656f3be1766409f500909668caba89cda6ea2`. The verifier imports the bounded
   bundle into a disposable bare repository and checks exact membership, modes,
   bytes/hashes, Git identities, all three diffs and protected source bytes.
2. Read the frozen A–F proposal and separate approval record. Its exact UTF-8 Git
   blob SHA-256 is `8c4d9992d57d1ea98ae14e4fd4abfe6971f316dd87443388e9a31a34538998e8`.
   The historical proposed heading is not its current approval disposition.
3. Check out the packet's tested commit in a fresh disposable worktree. Install
   locked dependencies with the unchanged workflow's Composer command. Record
   platform/tools, commit/tree and clean executable source before testing.
4. Run the two new FormationModelPreparation test classes. Set
   `PPC5_PUBLIC_EVIDENCE` to a fresh public evidence directory to retain selected
   public institution originals, complete lifecycle evidence and process events.
   Run `verify-public-originals.php` against its `originals` directory; it uses
   only public keys and proves integrity, never external model facts or current
   production authority. The independent Python counterpart additionally checks
   the complete native/O4 and lifecycle reference chain, using `cryptography`
   (46.0.7 in this run); it changes no project dependency. The packet verifier
   reruns that public-only check. `active-public-proof.json` selects the final
   originals and excludes earlier diagnostic counts. Do not copy ephemeral
   private keys or runtime roots.
5. Run the unchanged credential-binding regression, all eight partitions through
   `tools/ci/phpunit_partitions.py run <0..7> 8 <output>`, all 11 guards with
   `python -m unittest discover -s tools/ci -p 'test_*.py' -v`, and the unchanged
   aggregate `verify <evidence> 8 <tested-tree>`. Retain every native exit, UTC
   interval, JUnit count/warning/skip/error and before/after source identity.
6. The supplied external Windows launcher only supplies PHP for its extensionless
   entry and removes unsupported Windows extended-path prefixes from PHP arguments.
   It does not alter guard/worker logic or test selection. A host capable of
   creating symlinks is needed to complete the unchanged Python file-type guard.
   Do not convert a Windows privilege error into a pass or skip.
   This run's local partition 2 also exceeded the unchanged hosted 30-minute job
   limit. Direct-script completion does not establish hosted timing acceptance;
   retain that distinction and require the fresh hosted gate to pass its limit.
7. Confirm tested-to-final is documentation only, and the source/approval/countdown
   pins remain unchanged. Receiving source review and fresh complete hosted CI
   must precede runtime publication or integration.

No live step follows from this runbook. Use disposable institutions and ephemeral
fixture keys only. All five flags remain false, DEFER_ENROLLMENT remains, and the
actual retry allowlist is empty. R2 stays accepted; R3/R4/R5/R6 remain open.
Countdown is **4–6 before review**, potential **3–5 only after accepted complete
R1 closure with no new scope**. Submission earns no decrement.
