# Canonical consumer correction - terminal Blackquill audit v1

`CANONICAL_CONSUMER_INTEGRATION_TERMINAL_AUDIT_ACCEPTED_BOUNDED_PRE_EFFECT`

## Audited tree and sequence

Final review began separately from clean merged local main at
`7f1b634a37eb8f9d70058f4b18bcefc34e4ff22e` on 2026-09-03. Batch 3 at
`120925af3812b750e701c605c2c5a594849ff499` was reviewed first; acceptance was
withheld for schema-substitution and cached-admission gaps. Batch 3A corrected
those gaps, passed full PHPUnit (2015 tests / 46137 assertions), and was merged
before this review restarted. This audit adds documents and documentary tests;
it does not change runtime code or production wiring.

The reviewer applied Blackquill in a separately sequenced review in this task.
This is not a claim of a second independent human or agent review. Search located
callers; the full selected bodies and correction patches supplied evidence.
Reading ledgers v1/v2 preserve the required and additional sources; v3 records the
final reviewed implementation snapshot. Digests identify reviewed bytes, not
principal competence, issuance provenance or executable authority.

## Claim and evidence

The accepted claim is that one established downstream pre-effect consumer now
consumes native operation interpretation, and the inventoried competing paths
cannot use the same native root as an untouched legacy binding. It does not claim
a working native provider-send orchestrator, provisioned production identity,
provider admission beyond the local transition, or provider success.

The decisive edge is in
`src/Imperium/Runtime/Clavium/DeterministicJournalBoundCredentialBroker.php`:
`invoke` enters `NativeBindingReader::legacy`, validates its stored journal and
claim, calls `inspectClaim`, and only then could enter old invocation admission,
credential-attempt, credential consumption and wrapped callback. `inspectClaim`
calls `NativeBindingReader::forClaim`. Every bound classification refuses before
those cuts. This is the existing effect-corridor broker's actual invoke method,
not the new transition command returning its own reader result.

`src/Command/AgentMailEmailSendCommand.php` injects that same broker. The existing
`imperium:email:send-agentmail --inspect-claim` route exposes its read-only
interpretation through the real Console Application. Without inspection the
retired command still returns GOVERNED_EMAIL_SEND_EXECUTOR_UNAVAILABLE.
`config/services.yaml` registers NativeState/NativeBindingReader and production
service discovery wires the existing consumer; other native producer services
remain excluded. No second facade or optional native-reader default was added.

## Root, authority and state interpretation

The stored claim supplies instance, source authorization and email.send operation.
The resolver requires exactly one stored descriptor with matching authorization
id/digest and operation, then checks stored issuance/authorization integrity and
producer-derived request, replay fingerprint, claim id, execution id and winner
scope. It retains message replay identity separately from the native transition
root `sha256({instance,binding,operation})`; callers cannot select a different
binding or declare an unmapped email unrelated. Missing/ambiguous joins refuse.

NativeBindingReader verifies original descriptor identity/seal/status and calls
NativeReconstructor. Current proof additionally passes the native reader's
current principal/authority/admission validation. NativeReconstructor validates
stored principal, successor production, acyclic decision/custody/authority,
v3/adoption/source/activation/winner/receipt and registered migration edges.
It does not call an effect consumer or use future callback output as prior proof.
The retained native substrate is not rebuilt or granted broader competence.

| Source condition | Established consumer interpretation and consequence |
| --- | --- |
| Intact original without attempt | BOUND_INACTIVE; no bound provider effect. |
| Complete current native commit | COMMITTED_CURRENT; read-only receipt, no provider or retry permission. |
| Expired/revoked/noncurrent valid history | COMMITTED_NOT_CURRENT; historical receipt is evidence only. |
| Journal, pending publication, partial/orphan retirement | INCOMPLETE or conservative refusal; UNKNOWN_REPLAY_PROHIBITED. |
| Invalid descriptor, source identity, schema or native layout | CORRUPT or explicit refusal; no fallback to inactive. |
| Ambiguous binding or resealed changed request/replay/execution identity | Mapping/refusal error; no candidate selected. |
| Proven different descriptor operation | UNRELATED_OPERATION for inspection; independent legacy preconditions remain. |

The original descriptor remains BOUND_INACTIVE. The historical v3 contract still
says NOT_IMPLEMENTED. Local native v3 records and a current transition receipt
never imply credential availability, provider invocation or retry permission.
Inspection does not establish that a message may execute: provider policy,
credential-family compatibility and a complete live effect route remain outside
this accepted pre-effect boundary.

## Bypass disposition

The final `inventory-v2.md` classifies all C00-C30, D01-D11, A01-A05 and E01-E10.
The exact file/caller map remains in preparation inventory v1.

- D01-D10 enter native exclusion and use guarded descriptor/reference reads before
  their relevant legacy consumption/publication or credential cuts. D06-D09 also
  guard cached results, so corrupt upstream omissions cannot hide a native binding
  in a historical admission/proof. Filename and referenced binding IDs must match.
