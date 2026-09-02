# Atomic Transition Reproof Batch 4: counterfeit and interruption proof

`REPROOF_BATCH_4_COUNTERFEIT_INTERRUPTION_PROOF_COMPLETE`

The 32-case counterfeit matrix covers missing/reordered/duplicate/extra cases,
wrong observations and expectations, placeholder roots, cross-root substitution,
broken journal joins, opaque auxiliary payloads, effect flags, mutation
replacement, partial/complete confusion, replay/contention substitution,
unknown fields, encoded synthetic forbidden values, producer conclusions,
graph omission/edges, exclusion assertions, candidate roots/private fields/
success labels, authorization/proof/runtime substitution, verifier pins,
producer identity injection, and source byte/file substitution.

The tests reseal changed inputs, observations, ordered roots, origin, receipt,
candidate and exclusion section commitments. Semantic counterfeits must reach
independent case evaluation, rather than merely fail an outer hash. A separate
source attack recomputes the blob and manifest and deliberately trusts that
manifest: it still fails commit/tree/blob membership.

Five malformed-shape tests capture warnings without retaining their messages.
They exposed unchecked tree iteration and missing complete-case access. Both
are fixed with type/shape validation before traversal. Preflight now refuses
non-array case sets without attempting count(). All public failure output
remains stable reason codes, never offending private values.

Seven disposable publication cuts cover reservation, receipt-only, missing
finalization, truncated receipt/candidate/finalization and stale finalization.
Each refuses reading and re-reservation; no automatic execution retry occurs.
The reader rejects links and oversized files. These tests model incomplete
package states; they do not claim power-loss durability or concurrent runtime
lock proof. The existing semantic classifier tests cover all eight cases.

The CLI now requires `php -n`, refuses extra loaded files/autoloaders, and the
source collector disables Git's filesystem-monitor hook. This keeps startup
and read-only Git capture inside the reviewed local boundary. The explicit
loader was exercised without invoking a mission. Operator execution still
requires the separately approved source and command.

This checkout has core.autocrlf=true. A disposable detached source worktree
checked out with core.autocrlf=false is required for raw Git blob/loaded-byte
identity. Existing runtime source is not normalized or modified. The source
capture must pass before Batch 5 and no mission may run from a dirty checkout.

Validation after Batch 4: PHP 8.4.14, PHPUnit 13.3.0, **72 tests, 706 assertions**
across the four new batch files, the two preparation/selection files and the
existing atomic-transition classifier Batch 4 file. New batches alone pass
56 tests, 431 assertions. `git diff --check` and the explicit no-INI loader check
pass. No real mission, existing private receipt intake, signing, provider or
runtime-state mutation occurred.

Next: exact-source Batch 5 approval, separately authorized Batch 6 verification/
signing, Batch 7 admission, and separate Batch 8 audit from merged Batch 7 main.
V1 is unchanged. `CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`
remains controlling. No accepted v2 evidence or closure exists yet.
