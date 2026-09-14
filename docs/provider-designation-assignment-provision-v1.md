# PPC6 designation specialization v1 — offline candidate

Status: PARTIAL_DESIGNATION_ASSIGNMENT_EVIDENCE. This provision implements bounded parts of approved PPC2 A–F; it does not amend their competence or authorize FRESH establishment. The original proposal and separate owner approval remain unchanged.

## Custody, ingress and bounds

`FormationProfileDesignation` operates only through the existing Formation journal and its actual live same-root owner. Targets are exactly courtyard.courtthane and clavium.locksmith; stewardship is Laboratorium. The actual current native laboratorium.alchemist signs Ed25519 acts under a separately owner-signed delegation whose single purpose is DESIGNATE_FORMATION_PROFILE or REVOKE_FORMATION_PROFILE_DESIGNATION. DELEGATE_PERSONNEL_EVIDENCE cannot supply either purpose. Revoking the delegation's owner decision through FormationSignatures invalidates current use.

The state remains the explicit five-key imperium.formation-profile-designations/v1 extension: schema, initialization, delegations, events, current. Initialization is verified as historical signed evidence and grants no current Profile. New delegations use imperium.formation-profile-designation-delegation/v1. Their closed terms are schema, instance_id, citadel_id, steward, target, purpose, actor, public_key, not_before, expires_at, expected_head. Each retained delegation is exactly {terms, decision}, keyed by its canonical digest. Original decisions and signatures remain intact.

The act is exactly {payload, signature}; the payload is the approved PPC2 clause C field set without additions. The event value is exactly {envelope, candidate, attestations}; its key is the canonical envelope digest. Candidate retains the complete existing six-field chain, including the original signed exact approval. Original personnel envelopes remain in personnel_evidence; immutable PPC5 native binding, configuration, authorization, source line and seal originals remain in model_preparation. Current checks consume strict v2 through authorizedModelCandidateInOwner and verifyInOwner. Historical cryptographic reconstruction never renews an expired/revoked authority.

Bounds: 256 delegations, 256 total events and a maximum generation of 256; 16 MiB canonical designation-extension bytes; 1 MiB incoming act/candidate bytes; 1024 bytes for each nonempty correlation and reason; 48 lowercase hex nonce. Each referenced personnel envelope/delegation/approval is limited to 256 KiB; each native typed original is limited to 1 MiB. Cumulative referenced-original reconstruction is limited to 32 MiB across the retained event history (repeated work counts again). These limits apply before new current authority validation. Complete historical Senate findings, source envelopes, model authorization/delegation/binding/seal originals and correspondence remain required after terminal transitions. Existing strict PPC5 original/dependency bounds also apply. No pruning or generation recycling entry point exists. Overflow refuses the whole transition.

## Reference and generation map

| Identity | Meaning |
| --- | --- |
| Profile profile_id/profile_version/content_digest | Exact immutable generic Profile identity; unchanged generic contract |
| designation_generation | Per parent/Formation/steward/target event counter, including revocation |
| binding_generation | PPC5 native immutable binding lineage generation, verified against full O4 originals |
| O4 profile_generation | Independent current appointed holder generation |
| settings generation | Existing application/replacement counter, unchanged |
| personnel wrapper schema/id/digest | Explicit checked join for old schema-less personnel envelopes: imperium.formation-personnel-evidence/v1; versioned Profile evidence uses its actual v2 schema |
| event reference | imperium.formation-profile-designation-event/v1, envelope-digest identity and original-envelope digest |
| O4 references | Existing schema/id/sha256-prefixed-digest convention; never replaced by native digest-only identity |

## Lifecycle and history

Events reconstruct in increasing signed expected-head generation, not JSON object insertion order. Every per-target predecessor and increment must match; globally repeated heads and scoped nonces refuse. The index is derived from all signed events and compared exactly on every read/write. Each index entry retains last_event and an active reference or null; expiration is enforced against the observed clock and does not mutate the index. Tombstones remain after revocation.

An exact retained envelope plus candidate replay returns its historical event receipt before current-time/head checks. Changed bytes under the same actor/delegation/nonce refuse. Initial designation consumes the strict exact approved chain. A successor requires exact lineage.supersedes. A live predecessor receives superseded plus successor current_active attestations in one journal rename. A predecessor expired before the new signed issued_at receives expired; an already revoked predecessor is not relabeled. Acts prepared before an observed terminal boundary must be resigned with a matching timestamp (TERMINAL_OBSERVATION_RESIGN). Revocation records a terminal fact, never a replacement. Revocation after expiry retains a distinct expiry attestation and a following revocation attestation; the latter omits a false from-state and links to the expiry attestation. Attestations retain the generic closed contract and reference their governing complete signed payload in reason, with actor/time/correlation and prior attestation identity.

The component has not earned receiving acceptance. Full combination of all native/application/use orderings and every corruption bound remains an explicit proof obligation; the retained test map distinguishes executed cases from open cases.
