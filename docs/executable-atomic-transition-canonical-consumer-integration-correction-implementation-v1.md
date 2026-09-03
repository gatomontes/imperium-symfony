# Canonical consumer correction implementation v1

## Authorization and boundaries

The operator instruction to complete the campaign authorizes Batches 1–4 after
Preparation Batch 0. Preparation-only language remains historical. The inventory
and reading ledger v1 remain the baseline, not assertions about later code.
No live rollout, credentials, provider invocation, authority consumption or retry
is authorized by this implementation record.

## Batch 1 — interpretation boundary

`NativeBindingReader::interpret` reads the original descriptor and native proof.
It distinguishes `BOUND_INACTIVE`, `COMMITTED_CURRENT`, `COMMITTED_NOT_CURRENT`,
`INCOMPLETE`, `CORRUPT` and `UNRELATED_OPERATION`. Every result is read-only,
denies provider effects and retry, and retains `UNKNOWN_REPLAY_PROHIBITED`.
The historical `read` API is retained for the native transition substrate.
Neither interpretation mutates the original `BOUND_INACTIVE` descriptor.

`forClaim` resolves a stored deterministic execution claim to exactly one stored
binding by instance, operation and source authorization id/digest. It retains
the separate transition root, execution id and message replay fingerprint;
neither identity replaces the other. Ambiguous/missing joins refuse. Directory
and claim snapshots detect changes during inspection. Hashes establish byte
consistency, never issuance competence or provenance.

`assertLegacy` requires the supplied descriptor to equal the stored sealed source
and requires independent native reconstruction to report absence. Pending state,
orphan retirement, corrupt or committed state cannot fall back to inactive.
The caller must retain all historical validation and serialize any subsequent
write against the native transition boundary. This is a read check, not a lock
or an execution grant. Batch 2 owns mandatory consumer reachability and races.

Focused PHPUnit: `CanonicalConsumerCorrectionBatch1Test` passed 13 tests / 59
assertions, including inherited native substrate regressions. It proves current,
expiry, pending, corrupt and unrelated interpretation, descriptor preservation,
and byte-for-byte no-write inspection in disposable state. Application proof and
terminal acceptance remain outstanding until Batches 3 and 4.

Full Batch 1 PHPUnit: **1979 tests, 45874 assertions**, passed (PHP 8.4.14 / PHPUnit 13.3.0).

## Batch 2 — established consumer and bypass closure

`DeterministicJournalBoundCredentialBroker::invoke` now holds the existing native
outer lock, validates its stored journal/claim, and calls `inspectClaim` before
admission, credential checkpoint, consumption or callback. The same method is
exposed by the existing `imperium:email:send-agentmail --inspect-claim` command.
The command injects the established broker and Symfony clock. Success means only
read-only COMMITTED_CURRENT inspection; default sending still refuses. NativeState
and NativeBindingReader alone are explicitly registered in services.yaml; the
other native transition services remain excluded from discovery.

Bound claims load the unique stored outbound authorization issuance, check its
sealed authorization against the descriptor-pinned digest, and recompute the
producer's request, replay fingerprint, claim id and execution id. A resealed
claim with a substituted request or identity cannot reuse that native root.
Hashes establish integrity and joins, never independent competence/provenance.

The existing unbound claim protocol remains available only when the storage has
no descriptors and no native or isolated migration directory. LEGACY_UNBOUND has
no native root and grants no new permission: existing old scope, expiry and
one-use checkpoints still govern it. Any descriptor/native state makes this
fallback unavailable. Unmapped email is conservatively refused; it is not called
unrelated merely because its identity is missing. Native execution remains
pre-effect only.

### Disposition of every inventoried reader and corridor

The v1 preparation inventory gives the exact paths, callers and record edges for
these IDs. This table changes their disposition without relabeling old schemas.

