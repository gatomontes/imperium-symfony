# O4-B0 bounded local implementation report

**INCOMPLETE — not an O4 acceptance or integration candidate.** This run implemented
and tested the current original-backed assessment-resolution prerequisite. It did
not finish the selected atomic persistent Courtthane/Locksmith application batch.
The remaining items below are engineering gaps, not new policy decisions or an
assertion that the user must provide live authority before offline development.

## Delivered code

`Assignment/AssessmentResolver` resolves a policy through its fixed AuthorityStore's
existing FormationJournal inspect boundary. It accepts no supplied state, outcome,
response or success assertion. It verifies the current original policy and trust,
all three actual group successes, their exact claims, all custody checkpoints,
retained response envelopes, usage settlement, completion dependencies and frozen
inputs. It then reruns the selected AugurAdapter's exact operation/context,
provider mapping, token/tariff and semantic-evidence validation. It returns
transient evidence; it does not create an assessment-view H record or authority.

The narrow AugurAdapter seam extracts its existing response checks into shared
private helpers and exposes a typed internal locked-owner validation method.
The original classification entry retains its exception and terminal-failure
behavior. Missing production cognition evidence still refuses. A valid prior
success never replaces a current verifier. R1 limits and R2 bounded pure parsing
remain active. No service configuration, dependencies or legacy adapter changed.

The new test produces positive prerequisites through the unmodified original
AugurCognitionFixture and real O1/O2/O3 producers with synthetic temporary-root
authority and exact mock HTTP. It checks exact raw response bytes/digests,
candidate/Profile/source identity, separately reconstructed AuthorityStore,
missing verifier, revoked/expired policy and negative corruption of response,
settlement, frozen input and operation. Negative mutations are restored from the
actual producer-created state; no positive claims/outcomes/slots are seeded.
Resolution/refusal adds no HTTP request and no journal write.

## Concrete unfinished seams

1. Closed v4 state/migration and complete structural historical validation for
   applications/views, preserving original bytes and consumed rights.
2. Original-backed Profile-specific predicate verification, permitted whole-pair
   selection/view and exact deterministic `assessed_assignment_set` terms.
3. A policy-effect versus B signed-act admission/current-authority derivation,
   CommandLedger consumption and one-commit pair/generation/receipt publication.
4. Actual FormationCognition settings consumer, persistent resolver and explicit
   authorized whole-pair change/revalidation with unchanged personnel/session/lease
   authority. Application replay/contention/interruption/process proofs are absent.

The inspected act format requires a policy reference for APPLY and verifies the
original policy's currentness. The existing policy validator accepts mode A only;
its single application slot cannot be reused for replacement. No new compatible
change-authority derivation was completed in this run. On the consumer side,
FormationSessionAuthority takes the Courtthane holder from personnel, while
FormationSessionLeaseService takes Locksmith as the lease issuer. A settings
record must not replace either of those authentic authority checks.

StateValidation still rejects nonempty applications/assessment_views and unknown
versions. CommandLedger still refuses the missing assignment path. This refusal
must not be relaxed to make a test pass. No assignment or settings persistence is
claimed. The complete seven-row contract disposition is in
`docs/provider-onboarding/o4-b0-local-proof-matrix.json`.

## Verification

PHP 8.4.14; locked dependencies validated by Composer dry run with no changes.
Selected local tests: **884 tests / 8670 assertions**, all selections
exit 0. These include the new assessment test and original O1/O2/O3/FC/native,
reviewer, mapping, credential, parse-scope and frozen-inventory selections.
Changed PHP lint, non-debug test-kernel smoke, `git diff --check` and the static
specification checker were run; exact exits and full logs are in `evidence/final`.

Fresh complete local command: `vendor/bin/phpunit tests` under explicit PHP
network-function restrictions. **Timed out at the unchanged external 1800-second ceiling (exit 124). This is not a full pass.** The workflow and 30-minute
allowance remain unchanged. No hosted CI was run. Accepted O3 CI is inherited
background, never presented as a fresh O4 run. Selected passes do not cover the
missing application proofs or replace the full hosted gate.

The first development test failed because PHPUnit compared object-key order after
canonical journal serialization. The correction compares the exact record value
and digest while keeping byte-for-byte response assertions. The failed public
log and changed test/source variant are retained separately in
`evidence/development/1`; corrected development evidence is separate as well.

## Source and handoff

Clean entry: `f544b696a0ebdf8c25a71f3a369e5a93738074e9`, tree
`862fbfbf51294ad4a175381ded7a6f7b64dad09c`, including verified O4 preparation
and accepted O3 ancestry. New isolated worktree:
`E:/htdocs/imperium-onboarding-o4-b0`, branch `codex/provider-onboarding-o4-b0`.
Tested source: `4b2f1f897f7fbc57fa16d58fbbb6727e25e73be1`, tree
`8bf189d79458ff8cd0c5bb796b6a51578e5d4593`.
Final identities are external in `identities.json` to avoid a self-reference.
All 1,855 tested project PHP byte streams and hashes are retained and compared
with the final source. Generated PHPDoc-only variants, if any, are captured before
exact restoration under the repository's existing generated-reference convention.

The package includes canonical changed files, exact patch with reconstructed-tree
verification, incremental Git bundle, complete protected-original comparison,
commands/exits/public logs, proof matrix, tested-source snapshot, manifests and an
inner review ZIP with SHA-256. It includes no historical delivery ZIPs, vendor
tree, private credentials/evidence, runtime state or outer ZIP checksum.

No push, merge, real provider request, installed-state action, live enrollment,
appointment, activation or O5 work occurred. All five operational flags remain
false and the actual retry allowlist is empty. Stop for source review; this partial
change cannot close O4-B0, and integration requires completion plus fresh full
hosted CI.
