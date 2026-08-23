# Complete planning dossier

Curia mechanically assembles one immutable dossier from an exact drafted Mission Plan turn, every planning-only proposed model binding, declared resource demand, and explicit execution-relevant disclosure. The dossier is rendered as one plain, consecutive `1…N` list for easy human reference; every displayed line also carries an exact digest. The dossier records facts, assumptions, unknowns, dependencies, personnel, tools, credentials, data, external operations, costs, time and retention limits, risks, contingencies, fallbacks, evidence, provenance, reporting, expiry, revocation, and reauthorization conditions.

Assembly performs no new judgment and grants no approval, resource, binding, credential-release, model-assignment, provider-invocation, deployment, or execution authority. It creates one review authority bound to the exact dossier digest under which Imperator may `APPROVE_DOSSIER` or `OBJECT_RETURN_FOR_REVISION`.

The normal checkpoint is `CURIA_PLANNING_DOSSIER_SEALED_PENDING_IMPERATOR_REVIEW`. A legacy approval of an earlier plan turn is not approval of the assembled dossier.
