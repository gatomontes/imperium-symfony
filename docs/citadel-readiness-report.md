# Citadel operational readiness: R0–R3 terminal record

Disposition: **READINESS_PREPARATION_COMPLETE_WITH_EXPLICIT_BLOCKERS**.
This package completes bounded local preparation. It does not establish an
operational, activated or production-ready Citadel. Independent review and any
later deployment/commissioning remain separate.

## Delivered result

- R0: [Producer/consumer matrix](citadel-readiness-matrix.md), source/installation
  distinction, exact public export requirements, genuine personnel lineage and
  explicit unsupported credential/transport/successor boundaries.
- R1: `imperium:citadel:public-institutions` exports native public witnesses from
  fixed-root DI. `imperium:citadel:prepare inspect|decision|assemble` inspects
  supplied public exports, prepares exact unsigned bytes and joins an externally
  produced public signature without enrollment or runtime effects. No credential,
  HTTP or formation journal dependency is introduced in preparation.
- R2: [Owner runbook](citadel-readiness-runbook.md), public evidence request,
  exact canonical/disclosure examples and fresh synthetic command/DI rehearsal
  through separate interview, drafting, mission approval, child handoff, receiving
  acceptance and non-executing Step 1. Default live transport still refuses.
- R3: [Changed-test map](citadel-readiness-changed-tests.md), committed executable
  candidate, focused/full validation evidence, preservation checks and allowlisted
  ZIP with internal and external SHA-256 manifests. Full-suite result is below.

## Tested identity and commands

Entry: `7a7881b91f10f8c2382b28029ba2846d448370a9`.
Executable/test/tooling commit: `2313b69f8f06af5064df4a0e711834f8cf90d97d`.
Tested tree: `da7dee9c6f467e005e123c33ffbec7f31daedcd2`.
The implementation batch is `7331f40c`; the following tooling-only commit includes
the final preparation test transcript and full-run identity in the allowlist.
Final documentation identity is recorded separately in `REVIEW-IDENTITY.json`
and the external manifest, avoiding a self-referential commit/hash claim.

The ZIP is a curated review packet, not a deployment image. Its allowlist includes
tracked source/test/config/contract/bootstrap/office/runtime/public artifacts,
Markdown/JSON/TSV documentation and named proof/tool files. It excludes `.env`,
dependencies, private runtime stores and arbitrary working files. Reproduce the
full suite from the exact Git commit with locked dependencies; preserve the
installation and local environment boundaries described in the runbook.

Environment: Windows, PHP 8.4.14 ZTS x64, Composer 2.8.12, Python 3.12.10,
cryptography 46.0.7, PHPUnit 13.3.0. Locked Composer install used
`composer install --no-interaction --prefer-dist --no-scripts`; 82 packages,
no dependency updates/removals, unchanged composer.lock. No Imperium installation
or Composer application scripts were run as part of dependency preparation.

| Command | Result |
| --- | --- |
| `php vendor/bin/phpunit tests/Imperium/Runtime/CitadelReadinessPreparationTest.php tests/Imperium/Runtime/CitadelMissionFormationTest.php tests/Imperium/Runtime/CitadelFormationCorrectionTest.php --log-junit var/citadel-readiness-proof/focused.xml` | PASS: 50 tests / 341 assertions; 453.126 seconds in JUnit; no errors/failures/skips. Focused source matches the committed application/test changes. |
| `php vendor/bin/phpunit tests/Imperium/Runtime/CitadelReadinessPreparationTest.php --log-junit var/citadel-readiness-proof/preparation-final.xml` | PASS: final preparation changes, 10 tests / 64 assertions; no errors/failures/skips. |
| `php bin/console lint:container` | PASS; `debug:container App\Imperium\Runtime\Citadel\Formation\BoundedFormationTransport` resolves the private alias to `UnavailableFormationTransport`; normal compiler inlining notice retained. |
| `php bin/console imperium:citadel:prepare inspect docs/citadel-readiness/public-evidence-request.json` | Expected exit 2; every real public prerequisite missing, `live_ready: false`. |
| `php bin/console imperium:citadel:public-institutions` | Expected exit 2 in this checkout; nine CMF121 unavailable witnesses. This source tree is not treated as an installation. |
| `php tools/prove-citadel-readiness.php` | PASS on tested commit; 9 read-only preparation/assembly steps, 4 distinct prepared owner acts, 3 synthetic calls, accepted handoff and non-executing Step 1. |
| `php tools/prove-citadel-formation.php` and `php tools/prove-citadel-correction.php` | PASS on tested commit in new synthetic roots; no live provider, real key, installation or mission effect. |
| `python tools/verify-citadel-correction-proof.py` + independent Python Ed25519/canonical packet check | PASS: 4 CF01 owner signatures, 73 unique CF02 owner/institution signatures, 3 journal frames, 1 intact child receipt, 9 native witnesses, unchanged receipt bytes and 1 parent transition; all 4 new prepared signatures/objects verified. |
| `php vendor/bin/phpunit tests --log-junit var/citadel-readiness-proof/full-suite.xml` | PASS: **2,739 tests / 53,138 assertions**, exit 0; no failures/errors/skips; 4 historical linked-worktree warnings. Wall duration 980.641 seconds. |

