> Current O0 correction: [report](handoffs/provider-onboarding-o0-correction-report.md) and [decision/evidence card](provider-onboarding-current-decision-evidence-card.md).
> The preceding provider/retry design is independently accepted as preparation with
> a validator qualification. Its correction now awaits review; final O0 freeze remains
> pending policy/evidence. Earlier status wording below is historical where superseded.
> Settled DeepSeek/API-key/FRESH/D2-A/count/dollar choices remain; O1–O5 and live work stay deferred.

# O0 owner decision sheet — proposed package A

> Current selected-route preparation and remaining dependencies are in the
> [provider closure report](handoffs/provider-onboarding-o0-provider-report.md).
> Earlier UNSELECTED/unapproved choice language below is historical where the
> selected-decision record supersedes it; it does not reopen settled choices.

> Current owner decisions supersede the historical unapproved choices below only
> as explicitly recorded in [selected decisions](provider-onboarding-selected-decisions.md).
> DeepSeek/API key, FRESH route, D2-A and three retries per assessment call with a
> $1.20 total cognition ceiling are selected. The earlier no-retry/$0.30 proposal
> is historical. Exact evidence/policy and implementation gates remain open.

> [F1/F2 clarification](../contracts/provider-onboarding-continuation.md) now
> specifies progression/replay and tagged authority records. It changes no
> recommendation, provider selection, threshold, budget or approval status here.
> Policy-derived effects are available only where its registry expressly permits
> them and the eventual owner policy explicitly includes the exact one-use slot.
> This is still a proposal, not a signer or new institutional competence.

**All choices below are UNAPPROVED.** Preparing this sheet neither selects a
provider nor authorizes implementation, trust enrollment or a call. The engineering
proposal is [D2](provider-onboarding-authority-proposal.md); [review status](reviews/provider-onboarding-o0-independent-review.md)
remains O0_REVIEWED_WITH_BLOCKERS. The owner decides competence, scope and limits;
the executor supplies the classes, schemas, stores and protocols for review.

## D2: competence decision

Recommended proposal A: approve the design allocation in D2 for further offline
schema closure. The human Operator authorizes exact bootstrap policy, bounded
infrastructure access/evidence admission, actual fresh constitution if legally
pre-operational, assessment commission and conditional assignment application.
Oracle/Augur authors assessment only; Laboratorium owns Profile designation;
Conscription owns qualification/binding under genuine authority. Existing
installations require governed prospective cutover. New typed records preserve
original root and institutional provenance. No Courtthane/Curia substitution.

Alternative B: approve a narrower design with a separate exact Operator application
act after every assessment; remove conditional application from the initial policy.
All other competence checks remain identical. This adds a decision after results,
without requiring the owner to design a protocol.

Decision needed: accept A, accept B, or identify the exact competence allocation
to change. Neither choice authorizes production actors, enrollment, a founding
exception or O1–O5. Actual custodian/issuer public identities, exact instance and
current installation mode remain required public evidence before a runnable act
can be prepared. No private material is requested. Choosing EXISTING does not
certify its chain; choosing FRESH cannot reopen a closed window.

## D1: provider choice for first implementation design

| Choice | Concrete proposed scope | Tradeoff / unresolved evidence |
| --- | --- | --- |
| A — DeepSeek API key (recommended for continuity of source work) | Adapter candidate `deepseek-bootstrap-api-key/v1`; fixed HTTPS host `api.deepseek.com`, GET `/models` for separately authorized access observation and POST `/chat/completions` for cognition; candidate IDs `deepseek-v4-flash` and `deepseek-v4-pro`; no redirects, OAuth, streaming, tools or alternate API surface | Existing source has a DeepSeek path, reducing source divergence. This is an engineering rationale, not a finding of lowest cost, best quality, account availability or bounded live conformance. New wire/usage integration still required |
| B — OpenAI API key | A separately described fixed OpenAI adapter and exact model set; no inherited DeepSeek mapping. API-key authentication is documented, but this pass does not propose unverified model IDs or prices | Requires a provider-specific model/destination/usage appendix before freeze; preserves the generic design |

