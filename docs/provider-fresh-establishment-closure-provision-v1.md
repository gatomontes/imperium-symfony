# PPC8 exact native establishment revocation and proof provision

Design recorded before implementation under unchanged approved PPC7 A–F. No acceptance, assignment, succession, repair or live authority is granted.

## Representation and owner

The native Operator may withdraw only its exact `imperium.fresh-institutional-operator-authorization/v1` original, including the complete signed establishment terms. The target identity hashes those terms and the complete Operator envelope; Formation signature nonces and O2 act nonces are not target identities. Both original establishment signatures remain necessary to install. Revocation grants no Formation-owner competence.

Use the closed `IMPERIUM_FRESH_INSTITUTIONS_REVOCATION_V1` domain and `REVOKE_FRESH_FORMATION_AUTHORIZATION` effect, absent from generic O2 admission and generic Formation signatures. A signed payload binds its schema/domain/effect, complete target terms and Operator envelope, exact target reference, root/instance/citadel/Operator/trust identity, expected journal head, source state version, issued_at/expires_at, a 48-hex replay nonce, correlation and reason. Current trust is obtained from the existing actual Operator enrollment owner. No request supplies a trust enrollment.

The single existing Formation fence covers actual target reconstruction, current competence, replay/conflict decision, prospective-history validation and journal publication. No native file is written by revocation. Publication and completion retain Formation → native → bootstrap/target order.

## Explicit adapter and bounds

Old `imperium.fresh-institutional-establishment/v1` remains readable with exactly its old five fields and an empty effective native revocation history. The first authenticated revocation explicitly adapts it to `/v2`, retaining initialization, reservation, completion and preparation originals byte-for-byte and adding only `revocations`. The signed payload names the observed source version. No automatic rewrite, deletion or reset occurs. A missing establishment extension cannot be adapted; unknown versions and unknown keys refuse.

V2 holds at most 32 exact revocation receipts indexed by the new domain's nonce, with the existing 8 MiB complete-extension bound, 4 MiB input bound, depth 32 and 100,000 visited values. Each revocation interval is positive, at most 3,600 seconds, and within the enrolled Operator trust. Text is nonempty and at most 1,024 bytes. Expected heads and epoch bounds use existing validators. Retained history verifies target and revocation signatures, exact identities/digests, finite timestamps and source-version joins before any publication.

## Target currentness and replay

Before reservation, an authentic target must name the actual current head and the genuine founding originals, canonical root and identities. Pending targets must exactly match the retained reservation's native authorization and terms. Completed targets must exactly match consumed originals. The new revocation owner verifies current Operator competence; a completed target is historical, so withdrawing it does not require restoring founding authority or native files.

Reservation and every unfinished completion/recovery check join the exact native target against durable revocations, including the post-native decision. An unrelated O2 nonce cannot revoke this native target. Revocation blocks unfinished installation while retaining all reservation/native custody. Completion winning first keeps historical replay and native tenure semantics; later revocation never repairs files, resets consumption or retroactively removes installation tenure.

Exact revocation replay returns the original receipt without publication, even after grant expiry. Reusing the scoped revocation nonce with different bytes conflicts. A new nonce never erases an old revocation or makes a consumed reservation empty. The four receiving backdated-decision regressions remain intact, and every new mutation validates prospective history before publication.

## Proof and delivery

The campaign matrix governs actual-owner tests, both process orders, separate trust clocks, freshly signed malformed originals, alias/reset attempts and every distinct current authority entry point. Preserve the original 234-file inventory; add start-source snapshots and reconcile actual call paths rather than crediting a field-name search. Final evidence and the report must distinguish source proofs, executed observations, platform errors and any remaining gaps.

Future public inputs are prepared from a disposable root's actual initialized FRESH state, signed by the independently enrolled founding Operator, and submitted to the narrow revocation owner. Never insert O2 acts or seed accepted state. Current consumers continue to require complete native/journal agreement. The runtime remains absent from production wiring. Countdown 3–5, decrement zero; five operational flags false; DEFER_ENROLLMENT; retry allowlist empty.

## Exact additive schema record

The implemented closed envelope has exactly `payload` and `signature`. Signature is base64 Ed25519 over the canonical JSON payload. The payload has exactly:

