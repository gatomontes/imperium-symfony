# Native authority protocol: exact future owner procedures

Current disposition: [P0–P3 and NA-IR01/02 accepted and merged](citadel-native-authority-protocol-acceptance.md). [CP0–CP3 commissioning preparation](next-campaign-citadel-native-authority-commissioning.md) is selected. Real policy/deployment/enrollment are separate later gates. Earlier pending-review or next-action statements below retain their historical attribution.

All real commands below are future owner-only operations after source acceptance and separately authorized installation. This run exercises them only through actual command/DI in generated synthetic roots. There is no deployment command or real signer selected here. Start with [the contract](../contracts/native-institutional-authority-v1.md), [owner policy proposal](native-authority-owner-policy.template.json) and source map. Real identities/times/public key remain unknown null; the template is intentionally invalid and unapproved.

The root is fixed by `%kernel.project_dir%`. No command accepts a root, keypair, verifier, clock, credential or provider override. Public input files use A0's 1 MiB/depth 48/duplicate-key/local-path restrictions and custodian-controlled parents. Neither command reads private signing material. Canonical signing takes place in the owner's separately approved signer; no nonexistent signing-tool syntax is supplied.

1. The deployment owner approves the exact native domain, role, six effects, validity and explicit legacy-workflow fence. Independently confirm the SHA-256 public-key fingerprint. Populate the exact enrollment template with public values outside Git.
2. Run `php bin/console imperium:native-authority:enroll E:\owner\native-policy.json CONFIRMED_PUBLIC_KEY_SHA256`. Exit 0 records public trust and immediately fences unsupported legacy paths; roster remains absent. Repeated enrollment refuses. This operation needs administrative access control and explicit owner authorization beyond access to the ordinary incoming-decision command.
3. All ordinary operations use `php bin/console imperium:native-authority E:\owner\native-operation.json`. Input is exactly `{ "operation": "...", "arguments": {...} }`. No `enroll` operation exists there.
4. Use `snapshot` with empty arguments to obtain the exact head and recorded roster revisions. Prepare an explicitly chosen roster adoption object from supplied public historical evidence. Obtaining a new private Recruiter projection remains a separate authorized operation under the A0 runbook; do not infer permission to run it.
5. Use `prepare` with `{effect, object, issued_at, expires_at, nonce}`. It returns the exact unsigned payload and null signature, exit 2; it does not issue an act. Keep the original public object separately; output does not echo arbitrary supplied evidence. Sign canonical payload bytes through the separately approved signer.
6. Use `assemble` with `{object, payload, signature}`. Only an exact currently valid enrolled signature is accepted, exit 2. It returns `{object_digest, decision}`; this verifies assembly without consuming authority. Compare the returned object digest to the retained original.
7. Use `apply` with `{object, decision}`. Exit 0 means one native institutional act committed or the exact completed effect was recognized. It does not mean mission authority or live readiness. Retain every returned digest, nonce and registry revision. Wrong scope, stale head/roster/revision, expiry, revocation or conflict returns exit 1 and fixed code.

## Exact operation shapes

| Operation/effect | Arguments/object |
| --- | --- |
| `snapshot` | `{}`; historical observation, exit 2 |
| `resolve` | `{seat}`; current locked observation, exit 0 or refusal 1 |
| `ADOPT_ROSTER` | `{expected_head, seat, actor, occupancy_generation, prior_roster:null, evidence, effective_at, expires_at}`; Recruiter exact A0 public projection or sole retained native Garrison original |
| `SUPERSEDE_RECRUITER` | Same keys; exact prior digest, new actor/generation+1 and `{source_id,source_digest}` prospective case evidence; no inferred qualification |
| `RETIRE_ROSTER` | Same keys; exact current actor/generation/prior digest, `evidence:null` |
| `REVISE_GARRISON` | `{expected_head, roster_digest, prior_revision, request, occupancy}`; request is exact A0 two-scope unsigned request with current predecessor and fresh replay nonce |
| `REVOKE_DECISION` | `{expected_head,target}` where target is exact 48-hex decision nonce |
| `REVOKE_ISSUER` | `{expected_head,target}` where target is enrolled public fingerprint; one-way for this store version |
| `admit` | `{delivery_id,binding_id}`; separately invoked Garrison institutional act on a complete retained canonical delivery; exit 0, co-published native custody/disposition |
| `inventory` | `{inquiry_id}`; complete retained public inquiry, current original inventory power and roster; native response returned, exit 0 |