| ID | Existing symbol or corridor and enforced treatment | Classification |
| --- | --- | --- |
| D01 | ProviderBindingActivationDecisionService::decide guards claim/descriptor reads before caller consumption, under native outer lock. | EXISTS_CANONICALLY |
| D02 | ProviderBindingActivationIssuanceService::issue guards the initial decision's nested binding before caller consumption. | EXISTS_CANONICALLY |
| D03 | SingleExecutionProviderBindingActivationService::activate guards authority binding before legacy winner logic. | EXISTS_CANONICALLY |
| D04 | SingleOperationProviderBindingActivationIssuanceService::activate guards candidate and stored references before consumption/publication. | EXISTS_CANONICALLY |
| D05 | DurableProviderExecutionAuthorityIssuanceService::issue guards candidate and references before consumption/publication. | EXISTS_CANONICALLY |
| D06 | GovernedProviderExecutionAdmissionService::admit guards initial authority before cached admission lookup. | EXISTS_CANONICALLY |
| D07 | GovernedProviderExecutionCombinedAdmissionService::admit guards initial authority before cached activation-root winner lookup. | EXISTS_CANONICALLY |
| D08 | GovernedStationaryCredentialResolutionService::prove guards initial admission before cached proof or environment access. | EXISTS_CANONICALLY |
| D09 | GovernedStationaryCredentialResolutionV2Service::prove does the same for v2. No credential-family relabeling. | EXISTS_CANONICALLY |
| D10 | ProviderBindingActivationRevocationAuthorityIssuanceService::issue guards candidate/references. Native revocation retains its separate Root-act route. | EXISTS_CANONICALLY |
| D11 | GovernedToolResultReconstructionService::reconstruct remains archival: normalized result to binding/raw/eligibility/tool; read_only=true, continuing_authority=false, no outgoing execution caller. | DEFERRED_BOUNDARY |
| A01 | ProviderBoundCredentialEligibilityService::assess guards supplied sealed binding inside native outer lock before publication. | EXISTS_CANONICALLY |
| A02 | AgentMailProviderRequestEncoder::encode remains a pure transient codec with no production method caller or transport edge. Its output cannot bypass the retired direct transport. | DEFERRED_BOUNDARY |
| A03 | ProviderNeutralRawEvidenceService::preserve stores archival bytes/joins; its product feeds A04, not execution admission. | DEFERRED_BOUNDARY |
| A04 | ProviderBoundEvidenceNormalizationService::normalize calls A05 and stores an archival result feeding normalized admission/D11. | DEFERRED_BOUNDARY |
| A05 | AgentMailProviderEvidenceDecoder::decode is a pure historical byte decoder called by A04; no effect edge. | DEFERRED_BOUNDARY |
| E01 | Native transition command remains substrate and is never counted as downstream proof. | EXISTS_FRAGMENTED |
| E02 | Existing retired AgentMail command calls established broker inspection, which reaches native interpretation; default send refusal preserved. | EXISTS_CANONICALLY |
| E03 | DeterministicBoundaryExecutor refuses email.send before dispatch/credentials. Direct AgentMailEmailTransport refuses a valid request before network construction; its old network body is removed. http.post.json is unchanged. | EXISTS_CANONICALLY |
| E04 | Stored claim mapping is mandatory before effect-start journal publication/replay, broker admission and direct adapter callback. Native state cannot enter the legacy callback path. | EXISTS_CANONICALLY |
| E05 | Deterministic stored response/result/receipt reconstruction retains historical joins, not native execution/retry authority. | DEFERRED_BOUNDARY |
| E06 | A03 to A04/A05 to normalized admission to D11 remains archival, with no outgoing execution edge. | DEFERRED_BOUNDARY |
| E07 | D01–D10 old descriptor activation/admission routes now guard exact native roots before cached returns, consumption or credentials. | EXISTS_CANONICALLY |
| E08 | Mission/Legate/Delegate cognition uses occupancy, dedicated claims/brokers and deepseek.model.invoke, with no email descriptor reader. | DEFERRED_BOUNDARY |
| E09 | Sortie uses manifest claims/journal, sortie.deepseek.model.invoke and http.get registry; no email interpretation. | DEFERRED_BOUNDARY |
| E10 | Profile smoke is a cognition fixture; generic http.post.json is separate; inbound webhook calls verifier/InboundLazaretto/persistOnce, with no outbound edge. | DEFERRED_BOUNDARY |

D01–D10 explicitly construct guarded RecordReferenceValidator instances. Default
record validation remains general/archival. NativeState source loading does not
call the guarded validator, so interpretation is acyclic. Legacy validation is
preserved only when independent native reconstruction is ABSENT. Native pending,
committed, orphan retirement or unknown state cannot fall back to inactive.

### Locks, durable state and interruptions

Order: native-provider-transition, then existing legacy winner scope, then
immutable record scope. NativeState already enters this same outer scope before
its sorted source locks. Reads, early cached returns, authority consumption and
writes stay inside the outer scope. Broker-to-adapter nesting uses private
process-local depth tracking for that same held scope, released in finally.
Inspection takes no lock and writes no records; snapshots detect source changes.
An inspection result cannot authorize execution later.

No durable field, journal, winner, receipt or migration root is added. Original
BOUND_INACTIVE bytes remain unchanged. Existing native publication cuts and
independent reconstruction remain authoritative. A crash in inspection leaves
nothing to replay; interrupted native state prohibits legacy reuse. Legacy records
completed before a native transition remain historical, never native evidence.
Single-host cooperative flock/immutable-source assumptions remain; distributed
locking, hostile filesystem replacement and physical power-loss guarantees are
not newly proved. Historical v3 NOT_IMPLEMENTED and UNKNOWN_REPLAY_PROHIBITED
remain unchanged.

Focused Batch 2 correction regression passed 30 tests / 166 assertions. The first
full run found the old mechanical email smoke expected success through the now
closed generic bypass. Its test now requires explicit root-missing refusal and
no receipt; unrelated http.post.json round-trip tests remain. Application proof
and terminal acceptance still belong to Batches 3 and 4.

