# Citadel owner commissioning preparation runbook

Status: SOURCE_INTEGRATION_ACCEPTED_COMMISSIONING_BLOCKED. Stop before live
commissioning. [Matrix and B1 decision](citadel-readiness-matrix.md) control the
unresolved authority, credential and transport boundaries. This runbook neither
repeats CF01/CF02 owner ceremonies nor asks for approval of unchanged prior work.

## Interview completion boundary

The [IR01 correction](citadel-ir01-correction-report.md) is independently accepted
within the [recorded local scope](citadel-readiness-integration-acceptance.md).
UNDERSTOOD closes further interview use and replacement grants for that completed
intake state. Recover the original admitted attempt idempotently; do not use
controls or fresh grants to bypass completion. A supported signed reply explicitly
resumes discussion but leaves completed grants fenced; obtain fresh interview
authority afterward. Replies within an unfinished QUESTION exchange retain the
existing session behavior. Drafting still requires its separate exact approval.

## First concrete owner action

The owner identified `E:\htdocs\imperium`; its last supplied source identity and
public institutional observations are recorded in the
[integration acceptance](citadel-readiness-integration-acceptance.md). The source
merge does not update this installation. Do not repeat the empty five-null template.

Run the [native lineage campaign](next-campaign-citadel-native-institutional-lineage.md)
in its fresh worktree. Its first action is the
[bounded public collection](citadel-native-lineage-public-evidence.md): actual
Garrison/Guildhall upstream lineage, exact separate acceptance when supplied,
other public witnesses and explicit custody/trust/appointment gaps. New public
export commands will be documented only after implementation and offline proof.
The current installed source has no complete formation-compatible export.

## Historical initial request — preserved reference, not a repeat task

The installation custodian identifies the **intended existing installation root**,
its source commit/tree, public custody references, and the separate Ubuntu signing
environment's existing public signer interface. Supply public information only.
Use the field definitions in `docs/citadel-readiness-matrix.md` to populate a copy
of `docs/citadel-readiness/public-evidence-request.json`; leave unavailable fields
null. No installation path or key location has been selected for this campaign.
This request is not permission to install the reviewed source or enroll trust.

In the known local preparation checkout, the exact harmless starting commands are:

```powershell
Set-Location E:\htdocs\imperium-citadel-readiness
if (Test-Path var/owner-public-evidence.json) { throw 'Choose a new evidence filename; preserve the existing export.' }
Copy-Item -LiteralPath docs/citadel-readiness/public-evidence-request.json -Destination var/owner-public-evidence.json
php bin/console imperium:citadel:prepare inspect var/owner-public-evidence.json
```

Use a new output filename if one already exists; retain earlier exports. Expected
exit 2 with `live_ready: false`, missing evidence and explicit blockers. Exit 1
means malformed input; exit 0 is never a readiness approval. Return the public
file and its SHA-256 with the root/custodian declaration. Do not send private
keys, passwords, credential values, journals or private payroll material.

## Separated future owner actions

These actions are sequential boundaries, not an instruction to execute them now.
Commands with public filenames below run in the **owner-confirmed root after
separately approved deployment of reviewed code**. They have not been run against
a real installation. Exact deployment copy/service commands cannot be supplied
until that root and deployment method are identified; do not infer them from a
worktree name. Ordinary commands accept no root, clock, transport or verifier.

