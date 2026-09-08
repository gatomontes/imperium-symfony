# Recruiter/Garrison authority interfaces: owner runbook

This implements preparation and refusal with explicit issuer/currentness blockers. No positive authority revision, public trust enrollment, signature assembly, installation update or live commissioning is available through this interface. The accepted Guildhall lookup is complete. The public Python collector remains unchanged.

## Implemented command

`php bin/console imperium:citadel:authority MODE INPUT`

The root is fixed by Symfony `%kernel.project_dir%`; the command has no root, verifier, key, clock or transport override. A clock is injected only into disposable tests. Every successful preparation, projection, structural inspection or refused authority assessment returns native exit **2**. Malformed, changed, unsupported, stale or inaccessible input returns **1**, a fixed public code and false flags. No mode returns an authority approval. Preserve stdout and the native `$LASTEXITCODE`.

| Mode | INPUT | Footprint and result |
| --- | --- | --- |
| `garrison-request` | Explicit public preparation JSON using the template below | Reads only that file; emits deterministic unsigned request. No runtime read/write. |
| `garrison-inspect` | `{ "request": <exact request>, "occupancy": <exact supplied original> }` | Reads only that public file; checks supplied original digest, exact terms and time status; issuer/currentness remain blocked. |
| `garrison-verify` | Same object plus `"decision": null` or a supplied public object | Reads only that public file; always refuses absent authentic revision issuer. Incoming keys/signatures never become trusted. No consumption or revision write. |
| `recruiter-inspect` | Exact `imperium.recruiter-public-projection/v1` public packet | Reads only that public file; checks schema, projection/reference digests, T03/T04 bindings and explicitly unverified provenance/currentness. A forged self-consistent packet remains untrusted. |
| `recruiter-export` | Exact existing instance ID | **Future separately authorized private-state read** under bootstrap lock. Emits historical projection; grants no authority. |
| `recruiter-compare` | `{ "projection": <exact public packet>, "instance_id": <exact ID> }` | **Future separately authorized private-state read**, same lock/footprint. Checks equality with a new local source projection; stale source refuses. Equality is not authenticated currentness. |

All public inputs are bounded to 1 MiB and JSON depth 48; duplicate object keys refuse. Network wrappers/UNC inputs and final-file symlinks refuse. PHP's local-file checks assume custodian-controlled parent directories; they are not the Windows final-handle security boundary of the separate Python collector. Do not feed keys, credentials or private documents to public-file modes. Unknown errors are reduced to fixed codes without chaining private exceptions. The serializer does not emit arbitrary incoming decision contents.

## Public preparation safe within an authorized local review

Start with [the unsigned request template](citadel-authority/owner-garrison-request.template.json). Its nulls deliberately make it invalid until exact public evidence and owner-selected terms are supplied. `occupancy` must be the original native Garrison occupancy object, not an operator-root fixture or changed copy. Keep actual originals outside Git. `prior_revision` is either an exact `{id,digest}` reference or null meaning **unknown**, never verified absence. The revision verifier cannot confirm either without its missing current revision source. `effective_at` and `expires_at` are owner-selected Unix seconds; expiry must exceed effective time. `request_nonce` is an owner-selected 48-lowercase-hex replay identity. Reuse it only for the exact same intended request; preparation does not consume it.

The builder requests exactly Persona-admission disposition and custody registration, using the existing producer's narrow scope strings. It binds the original digest, instance, actor, occupancy generation and prior revision. Other observed powers retain true/false/null without importing newer defaults; null means absent in supplied bytes. Their original scopes remain bound by the occupancy digest. The request's own ID/digest is distinct from the unchanged occupancy. No positive authority-revision record is produced.

From a separately prepared local source checkout, with `owner-garrison-request.json` populated outside Git:

```powershell
php bin/console imperium:citadel:authority garrison-request E:\htdocs\owner-garrison-request.json
$requestExit = $LASTEXITCODE
if ($requestExit -ne 2) { throw 'Preserve the refusal output; do not treat it as authority.' }
```

Retain the returned exact request with the original public occupancy in the `garrison-inspect` input shape, then run:

```powershell
php bin/console imperium:citadel:authority garrison-inspect E:\htdocs\owner-garrison-inspection.json
php bin/console imperium:citadel:authority garrison-verify E:\htdocs\owner-garrison-verification.json
```

These future filenames are examples for owner-created public inputs, not claims those files exist. Save each output as UTF-8 without BOM into a fresh file, preserving native exits. Never overwrite an earlier artifact. Repeating preparation is deterministic for identical inputs; changing predecessor, scope, time or nonce changes the request. Expired requests remain inspectable as expired and never become permission to act. A valid signature from any supplied key still cannot satisfy the missing issuer competence.

## Future private read: separate authorization required

After independent source acceptance and separately authorized installation, the installation custodian must explicitly authorize reading `var/imperium/bootstrap-state.json`. The interface reads that one private file, bounded to 1 MiB, under `StateStore::locked`; this may create `var/imperium` and `bootstrap.lock`. It neither writes the backing state nor creates temporary state/export files. CLI stdout storage is the operator's separate output write. Exceptions cannot expose private state or diagnostic values.

Only then, from that actual installation, use `recruiter-export` followed by the real instance ID. No real ID or command to deploy code is invented here. The output allowlists source instance/manifest identity, observed state generation, T04 revision, T03 predecessor, consumed commission reference, retired predecessor, successor and qualification. Unknown private fields and raw state are excluded. Projection and qualification digests identify their own bytes; `source_original_digest` is null because no whole private source digest is published. The producer field names the exporter format, not an authenticated historical signature.

The lock serializes cooperating MasterMason/V0Activation StateStore writers. StateStore::write does not enforce lock ownership, and the legacy T04 producer is retired. No authoritative native revocation/supersession register exists in this contract. A locked observation/equality comparison is not a guarantee that the actor is current later or that the retained source is authentic. Missing, failed, ambiguous, unsupported or contradictory receipts refuse; no last-success/highest-generation selection repairs them. Future time/revocation closure requires an actual legitimate resolver, not a caller assertion.

## Exact missing authority boundary and recovery

Garrison needs an authentic issuer and enrolled trust path competent to extend this exact native occupant's admission/custody powers; a trusted current occupancy/revision and issuer-revocation source; and one exclusion boundary covering currentness, signature/effect validation, one-time consumption and immutable revision publication. Neither operator-root upgrade planning, formation trust nor planning acceptance establishes that competence. `GarrisonAuthorityRequest::resolveForAdmission` always throws `CAI036_AUTHENTIC_NATIVE_REVISION_RESOLVER_UNAVAILABLE`. There is no activation/mutation command or signing procedure to run now.

No new effect exists to reconcile, revoke or retry in this implementation. Repeated or competing preparation does not create another ACTIVE occupant or change old bytes. A later positive implementation must prove exact completed-effect recovery after expiry/revocation, interruption before/after publication and concurrent revision/currentness/revocation ordering across every affected consumer. Those transition proofs are explicitly inapplicable to this refusing implementation; the current suite proves zero revision/admission effect, retained independent inventory scope and bootstrap snapshot writer exclusion/interruption recovery.

FormationInstitution, FormationPersonnel and FormationPublicationEvidence are unchanged. Remaining institutions, formation-specific competence/delegation, custody/trust, candidates/appointments and B1 stay separate. Default transport refuses and unknown outcomes retain exposure without retry/refund. Citadel receives; Castellan interviews; understanding closes interview authority; separate drafting approval; separate mission approval; legitimate child-Curia constitution/handoff; receiving assessment grants no execution authority. Readiness, activation and execution remain false.
