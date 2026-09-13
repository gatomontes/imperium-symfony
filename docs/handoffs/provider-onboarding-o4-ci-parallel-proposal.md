# O4 complete-suite CI proposal — approval pending

O4 remains incomplete and integration PR #798 remains draft. This proposal changes
the acceptance method; it does not claim that the original serial gate passed.

## Verified evidence

The reviewed V2 source is commit `dc9c815ae3e903c016809a604ff538c86aafbc22`,
tree `c7f9f5b66698f7450ddcb1661fd8a0595da7b598`.
[Diagnostic run 34766335357](https://github.com/gatomontes/imperium-symfony/actions/runs/34766335357)
tested the same runtime and original tests with a diagnostic-only workflow change
(tree `5fa7f32d93724bea9d3dee92aa6ba0588af7b781`). All eight jobs succeeded.
Downloaded artifact hashes match GitHub's recorded SHA256 values.

Independent comparison of the complete PHPUnit enumeration with every JUnit case
proves that all 606 test files and all 3,697 test identities appear exactly once.
Results: 63,719 assertions, four explicit skips, no errors or failures.
Combined test duration was 2,711.423 seconds; the longest partition took 908.185
seconds. These are partition timings, not a successful serial run or a guarantee
of future CI wall time. Parallel execution reduces elapsed time, not total work,
and does not exercise cross-partition shared-process ordering interactions.

## Rejected performance experiments

Quiet local PHP 8.4.14 largest-consumer measurements were 421.119 seconds for V2,
424.627 seconds for a general scoped encoding cache, and 418.698 seconds for a
narrow migration-record cache. Each passed one test / 45 assertions. Neither
candidate demonstrated a meaningful end-to-end improvement. Both were rejected;
no experimental runtime change is retained. These single-run comparisons are
rejection evidence, not statistically established speedups. The packet retains
the source snapshots, logs and source inventories.

## Concrete proposed change

The PHPUnit workflow assigns the full, freshly enumerated suite to eight disjoint
file partitions. Each installs locked dependencies and runs the existing
credential-binding regression. All workers retain a 30-minute timeout.
The aggregate `test` job requires successful worker exits, a common checked-out
Git tree, exact committed Git bytes before and after tests and across
workers, and exactly one JUnit result for every enumerated test identity.
Missing artifacts, duplicate or absent cases, differing inventories, failures,
source changes and incorrect partitions fail the gate. Skips remain explicit.

The coverage algorithm passed against all eight original diagnostic artifacts.
Eleven local guard tests cover synthetic success and injected failures. The guard also rejects source that was changed identically before every worker,
untracked executable files/configuration, changed file types and misleading skip counts.
Those fixtures validate the new metadata checks; the historical diagnostic run did
not collect the new source-hash and exit metadata. The first fresh run correctly stopped before suite execution because Symfony
regenerated PHPDoc in `config/reference.php` during dependency installation.
The corrected guard records that generated file's raw hashes separately and
requires identical executable PHP tokens to the committed file, ignoring only
comments and whitespace for that exact path. Every other tracked file still
requires exact Git bytes. Tokenization never executes the reference file.
Dedicated checks accept changed documentation and reject executable mutations.
A fresh hosted run of this corrected workflow is required.

The proposal adds the independently requested `JournalReviewerFreshReadTest.php`.
It passed locally: one test / ten assertions on PHP 8.4.14. It was not included
in the historical 3,697-test diagnostic total. Existing runtime files, original
tests, fixtures, dependency locks and service configuration remain unchanged
from V2.

## Required operator decision and completion sequence

The prior [NEXT-INSTRUCTIONS.md](https://github.com/gatomontes/imperium-symfony/blob/ef98490bf61f950c19d8a42e23157dabc046f160/review-artifacts/O4-CI-V2-REVIEW/NEXT-INSTRUCTIONS.md)
requires one unchanged local serial suite within 1,800 seconds and explicitly
forbids splitting. The operator resumed O4 work but has not expressly approved
replacing that acceptance requirement. This proposal is therefore published
separately and is not yet the integration branch or an accepted completion.

If approved, replace that serial performance prerequisite with this complete
parallel acceptance gate, update #798 to the reviewed proposal, run fresh hosted
CI on its exact integration source, inspect all aggregate evidence, then merge
and verify the resulting main tree. Any failed gate remains blocking. Approval
does not establish a serial speed improvement or authorize O5 implementation,
real credentials, provider calls, activation or commissioning.

The proposed workflow, its coverage guard and fault tests, and the added journal
regression are committed alongside this document. A fresh run validates this
proposal only; the existing serial acceptance requirement remains outstanding
until the operator decides whether to replace it.