| Actor / environment | Prerequisites and public input | Exact command / interface | Observable result and retained evidence | Stop / recovery |
| --- | --- | --- | --- | --- |
| Custodian / PowerShell installation | Identified existing root; reviewed code separately deployed | `git rev-parse HEAD`; `git rev-parse 'HEAD^{tree}'`; `php bin/console imperium:citadel:public-institutions` | Public nine-role witness JSON; record command exit and owner custody declaration | Exit 2 reports missing/invalid/unsupported lineage. Stop; do not bootstrap, substitute test actors or reopen founding installation. |
| Custodian / public export | Existing trust enrollment, if any | Public-only projection schema in matrix; no journal-reading CLI is introduced | Confirm fingerprint independently and retain enrollment receipt/provenance | Missing enrollment is a later administrative decision. `FormationSignatures::enrollPublicTrust($trust, $confirmedFingerprint)` is the actual API, not an ordinary owner command; its integration/deployment authorization is unresolved. |
| Competent institutional actors + Imperator | Genuine native incumbents, real role keys in existing custody, bounded delegation | `php bin/console imperium:citadel:formation personnel-source.json` (`personnel-authority-source`, `{role}`); then signed `delegate-personnel-evidence` / `record-personnel-evidence` requests | Exact actor; owner delegation and independently signed institutional envelopes, immutable returned references | A missing genuine judgment stops that candidate. Do not copy synthetic proof identities/findings or owner-write eligibility flags. |
| Imperator / Ubuntu signer and installation import | Exact examined Profile, scope/Seat, public source closure | Prepare `APPROVE_FORMATION_PROFILE`; sign externally; later retain approval in candidate `profile_approval` | Profile approval remains distinct from Senate judgment and Conscription qualification | Changed source/expiry requires a new exact preparation; no generic Profile activation implied. |
| Imperator / signer, then runtime | Exact qualified Castellan and Locksmith candidates and next generation | Prepare `APPOINT_CASTELLAN` / `APPOINT_FORMATION_LOCKSMITH`; import `appoint-castellan` / `appoint-locksmith` via formation command | Attributable separate Seat bindings | Wrong generation/actor/scope refuses. Preserve existing occupancy; no automatic successor selection. |
| Request submitter / installation | Real bounded objective, later authorized installation use | `php bin/console imperium:citadel:intake owner-submission-0001 request.txt` | Durable exact request and returned `intake_id` | Retain original bytes and returned identity. Reuse same submission identity only for the same bytes. No spending authority from intake. |
| Imperator / preparation then signer | Qualified current holder; real returned intake ID; chosen B1-compliant transport terms | Formation `authorization-source` with `{intakeId, phase: "interview"}`; prepare `AUTHORIZE_INTERVIEW_SESSION` over exact terms | Exact opening exchange/holder source, evolving transcript disclosure, provider/model/destination, per-call and aggregate ceilings, expiry | Stop for missing pricing or unsupported limits. Understanding is not drafting permission. |
| Deployment authority / installation | Independent review, actual trust/institutions/custody and B1 resolved; separately authorized commissioning | **BLOCKED: no supported live activation command exists.** Default is `UnavailableFormationTransport` | Keep `CMF034`; no network/credential activity | No env toggle, container override, caller-selected production transport or direct provider invocation is a substitute. |
| Runtime under granted session | Exact authenticated grant and separately commissioned supported transport | Formation `grant` with `{intakeId, phase, terms, decision}`, then `call` with `{sessionId, attemptId}` | Bounded interview question/understanding, claim and exposure, sealed response | Do not retry unknown outcome. `recover-response` with original session/attempt admits retained response only. Refusal and admitted understanding close interview use; only an unfinished, never-refused defer/resume preserves usable authority and accounting. |
| Imperator / separate later drafting | Current attributable understanding; proportionate present-material Charter | `drafting-request` with `{intakeId, charter}`; `authorization-source` phase `drafting`; prepare/sign `AUTHORIZE_EXACT_DRAFTING`; grant/call | Distinct exact approval and Planning Authorization before any proposal | New investigation needs its competent commission. Never treat “understood” as approval. |
| Imperator / mission review | Exact numbered dossier and genuine child candidates | `present-mission` with `{intakeId, version, appointments, expiresAt}`; prepare/sign `APPROVE_MISSION_AND_CONSTITUTION`; `review-mission` with exact terms, `APPROVE`, all line digests using the exact `lineDigests` argument, rationale and decision | Separate mission review with typed mission/constitution/appointments effects | A changed dossier/intent cannot reuse approval. Retain objections and earlier versions. |
| Formation / child receiver | Exact approved mission, reserved identity | `reserve-mission` with `{reviewId}`; `deliver-handoff` with `{intakeId}`; separate `AUTHORIZE_RECEIVING_ASSESSMENT` grant/call | One legitimate child, original exchange/authority closure, receiving ACCEPTED or concrete GAP | Uncertain effect retains fence. Retry delivery with original intake; recognize only exact retained receipt provenance. No invented acceptance. |
| Receiving owner / validation | Attributable acceptance and current source | `validate-step-one` with `{intakeId}` | Non-executing schema/reference validation | GAP stops validation. No mission execution command belongs to this package. |

All formation operation files have exactly `{"operation": "...", "arguments": {...}}`.
Use the named arguments above and returned identities, never illustrative IDs.
Run them with `php bin/console imperium:citadel:formation operation.json`.
Runtime consumers recheck signatures, time, revocation and source currentness;
successful preparation is not a prediction of successful import.

## Exact canonical packet preparation and public signature assembly

Create a public `decision-request.json` with these exact fields:

```json
{
  "schema": "imperium.citadel-preparation-request/v1",
  "citadel_id": "<actual returned Citadel identity>",
  "trust_fingerprint": "<independently confirmed SHA-256 of raw public key>",
  "effect": "AUTHORIZE_INTERVIEW_SESSION",
  "object": "<replace with the exact terms OBJECT, not a string>",
  "expires_at": "<replace with owner-selected future Unix integer>",
  "source_identity": {
    "commit": "<actual 40-hex commit>",
    "tree": "<actual 40-hex tree>",
    "public_export_digest": "<canonical public export SHA-256 from inspection>"
  }
}
```