- A01 eligibility and A02 encoder guard caller arrays under the same exclusion.
  A02 directly validates the stored descriptor when native state exists; changing
  its schema cannot disable the guard. Legacy credential families are not renamed.
- E04 journal start checks before publication or cached return. Direct adapter
  invocation requires the stored journal and exact claim interpretation. It cannot
  bypass the broker with a caller-created journal array.
- E03 generic executor rejects email.send before IronGate/credentials; its request
  has no binding root. Direct AgentMail transport refuses before network creation.
  This deliberate refusal also changes the old deterministic email smoke to expect
  no receipt. Separate http.post.json round-trip tests retain their positive path.
- LEGACY_UNBOUND exists only when the storage has no descriptors and no native or
  isolated migration directory. It has no native root or new permission; existing
  old claim checks remain. Any binding/native state disables this fallback. This
  conservative restriction can refuse previously unbound email in a populated
  storage; it is intentional, not a claim that those messages are unrelated.
- E05 response envelope/checkpoints -> raw result -> receipt admission -> receipt
  reconstruction remains archival. E06 A03 raw evidence -> A04 normalization ->
  A05 decoder -> normalized admission -> D11 reconstruction remains archival.
  D11 returns read_only=true and continuing_authority=false, with no outgoing
  execution caller. The real container positively reconstructs this archive.
- E08 mission/Legate/Delegate gateways use their dedicated cognition claims/brokers
  and deepseek.model.invoke. E09 Sortie dispatch/child runner uses manifest/CAS,
  sortie.deepseek.model.invoke and configured http.get. E10 inbound HTTP calls
  verifier -> InboundLazaretto -> persistOnce; http.post.json uses its own transport.
  These concrete call graphs, not a search absence alone, establish separate meanings.

## Lock, durable and interruption obligations

No new production durable field, store, winner, journal, receipt or mutable binding
projection is added. The existing seven-member native write set remains unchanged.
Applicable legacy paths hold native-provider-transition before old winner and
immutable scopes. NativeState uses that same outer lock before sorted source locks.
Broker/adapter nesting retains a process-local depth entry only while that scope
is held, removed in finally. The graph does not call an effect consumer from a
reader and does not acquire the native lock after a held source lock.

Read-only inspection takes no transition lock and writes nothing; snapshots detect
source changes and its result grants no later execution right. Ordinary guarded
legacy invocations can create an empty existing protocol lock file. Process death
releases OS locks; journal/pending native evidence remains unknown, never repaired
into replay. Original pinned-grant retirement and old email/activation stores stay
distinct. No grant conversion, old winner revival or automatic retry is introduced.

## Executable evidence and limitations

CanonicalConsumerCorrectionBatch3Test boots the real Kernel, imports production
configuration and uses FrameworkBundle Console Application command discovery.
Its changes are disposable storage/cache, clock, credential sentinel and public
visibility; the reader/broker/command/adapter definitions are production ones.
The actual container-created broker refuses a current native claim before its
credential/callback checkpoints. The same container exercises all ten guarded
readers, eligibility, encoder, generic executor, direct transport and archival D11.
Counters, explicit refusal errors and file snapshots establish the pre-effect cut.

A PHP process holds native publication after its journal commits; a second process
attempting legacy admission waits, then refuses after native completion. Another
process exits after the journal cut; inspection stays incomplete and re-entry
UNKNOWN_REPLAY_PROHIBITED, with no transition commit or repair. Inherited native
regressions cover original publication/migration/currentness obligations. No new
durable join adds another commit protocol in this campaign.

Both adversarial corrections were reproduced before fixing. The encoder's schema
substitution and D06 cached-result omission now refuse through the application
container. D06-D09 source checks also require the cached-record guard before the
old consistency check and return. Original legacy positive suites remain passing.

Synthetic Root signatures and historical fixture inputs are test evidence only;
they are not production provenance. All mutating tests use temporary storage.
The proof assumes cooperative single-host PHP/flock and protected immutable source
files. Hostile filesystem replacement, distributed locks, physical power loss,
network filesystems, live credentials and cross-process capabilities remain
DEFERRED_BOUNDARY. There was no live provider, mission, authority, Root act,
transition, retry, Iron Gate or Lazaretto operation during this campaign.

Final Batch 4 documentary regression passed **12 tests / 957 assertions**.
Final full PHPUnit (`php vendor/bin/phpunit tests`) passed **2019 tests / 46348 assertions** on PHP 8.4.14 / PHPUnit 13.3.0.

## Verdict

The original post-terminal objection is addressed at the established consumer's
real pre-effect decision and its competing readers, not by promoting the native
command island. Bounded canonical-consumer integration is accepted for the
reviewed tree. All effect/deployment exclusions above remain binding. No further
implementation batch remains in this campaign; live execution needs a separately
scoped and authorized campaign, not an inferred continuation from this marker.
