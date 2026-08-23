# Oracle model-selection criteria

## Status

Planned.

## Objective

Enable Curia to request model research from Oracle using explicit, pre-determined criteria so Augur can identify an appropriate LLM for a specific Officer and mission function without wasting tokens on irrelevant evaluation.

## Required flow

1. Curia defines a Model Requirement Commission for the Officer or mission function.
2. The commission specifies the cognitive task, required capabilities, operational constraints, risk constraints, tool requirements, budget, latency, provider restrictions, data-residency requirements, and fallback expectations.
3. Oracle receives the bounded commission and instructs Augur to evaluate only accessible and admissible model candidates against those criteria.
4. Clavium supplies provider-access assertions or bounded access leases through its Locksmith; credentials and secret material remain in Clavium custody.
5. Augur returns evidence, ranked candidates, capability fit, costs, latency, limitations, risks, and operating conditions.
6. Curia selects or recommends a candidate.
7. The authorized authority approves where required.
8. Conscription seals the selected provider, model identifier, version, configuration, limits, fallback policy, and selection provenance into the Officer Profile.

## Office boundaries

- **Curia:** defines the required outcome and evaluation criteria.
- **Oracle:** manages model intelligence and the evaluation commission; it does not assign, authorize, substitute, or deploy models.
- **Augur:** performs bounded research and evaluation and reports evidence; it does not command model use.
- **Clavium:** detects and controls provider credentials and issues bounded access assertions or leases through its Locksmith; neither the Office nor its Officer discloses secret material to Oracle or Augur.
- **Conscription:** seals the approved model binding into the Profile.
- **Officer:** executes only under the sealed model assignment.

## Provenance requirements

The final assignment must preserve:

- Curia requirement commission;
- Oracle catalogue snapshot;
- Augur evaluation and evidence;
- candidate comparison and tradeoffs;
- selection and authorization decision;
- provider, model identifier, and version;
- runtime configuration and resource limits;
- fallback or substitution policy;
- model actually used at execution time.

A silent model substitution is prohibited. Model capability does not confer operational authority.