This schema illustration is deliberately invalid until real values are supplied.
No fake identity, tariff, budget or expiry is a production default. Executable
serializer examples with **synthetic-only** exact values live in
`proof/readiness-demo.json`, including four separate signed effects and exact
outgoing synthetic requests. Source identity is inspectable review metadata;
the accepted signed payload binds the effect and canonical object digest, not
new arbitrary metadata fields. Confirm source identity outside the signature.

```powershell
if (Test-Path signing-packet.json) { throw 'Choose a new packet filename; preserve prior bytes.' }
php bin/console imperium:citadel:prepare decision decision-request.json > signing-packet.json
Get-FileHash signing-packet.json -Algorithm SHA256
```

For JSON redirection, use a UTF-8-capable PowerShell environment or explicitly write UTF-8 without BOM; PHP readers call `json_decode` directly.

Keep the complete packet. It carries the canonical object, destination/model,
disclosure, payload, exact base64 signing bytes and SHA-256. Review the object,
not just its digest. Session object fields are exactly `source`, `provider`,
`model`, `destination`, `pricing`, `per_call`, `total`, `visible_intakes`,
`disclosure`, `expires_at`. Limits use integer fields in this order: `calls`,
`input_tokens`, `output_tokens`, `cost_microusd`, `milliseconds`; per-call calls
is 1. Source is the exact `authorization-source` result, not a hand-written
holder identity. Signed disclosure covers the evolving exchange, holder cognitive
Profile, listed registry context, and phase-specific prior drafts/reviews or
receiving packet; transport inspection must still bind each exact outgoing body.

In the separately identified Ubuntu signing environment, with the public packet
placed in its approved working directory, the following **public-only** extraction
uses PHP's inspected canonicalizer output verbatim; it does not read a key:

```bash
php -r '$p=json_decode(file_get_contents("signing-packet.json"),true,512,JSON_THROW_ON_ERROR); $b=base64_decode($p["signing_bytes_base64"],true); if($b===false || hash("sha256",$b)!==$p["signing_bytes_sha256"]){exit(1);} $h=fopen("payload.bin","xb"); if($h===false){exit(1);} fwrite($h,$b); fclose($h);'
sha256sum payload.bin
```

Compare with the packet digest; review payload and object. The existing trusted
signer must sign the **raw payload.bin bytes with Ed25519**, yielding a 64-byte
signature encoded base64. The runtime uses
`sodium_crypto_sign_verify_detached(signature, CanonicalJson::encode(payload), publicKey)`.
Do not sign the hex digest, pretty JSON, object instead of payload, or a newline.
Exact private signer invocation is **UNVERIFIED** because the owner has not
identified its existing interface. No new key tool, key format conversion or key
location is prescribed. Private key material stays entirely in that custody.

Return only the base64 signature and public key. Create `signed-public.json` with
exact fields `{packet: <complete packet object>, public_key: <base64 raw public
key>, signature: <base64 detached signature>}` and execute:

```powershell
if (Test-Path assembled-decision.json) { throw 'Choose a new result filename; preserve prior evidence.' }
php bin/console imperium:citadel:prepare assemble signed-public.json > assembled-decision.json
```

This checks exact payload/object bytes, fingerprint, signature and expiry without
enrolling trust. Its `decision` can be placed into the appropriate operation's
`arguments`; its `object` preserves the original field order for runtime terms.
For a grant use `{intakeId, phase, terms: assembled.object, decision:
assembled.decision}`. Mission approval instead uses `object.terms`,
`object.line_digests`, `object.rationale` plus `disposition: APPROVE` and the
assembled decision. Profile approval is embedded in the qualified candidate.
Do not copy assembly's additional diagnostic fields into a runtime envelope.

Expired or altered packets refuse; keep evidence and prepare new bytes only for
the exact needed decision. Signature byte validity does not establish enrollment,
institutional competence, currentness, revocation status or permission to activate.

## Rehearsal and limits

Run focused tests and `php tools/prove-citadel-readiness.php` in the local checkout;
the script accepts no root/clock/key/transport arguments. It creates fresh roots,
ephemeral synthetic keys and actual command/DI services, signs prepared bytes,
then rehearses interview → distinct drafting → separate mission approval →
legitimate synthetic child → receiving acceptance → non-executing Step 1.
`prove-citadel-formation.php` and `prove-citadel-correction.php` retain the prior
full mechanics and correction regressions. These runs are not owner ceremonies.
Scripted understanding proves neither live Castellan competence nor remote
provider behavior. No test identity may be used to satisfy a real prerequisite.
