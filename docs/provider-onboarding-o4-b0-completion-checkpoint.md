# O4-B0 continuation checkpoint — INCOMPLETE

This is a partial engineering checkpoint, not the requested completed O4 delivery.
O4 remains HOLD. No external dependency is claimed to explain the missing code.

The review archive manifest was verified before use. The original source worktree
was clean at 4711ea5def32148335069f396fc86ab6abc82df4, tree
68ec1d4f305d8a66a280680c0cdf358d33b54934. Work is isolated in
E:/htdocs/imperium-onboarding-o4-b0-completion on branch
codex/provider-onboarding-o4-b0-completion. Earlier reports remain unchanged.

## Implemented

AssignmentMigration explicitly advances a quiescent v3 authority state to v4.
It retains the exact bounded v3 predecessor and its digest, verifies preserved
original maps and consumption, and refuses unsupported or malformed state.
Earlier migration owners recognize v4 without downgrading it. Inspection does
not migrate. The existing v3 producers and original-backed assessment validation
continue to recognize the explicit new version.

Assessment verification was moved into a private trait for sharing by journal
owners without exposing a public supplied-state entry or reacquiring a lock.
The public resolver retains its prior behavior. No application owner uses this
trait yet; this is groundwork, not an implemented application boundary.

The new migration test uses actual synthetic O1/O2/O3 producers, checks retained
history and corruption refusal, and includes a separate PHP-process journal read.
That process test proves migration-state persistence only, not settings persistence.

## Missing completion

Nonempty applications and assessment_views still refuse. The atomic application
writer, complete application/view history validation, exact assessed-set derivation,
Profile-specific predicate verification, mode B compatibility, persistent settings
consumer and fresh-authority whole-pair replacement are not implemented.
The seven-row application proof matrix is not complete. No full local or hosted
O4 suite pass is claimed. This checkpoint must not be integrated as O4 completion.

## Verification scope

PHP 8.4.14. Existing dependency locks were checked with Composer dry run, which
reported nothing to install, update or remove. Changed PHP lint, non-debug test
kernel smoke, existing Augur migration regression and 86 static specification
checks passed. Targeted test results and exact final-source identities are in the
checkpoint artifact logs and identities. Selected checks do not replace the
unchanged full vendor/bin/phpunit tests gate or fresh hosted CI.

No real provider requests, credentials, installed-state operations, live
commissioning, operational activation, subagents, push, merge or O5 work occurred.
All five operational flags and the actual retry allowlist remain unchanged.