Full Batch 2 rerun passed: **1994 tests, 45969 assertions**.

## Batch 3 — application and adversarial proof

`CanonicalConsumerKernel` extends the real App Kernel and imports its production
configuration/discovery. Only disposable storage, a MockClock, a credential
sentinel, and visibility of existing services change. No alternate reader, broker,
command, adapter or service factory is installed. The real FrameworkBundle Console
Application resolves `imperium:email:send-agentmail` through its production tag.
The test invokes both the CLI inspection and the container-created broker's actual
invoke method. This is application proof beyond direct construction.

The application matrix covers untouched inactive, committed-current, expired and
revoked/noncurrent, corrupt descriptor, pending publication, substituted request,
and ambiguous binding joins. Every inspection is checked against a complete file
snapshot to prove no write or repair. The current result is secret-free: no
credential metadata, idempotency key, destination or payload is returned. Actual
invoke refuses before credential/callback checkpoints; the sentinel remains at
zero calls. The same application container tests generic executor and direct
AgentMail transport refusal before effects.

D01–D10 are all instantiated and exercised through the container, including an
alternate historical descriptor filename, nested activation authority, old
admission roots and candidate-based paths. A01 and A02 also refuse the same native
binding. A separate intact binding for http.post.json retains inactive legacy
interpretation and is classified unrelated for email.send. The archival A03/A04/
A05/D11 chain still reconstructs through the real D11 service with no new files,
credentials, external I/O, provider reinvocation or continuing authority.

Adversarial tests found and fixed two concrete defects: a file occupying the native
journals directory was being mistaken for absence, and the single-execution
activation service lacked its NativeBindingReader/NativeState import. Layout
validation now rejects malformed native/migration directory structure in all
reader entrypoints. Nested claim field sets are validated before use. The request
encoder A02 was additionally guarded under the same native lock: it no longer
accepts a native binding even as a transient request. Its Batch 2 deferred posture
is superseded by EXISTS_CANONICALLY for exact native-root exclusion. Historical
codec tests now supply the required reader and retain their byte-shape checks.

A real PHP child process pauses native publication after its journal commits while
holding the transition lock. A second PHP process reaches legacy admission and
waits. After release, native publication completes and the legacy process refuses;
there is one native transition and no legacy admission. A separate child exits
immediately after journal publication; inspection remains INCOMPLETE and re-entry
is UNKNOWN_REPLAY_PROHIBITED with no repair or commit. Workers are restricted to
named disposable temporary roots and start without a shell. No dependency was
installed: the attempted Symfony Process helper was unavailable, so the test uses
proc_open directly. This proves cooperative local process contention, not physical
power-loss durability or distributed consensus.

The claim fixture uses the actual legacy request, decision, issuance, claim and
journal producers. The native fixture uses actual native principal, successor,
authority and atomic publication services over synthetic Root signatures and
historical fixture inputs. Those fixtures are test evidence only, never production
principal or provider-assurance provenance. No live Root act, grant, credential,
provider call, effect or retry was created or authorized.

Focused Batch 3 and encoder regressions passed **26 tests / 188 assertions**.
Terminal acceptance is still withheld until the separate Batch 4 audit starts from
clean merged Batch 3 main.

A final alias regression also proved that intact bytes alone cannot select the
root being guarded. Guarded direct descriptor reads now require filename identity;
nested provider_binding references require the loaded binding_id to equal the
reference id. An intact descriptor for a different root, copied under an alias,
refuses CCI_BINDING_IDENTITY_MISMATCH without a write. General archival validators
remain unchanged. Final focused Batch 3: **21 tests / 165 assertions**, passed.

Final full Batch 3 PHPUnit passed **2015 tests / 46134 assertions**.

## Batch 3A - corrections required by the first terminal review

The first separate review began from clean merged main at
120925af3812b750e701c605c2c5a594849ff499 and withheld acceptance. It found that
A02's schema-dispatched guard could be skipped by substituting the schema on a
caller array. A02 now checks native-state presence and validates the exact stored
descriptor directly, so schema substitution cannot select the guard.

A container regression also reproduced a cached D06 admission being returned when
a corrupt upstream authority omitted its binding. The cached result itself still
named the native binding. D06-D09 now guard each cached admission/proof before
its existing consistency checks and return, while retaining the outer native lock.
The regression confirms refusal and no record mutation; its existing legacy mutex
is primed before the snapshot because opening that mutex may create an empty lock
file. Inspection itself still creates no lock or record. The generic transport
remained closed throughout; this correction does not grant provider execution.

The audit must restart from clean merged Batch 3A main. No principal, successor,
authority, production state, credential, provider call or retry was created live.
BOUND_INACTIVE, historical v3 NOT_IMPLEMENTED and UNKNOWN_REPLAY_PROHIBITED remain.

Batch 3A focused: **27 tests / 194 assertions**; full: **2015 tests / 46137 assertions**, passed.
