# PPC0 fixed custody contract and future operator runbook

Status: PARTIAL_BLOCKED. This is a source contract, not an installed custody claim.

`Deployment/Composition` implements the existing Gateway without an alias. The default
DeploymentGateway and services.yaml remain unchanged. Infrastructure constructs one
Composition with an existing AuthorityStore, matching OperatorRootOwnership, admitted
grant original, two absolute directories and fixed evidence implementations. No public
command can supply those inputs. Construction, preview and status do not construct a
FileKeySource, EnvelopeStore, adapter or Runtime. Only advance, resume or settings
resolution constructs the graph. Resume uses the existing evidence-only Runtime path.
One exact key instance is shared by AccessAdapter, AugurAdapter and Runtime. One exact
store and AssignmentEvidence instance serve runtime application and PersistentSettings.
The factory never enrolls, migrates, generates keys or creates storage.

## File custody v1

The provisioner owns an existing absolute directory and every ancestor. Its fixed files are:

| Path | Contract |
| --- | --- |
| custody.lock | Existing regular file, never replaced. Every publisher takes an exclusive lock. Readers take a nonblocking shared lock. |
| generation | Public opaque generation, 1–128 ASCII letters/digits/underscore/hyphen, first character alphanumeric, no newline. Must change for every key rotation; never reuse or roll back. Not a credential hash. |
| keys/GENERATION | Immutable private bytes for that generation; 1–4096 printable ASCII bytes, no whitespace/newline. Never overwrite, modify or reuse a generation. |

Provision a new immutable key file completely, then publish its generation while holding
exclusive custody. Readers hold shared custody through the callback, so cooperative
rotation waits for an in-progress delivery to finish. Runtime's existing generation
checks refuse a rotation between verification/capability checkpoints. No rotation can
retroactively cancel an already-dispatched operation. generation() reads only the public
generation and lock, even if the private file is absent. Missing/busy/malformed custody
refuses without repair. withKey delivers once and suppresses exception content/chains.
PHP does not promise secure memory erasure; this is callback confinement, not an HSM.

This contract trusts the deployment custodian and OS ownership. It does not authenticate
provider entitlement. Link/junction and traversal checks are defensive; PHP path checks
are not race-free openat/O_NOFOLLOW operations. Untrusted writers, hard links, network
filesystem locking, rollback, key-file mutation and hostile ancestor replacement are
unsupported. Real provisioning must enforce immutable generations, exclusive publisher
ownership, private reader-only permissions and a local filesystem with tested flock.
Windows ACLs and Ubuntu ownership/mode policy must be verified separately by their owners.
No ACL or Ubuntu installation was inspected here. No real custody location is selected.

EnvelopeStore uses a separately protected existing absolute directory. It publishes
canonical bounded responses under retention.lock using a temporary file and rename;
duplicates must match exact bytes. Restrict readers/writers because responses may be
confidential. fflush/rename are not fsync of data and directory: power-loss durability
is not guaranteed. Retain unknown effects and maximum exposure; no automatic retry,
refund, reset or deletion is introduced. Neither response storage nor custody is an
authority producer.

## Exact future order (not authorized by this document)

1. Review the tested source and complete gate evidence. Resolve any failed gate before
   publication/integration; separately accept PPC0. Preserve O0–O5 historical pins.
2. Name the deployment owner, local root, instance/citadel/operator identities, clock,
   source identity and custodians. Obtain separate deployment approval. Verify the actual
   Ubuntu host, directory ownership, ACLs, lock/rename/crash behavior and backup policy.
3. Supply competent producers for all five rows in the evidence matrix. Require retained
   originals, authenticated scope, revocation/current generation and explicit conflicts.
   Review each concrete adapter against the frozen port before wiring it. Do not install
   the test verifier, synthetic tokenizer or self-declared approval as a producer.
4. Resolve the existing DEFER_ENROLLMENT decision separately if enrollment is proposed.
   Admit genuine authority through accepted owners only under separate authorization.
   Verify FRESH vacancy; do not reuse an occupied installation or infer cutover permission.
5. Under a separately approved custody proposal, provision the fixed file contract and
   response directory; bind public generation to the admitted credential original. Keep
   secrets outside source, public requests and review evidence. No commands here generate keys.
6. Deployment wiring may then explicitly bind the existing Gateway interface to the
   Composition instance described above. Retain the ordinary DeploymentGateway until
   this wiring has its own approval. Preserve the same store/evidence for settings use.
7. Run `imperium:provider:onboard PUBLIC_REQUEST_FILE --format=json` with the canonical
   request's mode set to preview; inspect `imperium:provider:status SEQUENCE_ID --format=json`.
   Obtain separately bounded commissioning authority before mode advance. Command names
   and schemas are unchanged; this document supplies no advance authorization or request.
8. For interrupted work use `imperium:provider:resume PUBLIC_RESUME_FILE --format=json`
   only for retained-evidence recognition. Unknown remains unknown with maximum exposure.
   Keep the actual retry allowlist empty and require PersistentSettings revalidation at use.
9. Native appointment, activation, mission execution and existing-installation cutover
   remain separate decisions. All five operational flags in this run remain false.