The four warnings are unchanged historical `.git/HEAD` reads in
`DeploymentCustodyCrashDemonstration.php:290`,
`OperationalConstructionCrashDemonstration.php:399`,
`TerminalRetirementCrashDemonstration.php:183` and
`UnknownProviderOutcomeCrashDemonstration.php:245`. A narrowly targeted
`--display-warnings` audit passed all four tests / 90 assertions and retained
the exact warning messages in `proof/verification.json`; their source blobs
match entry. This audit identified the warning provenance without repeating
the full suite or changing unrelated historical code.

The full-run JSON records start/end UTC, wall duration, exit status and JUnit
totals against the exact tested commit/tree. The packet refuses a failed or
incomplete full suite and refuses executable changes after that identity.
The fixed-path historical correction verifier was run against a newly created
copy of this run's synthetic correction output in this checkout only; no earlier
packet was overwritten. Its input SHA-256 is recorded in the public proof check.

Symfony regenerates `config/reference.php` annotations while booting test/dev
containers. The generated diff is retained as evidence and restored to the
committed bytes; no reference annotation regeneration is adopted as runtime code.
After testing, only Markdown reports/steps/flow may differ from the tested commit.

## Remaining limitations and next owner action

1. Intended real installation, custody, public formation trust/enrollment and exact
   genuine incumbent/candidate/appointment evidence remain UNVERIFIED. No real
   public exports were supplied. The public signing environment and existing
   Ubuntu signer invocation are also unverified; no private key location or
   deployment command is invented.
2. The actual provider/model/destination and applicable current tariff are
   unselected for this campaign. Existing Delegate/governance credential claims
   and text-returning platform APIs do not implement the formation aggregate claim
   and enforceable usage/limit contract. Default activation remains unavailable.
3. B1 requires concrete evidence of supportable provider bounds, or an explicit
   owner policy amendment distinguishing local deadline/tariff reservation from
   guaranteed remote billing/cancellation ceilings. Timeout is not remote
   cancellation or zero billing; unknown outcomes retain maximum exposure.
4. No deployment successor lineage was supplied, so no speculative successor
   adapter was written. CMF123 remains a precise producer/schema boundary.
5. Synthetic signatures, interviews and acceptance establish control mechanics,
   not real institutional authority, live semantic competence, provider behavior,
   independent historical custody/timestamps, power-loss durability or scale.
   Office investigation commissions and mission execution remain outside this run.

**Next owner action:** the intended installation's custodian supplies the public
root/source/custody declaration and available public evidence using the runbook's
exact request file, leaving missing fields null. Identify the existing separate
signer interface using public metadata only. Resolve B1 before preparing any live
activation. There is no request to repeat CF01/CF02 or approve already accepted
unchanged scope, and no request for private keys, credentials or payroll data.

The preserved candidate/source-review refs and ten accepted source/evidence blobs
are cross-checked in `proof/preservation-check.json`. No push, merge, branch
deletion, real-key handling, real enrollment, commissioning, live call, installation
change or mission execution occurred. Local implementation commits await review.

Imperium via solitaria est.