```text
schema = imperium.fresh-institutional-revocation-authorization/v1
domain = IMPERIUM_FRESH_INSTITUTIONS_REVOCATION_V1
effect = REVOKE_FRESH_FORMATION_AUTHORIZATION
target = {terms, operator}
target_ref = {schema, id, digest}
root_identity, instance_id, citadel_id, operator_id, trust_fingerprint
expected_head = {generation, digest}
adapter_from = imperium.fresh-institutional-establishment/v1 | /v2
issued_at, expires_at, nonce, correlation, reason
```

The abbreviated `/v2` above denotes the full `imperium.fresh-institutional-establishment/v2` string. `target.terms` retains every existing establishment term, including all five founding originals and the entire exact package. `target.operator` retains the existing native Operator payload/signature unchanged. `target_ref.schema` is the original native authorization schema, `id` is `native-establishment-act-` followed by the bare lowercase SHA-256 of canonical `{terms,operator}`, and `digest` is that same bare digest. Identity fields equal the target terms and independently observed owner identities. The trust fingerprint retains the Operator's existing `sha256:` convention; native journal/reference digests retain their existing bare-hex convention.

Each `revocations[nonce]` receipt has exactly `schema`, `id`, `sequence`, `envelope`, `recorded_at`, `record_digest`. Receipt schema is `imperium.fresh-institutional-revocation/v1`; id is `fresh-revocation-` plus the envelope digest. Sequence is contiguous 1–32. The record digest excludes only itself. `recorded_at` lies in the signed half-open validity interval. Canonical map order is not chronology: history sorts by sequence, requires the first adapter source v1, all later sources v2, and strictly increasing expected-head generations. The target head follows initialization and cannot exceed the revocation head. A reservation and revocation cannot both win the same head. A post-reservation revocation must join the exact reserved native target. All five target founding originals also join the retained holder, policy, constitution, command and step completion, without renewing their historical competence.

The input, structural, record-count and complete-extension byte limits all apply; reaching any limit refuses atomically. A large target can exhaust bytes before 32 receipts. No pruning, rollover, alternate registry or privileged reset exists. Native establishment and the new withdrawal require the existing enrolled Operator identity; the withdrawal does not widen the generic O2 effects list or Formation-owner decision contract.

## Future public-input runbook

This is a review runbook, not authorization to run on an installed root. The tested route is an explicitly constructed library service; there is no added production command, dependency wiring or provider integration.

1. Obtain the intended root's actual Formation owner, existing `AuthorityStore`, clock and approved base evidence adapter. Read the initialized FRESH journal through its owner. Preserve exact public initialization, founding, trust and target originals. Public synthetic adapters in these tests are not real external evidence.
2. For an unreserved target, prepare the complete package through `FormationFreshEstablishment::prepare`, obtaining the actual head and founding originals. For a consumed target, retain the exact reservation terms and Operator envelope. Do not edit its nonce, package, identity, purpose or founding references. Installation itself still needs its separate exact Formation signature.
3. Call `prepareRevocation({terms,operator}, issuedAt, expiresAt, nonce, correlation, reason)`. This performs current owner/target checks and returns public bytes only. The nonce is 24 cryptographically random bytes rendered as 48 lowercase hex digits. Pick a finite interval inside existing trust and the 3,600-second limit.
4. Have the independently enrolled founding Operator sign the exact canonical payload through its separately governed signing custody. The review packet carries no private key and this runbook does not authorize access to one. Assemble `{payload,signature}` and call only `revokeAuthorization` on the actual service owner.
5. Retain the returned receipt and public journal before/after identities. On an uncertain outcome, replay the identical signed envelope. A successful historical replay publishes nothing. A stale-head refusal requires a newly observed public preparation and a new exact signature for that head; do not modify a signature or silently retry. This describes protocol handling; the campaign's actual retry allowlist remains empty.
6. Observe unfinished work refusing the exact revoked target. Preserve partial files and reservation originals. After completed installation, observe historical receipt replay separately from current actor resolution; damaged or successor native state still refuses current authority. No replay authorizes repair, reinstallation, replacement or succession.

No step supplies model approval, assignment/application/use evidence, external account access, enrollment, commissioning, deployment, activation or execution authority.
