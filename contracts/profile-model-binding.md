# Authorized Profile model binding

Conscription consumes one Mission-Authorization-derived model-binding authority to create a new immutable Profile version for the exact target Seat. The revision preserves the complete source Profile, declares `SUPERSEDES` lineage, and adds the exact provider/model/version reference, non-secret runtime configuration, approved dossier line, Mission Authorization provenance, constraints, and fallback disclosures. Conscription accepts no caller-supplied binding terms: the configuration must be the exact configuration enumerated in the approved dossier and carried by the single-use authority.

The binding specification contains no secret and releases no credential. Sealing does not designate the revision current/active, assemble a manifestation, invoke the provider, deploy, or execute. Changed configuration, model version, target, limits, or fallback terms require a new approved dossier and Profile version.

The normal checkpoint is `PROFILE_MODEL_BINDING_SEALED_PENDING_ACCESS_AND_ACTIVATION_PREPARATION`.
