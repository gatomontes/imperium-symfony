# Mission Authorization derivation

Runtime mechanically consumes one approved-dossier derivation authority to seal a Mission Authorization whose scope is exactly the approved dossier version, digest, numbered lines, proposed bindings, resource demands, disclosures, limits, fallbacks, and lifecycle conditions. Derivation may not add, infer, reinterpret, or broaden a term.

Mission Authorization is an authority source, not an action. It opens only exact, single-use preparation authorities required by the dossier, including model-binding/Profile sealing, personnel preparation, tool/credential/data preparation, and later execution-commission derivation. No authority permits silent substitution or direct execution.

The normal checkpoint is `MISSION_AUTHORIZATION_SEALED_PENDING_AUTHORIZED_PREPARATION`. Derivation itself performs no Profile mutation, credential release, provider invocation, deployment, external effect, or execution.