Public references inspected on 2026-09-09, by 21:50:36 UTC: DeepSeek's
[first-call guide](https://api-docs.deepseek.com/) documents its API-key route and
base URL; its [model-list reference](https://api-docs.deepseek.com/api/list-models/)
documents the listing interface and example IDs. OpenAI's
[authentication reference](https://developers.openai.com/api/reference/overview)
documents API-key bearer authentication. These public pages establish interface
leads only. No authenticated endpoint was queried. No price, remote cancellation,
usage guarantee, suitability or account entitlement was verified. Attempts to
open two deeper DeepSeek reference pages failed; no claims depend on them.

Recommendation A is **not selected**. Exact request options, configuration digest,
tokenizer, revision pinning and structured usage compatibility need retained
provider documentation and reviewed serialization before implementation freeze.
If a provider alias cannot pin its underlying revision, proposed default is refuse
until the owner explicitly accepts the named alias limitation. Do not quietly
resolve that choice through a model default.

## D3: concrete proposed workload and policy values

These conservative engineering limits are proposed decision values, not evidence
of model capability or a charge estimate. Changing them changes policy version.
Do not round a provider tariff down to make it fit. No candidate passing the
limits means refuse, not increase budget or use a different provider automatically.

| Policy field | Package A proposed value | Owner decision |
| --- | --- | --- |
| Initial assignment roles | `courtyard.courtthane` and `clavium.locksmith` only; model settings only, never appointments | Approve both, or narrow to Courtthane only |
| Augur model | Pure least-cost eligible base among the exact selected provider's approved finite set, before assessment; never auto-replace it | Approve algorithm; actual winning tuple requires retained evidence |
| Candidate universe | For D1-A, the two exact IDs above; no automatic addition on catalogue refresh | Approve scope; other provider requires its exact set |
| Capability minimum | Text input; evidence-grounded comparison; machine-parseable JSON matching result schema; all required predicates supported; no external tools; context capacity at least 32,768 tokens, output support at least 4,096 tokens | Approve thresholds; actual capability proof remains required |
| Input budget per call | At most 16,384 tokens including system instructions, profile, evidence and prior context, with evidenced tokenizer | Approve ceiling; unknown token count refuses |
| Output budget per call | At most 4,096 total billable generated tokens, including separately billed reasoning where applicable | Approve ceiling; cannot enforce/measure total means unsupported |
| Context reserve | 4,096 tokens; input + output + reserve = 24,576, within required capacity | Approve reserve |
| Ordered assessment workload | W1 provider evidence matrix; W2 Courtthane model-fit assessment; W3 Locksmith model-fit assessment. One call each, concurrency 1, all by the bound Augur base model | Approve three calls; narrowing to Courtthane removes W3 and reduces totals |
| Rubric | Identity/runtime mapping, evidence support, minimum capability, exact role/Profile fit, access, admissibility/data constraints, complete tariff, bounds/usage support, contradictions, uncertainty; PASS required on all mandatory predicates | Approve rubric; Profile-specific predicate refs must be bound before execution |
| Evidence ages | Catalogue/capability at most 7 days; tariff at most 24 hours and valid through authorization expiry; account-access observation at most 15 minutes; at least one official source per mandatory factual claim | Approve; unknown or contradictory mandatory evidence refuses |
| Comparison workload | Same three scenarios at maximum token quantities and no cache discount; exact arithmetic from base policy | Approve upper-cost comparison; no average-use estimate |
| Cost ceilings | 100,000 micro-USD ($0.10) per cognition call; 300,000 micro-USD ($0.30) total and maximum comparison cost; no retry budget | Approve or specify replacements; this is not a remote billing guarantee |
| Call/time ceilings | 3 cognition calls, 60,000 ms local deadline each, 180,000 ms summed exposure; concurrency 1 | Approve; local deadlines do not prove remote cancellation |
| Access request | At most one separately authorized GET observation, 10,000 ms local deadline, response at most 1 MiB, cognition tokens zero; require evidence of zero fee before permitting it under a zero-cost cap | Approve design only; listing never substitutes for invoke-eligibility proof |
| Policy lifetime | 30 minutes from a concrete future signed issue time; no future-dated observations or automatic renewal | Approve duration; actual UTC interval supplied later |
| Application mode | One conditional all-or-none application to approved role tuples; retain current approved settings forever until explicit change; no automatic reassignment or fallback | D2-A approves conditional design, D2-B requires separate exact application act |
| Data scope | Public repository doctrine/Profile content and admitted public provider facts only; no installed/private data or external research tools | Approve scope |

Access and cognition reservations use one policy budget: maximum 4 total external
requests, 190,000 ms total exposure and 300,000 micro-USD. The access observation
adds no hidden fifth request or billable evaluation. If invoke eligibility cannot
be established without a paid probe, package A refuses; a separately proposed
bounded probe policy would need review rather than weakening the eligibility rule.

The workload text is fixed as follows, with variables resolved only from exact
retained public refs: W1: "Compare the approved candidate set against the ten
mandatory predicates using only the supplied evidence. Return each disposition,
source references, contradictions and unknowns. Do not infer access from listing."
W2: "For the exact approved Courtthane Profile, assess the approved candidates
against its declared criteria and the ten mandatory predicates. Return the
evidence-bound fitting candidates and limitations. Do not appoint or invoke."
W3 substitutes the exact approved formation Locksmith Profile and preserves the
remaining W2 text. Exact serialized prompts include this text, the bound holder
Profile and sorted referenced evidence; their wire digests are prepared before
authorization. Missing actual Profile/evidence/configuration refs blocks freezing
those bytes; this sheet does not fabricate them.

Target assignments must enumerate permitted `{role, model_id, model_version,
configuration_digest, profile_ref}` tuples in the final policy. The numerical
proposal does not authorize all configurations. Since no genuine target Profiles
or provider mapping have been supplied, those exact tuple refs remain unresolved.
They require separately lawful public evidence preparation; the owner need not
invent JSON digests. Atomic application requires the eventual exact role-specific
Profile approvals, so an assessment can remain a proposal without applying it.

## D4 and the actual next decision

Keep B1 unchanged. Evidence of supportable remote bounds is a live gate; no local
deadline, output cap or dollar number in this sheet settles it. Further offline
design is permitted without resolving live cancellation/billing. O0 completion,
implementation selection, deployment, enrollment and live commissioning are
distinct decisions; none follows automatically from approval of package A.

Owner response can name D2 A/B, D1 A/B, desired installation mode, and acceptance
or amendments to the D3 rows. After that decision, the executor resolves the exact
public artifacts/provider appendix and presents final schema/policy bytes for
review. Required signer, trust, incumbent and Profile evidence remains explicit.
All proposals stay unapproved until such a response is supplied.