The Garrison request builder is still `php bin/console imperium:citadel:authority garrison-request PUBLIC_PREPARATION.json`. Its prior revision null means unknown at preparation; the new signed transition verifies absence only against its own matching enrolled head. A later request supplies `{id,digest}` for the current native revision. Copying a request into the occupancy directory never installs authority.

Preserve native stdout and `$LASTEXITCODE` immediately. Write UTF-8 output into fresh external filenames; never overwrite an original artifact. The example `E:\owner` filenames are illustrative owner-created public inputs, not files supplied or commands run in this campaign.

## Footprint and recovery

NA-IR01/02 correction: admission checks the same combined native/legacy custody set as inventory while holding the shared lock. `NAT037_CANDIDATE_ALREADY_ADMITTED` refuses a fresh effect for a held Persona, including its old delivery or a different delivery identity; keep the original custody/disposition bytes. `NAT040_CUSTODY_INVALID` refuses unsupported custody schema/state/instance/Persona identity or duplicate retained identities. Existing parsing and record-integrity failures retain their original refusal codes. There is no automatic legacy migration or legacy-to-native recovery. A different complete Persona can coexist with retained legacy custody.

Fresh `apply` and `admit` sample one acceptance instant under the lock immediately before fresh authority checks, after exact retained replay is excluded. Every authority interval check and retained acceptance timestamp for that operation uses that sample. This is the authorization acceptance time, not the later rename/publication time; expiry during processing does not create a contradictory retained acceptance timestamp. Authority already expired at that instant refuses before an effect. Exact completed recovery still checks original signatures, intervals and revocation ordering; it never renews authority. The contract defines the same single observation instant for current resolution/inventory and retains all filesystem limitations.

Every protocol operation may create the native transition-lock directory/file. Enrollment and successful new acts append immutable frames under `var/imperium/native-authority`; admission custody/disposition live together there. Original occupancy and private bootstrap state stay unchanged. Public assembly/preparation/snapshot acquire the lock but append no frame. Native inventory returns a distinct versioned response without publishing a legacy Guildhall response file. Unsupported old consumers refuse rather than using stale raw state.

Replay `apply` with the identical signed object/decision to recognize an already completed act, even after expiry/revocation. Replay `admit` with the same delivery/binding only to recover a retained completed admission; it does not authorize a new candidate. Different bytes under a used decision or request nonce refuse. A losing competing head must be reviewed and prepared afresh; never silently rebase a signed act. After retirement/revocation, new admissions refuse while existing effects remain history.

`NAT005_UNKNOWN_OUTCOME_FENCED` means a pending publication must be preserved for a separately reviewed recovery procedure. There is no automatic deletion, forced completion, expiry release, retry refund or key rotation command. Source rollback, lost history and mixed old/new binaries are not supported recovery. The precise cooperating-writer and durability limits are in the contract.

Next after local completion: independent review of this entire packet, then separately authorized policy approval/deployment/public trust enrollment, owner attestations and genuine institutional acts. Other institutions, actual candidate judgments, formation-specific competence/delegation, custody/trust, appointments and B1 remain open. Do not repeat the accepted Guildhall lookup. Citadel receives; Castellan interviews; understanding closes interview authority; drafting and mission approval are separate; legitimate child constitution precedes handoff; receiving assessment grants no execution authority.
