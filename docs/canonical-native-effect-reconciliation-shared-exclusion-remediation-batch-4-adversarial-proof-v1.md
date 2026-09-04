# Canonical Native Effect Reconciliation Shared-Exclusion Remediation — Batch 4 adversarial proof v1

`BATCH_4_COMPLETE_RECONCILIATION_ADVERSARIAL_SHARED_EXCLUSION_PROVED`

Three separate-process checkpoint races use isolated runtime fixtures and the
real `NativePrincipal::lifecycle(REVOKE)` writer. In every trace, the use worker
holds the native shared exclusion after currentness passes, the mutation worker
announces its attempt, and no revocation marker can appear until the governed
publication completes and releases the lock:

- DP01: `DP01_CURRENTNESS_HELD` -> `MUTATION_ATTEMPTING` -> decision and
  issuance authority published -> `MUTATION_COMMITTED`.
- IU01: `IU01_CURRENTNESS_HELD` -> `MUTATION_ATTEMPTING` -> issuance authority
  consumed and reconciliation authority/evidence published -> mutation commit.
- CU01: `CU01_CURRENTNESS_HELD` -> `MUTATION_ATTEMPTING` -> capability consumed
  and claim published -> mutation commit.

Interruption after issuance-authority consumption leaves no target authority;
a fresh process-local capability finishes only the same deterministic
publication. A changed validity window conflicts with an already established
target. Existing suites retain interruption coverage at authority, issuance,
claim, consumption and receipt cuts, fresh-process custody, frozen-perimeter
checks and source lifecycle refusals.

The lock identity calculation is asserted against the running Windows/Linux
family rule. This proves cooperative single-host behavior only. Distributed
filesystems, multiple hosts, hostile writers, Root-history repair, providers,
credentials and external I/O remain excluded.
