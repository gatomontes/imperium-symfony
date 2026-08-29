# Provider Binding Activation Integrity Remediation — credential-reference boundary hardening

## Result

`BATCH_4_CREDENTIAL_REFERENCE_BOUNDARY_HARDENED_NO_CUSTODY_CHANGE`

`CredentialCapability` no longer retains or publicly exposes the clear credential reference. It
derives the reference digest during construction and generic metadata contains only that digest.
Eligibility, execution-claim, feasibility and journal-bound admission readers now compare the
digest without receiving the clear reference.

The issuing `EnvironmentCredentialBroker` is the only post-construction clear-reference custodian.
It keeps the reference in a private, process-local live map paired with the exact issued object and
uses it only inside its existing consumption boundary. The map is not serializable capability
metadata, durable custody or cross-process possession. The terminal custody refusal is unchanged.

## Exclusion proof

Ordinary capability metadata, JSON/log representations, validator exceptions, execution claims,
feasibility records, eligibility records and admissions exclude the clear reference and credential
secret. They may retain the existing SHA-256 reference digest. Exception messages identify only
failure classes and do not interpolate the reference or secret.

PHP cannot guarantee memory zeroization for immutable strings. This batch therefore claims
reader minimization and durable/log/exception exclusion, not zeroization, dump immunity or
cross-process custody.

## Preserved perimeter

No principal provenance is produced. No artifact disposition changes. No process-loss evidence is
run. No capability is issued, reconstructed, transferred, delivered, consumed or resolved by this
batch. No credential platform is selected, no command is migrated, no provider is invoked, no
external I/O occurs, and Iron Gate and Lazaretto remain closed. Provider Execution Assurance
remains paused.
