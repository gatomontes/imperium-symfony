> Current selection decision: [medium capacity](provider-onboarding-medium-capacity-decision.md) supersedes the earlier
> unspecified target-assignment choice. All other approved P1–P9 values remain.

# O0 — current decision and evidence card

Status: P1_P9_OWNER_APPROVED; EXACT_EVIDENCE_AND_FINAL_REVIEW_PENDING.
This card consolidates the current selected route. It supersedes obsolete pending
choice and numerical rows for current presentation only; historical records remain
unchanged. After the correction, the owner replied “proceed” to the explicit
request to approve or amend P1–P9. Those presented policy/design values are now
approved as recorded in the [approval record](provider-onboarding-policy-approval.md).
The [correction report](handoffs/provider-onboarding-o0-correction-report.md) records
validation and the [independent review](reviews/provider-onboarding-o0-provider-independent-review.md)
records the preceding review verdict. O0 final freeze remains pending.

## Settled — do not ask again

| Decision | Recorded choice |
| --- | --- |
| Provider/authentication | DeepSeek, API key; one provider for first implementation |
| Installation route | FRESH, separate from the existing installation; no reset or reopened founding window |
| Authority design | D2-A: bounded initial consent can permit one all-or-none application of the exact permitted assignment set after genuine authority and predicates pass |
| Retry ceiling | Three retries per assessment call, four attempts per W1/W2/W3 group, at most twelve cognition attempts |
| Dollar ceilings | $0.10 / 100,000 micro-USD per attempt; $1.20 / 1,200,000 micro-USD total |
| Persistence | Settings persist until explicit operator change; no silent fallback, automatic reassignment or Augur self-replacement |

The retry ceiling is not a retry guarantee. The retained DeepSeek failure evidence
supports an EMPTY safe-retry allowlist. Actual retries currently refuse; unknown
outcomes retain exposure and fence progression. This correction adds no reason.
The approved dollar ceiling is not a demonstrated remote billing guarantee.

## Approved P1–P9 — values fixed; genuine evidence still required

These are the existing proposals, not new defaults. Their exact source is
[o0-public-preparation.json](provider-onboarding/o0-public-preparation.json),
[o0-workloads.json](provider-onboarding/o0-workloads.json), the
[provider appendix](provider-onboarding-deepseek-appendix.md), the
[original decision sheet](provider-onboarding-owner-decisions.md) and
[base policy](provider-onboarding-base-model-policy.md). No model has been selected
as an eligible winner. Approval of these rows does not admit missing evidence.
The linked preparation snapshots retain their earlier status labels; the new
source-bound approval record supersedes those labels for the specified choices.

| Row | Approved policy/design value |
| --- | --- |
| P1 — finite candidates and alias limitation | Only `deepseek-v4-flash` and `deepseek-v4-pro`; no automatic catalogue additions. Retained observed labels are `DeepSeek-V4-Flash-0731` and `DeepSeek-V4-Pro-0813`. Accepted limitation: request revision pinning is unestablished and these named aliases do not guarantee unchanged weights; no substitution or automatic catalogue expansion |
| P2 — request configuration | HTTPS `api.deepseek.com:443`; cognition POST `/chat/completions`; system then user messages; `max_tokens: 4096`, `stream: false`, `thinking: {type: disabled}`, integer `temperature: 0`, `response_format: {type: json_object}`. No tools/tool_choice, top_p, reasoning_effort, stop, user_id or stream_options. No redirects, alternate endpoints or hidden middleware retries |
| P3 — capability and token limits | Text input and evidence-bound structured JSON comparison; no external tools; every exact approved Profile predicate must pass. Input maximum 16,384 tokens including instructions, Profiles, evidence and prior context; generated maximum 4,096 total billable tokens with reasoning accounted without double-counting; minimum context 32,768; reserve 4,096. Input + output + reserve is 24,576. Exact tokenizer/framing/bounds evidence required; unknown refuses |
| P4 — workload and targets | W1 provider evidence matrix → W2 exact Courtthane Profile fit → W3 exact formation Locksmith Profile fit, by the bound Augur base model, concurrency one. Exact target seats `courtyard.courtthane` and `clavium.locksmith`; model settings only. One group freezes its semantic inputs before attempt 0; application consumes all three selected valid outcomes locally. Complete permitted model/configuration/Profile tuples and expected assignment generations remain required |
| P5 — rubric and result contract | The ten mandatory predicates below; PASS required for eligibility and exact Profile fit. One result row per approved candidate, evidence refs confined to frozen input, no unknown/duplicate objects or keys. Schema/usage/attribution validated by the fixed verifier; model text supplies no authority. Exact workload texts and result proposals are retained in o0-workloads.json and approved as the presented workload/result proposal |
| P6 — evidence ages and comparison | Catalogue/capability maximum 604,800,000 ms (7 days); tariff 86,400,000 ms (24 hours) and valid through authorization expiry; access 900,000 ms (15 minutes). At least one official source per mandatory factual claim; contradictory or missing support refuses. Same W1/W2/W3 scenarios and conservative token ceilings for both candidates, no assumed cache discount, exact micro-USD arithmetic, stable tie-break; show three-success baseline and twelve-attempt maximum |
| P7 — time and response bounds | 60,000 ms local deadline per cognition attempt; 720,000 ms summed cognition exposure. Policy lifetime 1,800,000 ms (30 minutes), with actual issue/expiry instants supplied later. Response limit 1,048,576 bytes. No automatic extension, renewal or implication of remote cancellation |
| P8 — access observation and shared totals | One separately authorized GET `/models`, at most 10,000 ms, zero cognition tokens, zero fee required by evidence, no retries. With cognition: maximum 13 external requests, 730,000 ms summed local deadlines, concurrency one and the already approved $1.20 shared ceiling. Listing alone never proves invoke entitlement; no paid probe allowance |
| P9 — data scope | Public repository doctrine/Profile content and admitted public provider facts only; no installed/private data, credentials in prompts or external research tools. Credential values remain within the eventual approved custody callback |

The ten mandatory predicates are identity/runtime mapping, evidence support,
minimum capability, exact role/Profile fit, access, admissibility/data constraints,
complete tariff, bounds/usage support, contradictions and uncertainty. A report can
validly return no fitting candidates; it cannot thereby authorize application.

P1–P9 approval is recorded; do not ask for it again. Exact Profile predicates, tuples
and evidence bindings cannot be approved by substituting unspecified values. This
approval is only policy/design scope; implementation, deployment, enrollment
and live requests remain separate decisions. Earlier three-call/$0.30/no-retry
rows are historical and are not the current ceiling.

## Evidence — producer and stage

| Required material | Competent source/producer | When needed; what can be done now |
| --- | --- | --- |
| Intended new instance and issuer identity | Actual Operator identifies intended instance/Citadel and issuer; authority design names genuine competence | Public intended identifiers and provenance description can be prepared during O0. Naming FRESH does not create an instance, certify an open window or reset the old installation |
| Public trust and scope | Operator-authorized trust administrator; eventual BootstrapActVerifier consumes independently confirmed public key/fingerprint, scope, validity and revocation source | Review ingress design now. Enrollment receipt and installed trust are produced only during a separately authorized ceremony; DEFER_ENROLLMENT remains |
| Fresh root/window and founding binding | Genuine Operator-root installation/window records; future FoundingAugurBridge preserves original provenance | Specify producers and required records now. Actual open-window proof, founding act and occupancy belong to authorized fresh setup; no checksum-based development act or fixture substitute |
| Augur, Courtthane and Locksmith Profiles and holder evidence | Persona custody from Garrison; seat Profile derivation/designation from Laboratorium; competent approval and required qualification/binding from actual institutional producers, or genuine constitutional founding provenance for the fresh branch | Draft intended criteria now; approved public Profile bytes can be supplied when genuinely available. Do not require standing-route ceremonies as a substitute for the distinct fresh path. Exact approvals/holder/currentness must exist before the corresponding invocation/application |
| Complete permitted tuples and runtime map | Operator approves finite whole objects; future mapping/admission consumers bind exact model/configuration/Profile and adapter version | Prepare finite definitions after P1–P5 choices. Current legacy adapter does not implement the proposed closed configuration/Pro/usage surface. No template name confers support |
| Public provider capability, tariffs and tokenizer/framing | Official provider material, exact tokenizer/version/accounting documentation; future reviewed wire/preflight/response verifier | Retained public snapshots and conditional arithmetic already exist. Exact framing, conservative token support, fees/rounding/effective interval and required usage must be evidenced before eligible selection/live conformance; do not turn missing fields into zero |
| Account-scope invoke access and zero-fee observation | Authentic provider/account evidence; eventual authorized infrastructure observation and fixed recorder | Identify missing provenance/scope now. Authenticated requests require their own later authority; key presence or GET listing alone is insufficient, and no paid probe is implied |
| Remote cost/time/cancellation semantics | Provider-supported evidence plus the accepted custody/transport protocol; any changed residual-risk policy requires explicit separate owner decision | B1 stays UNKNOWN and blocks live qualification. It does not prevent separately selected offline engineering that refuses when evidence is missing |
| Current heads, budget, assignment generations and policy admission | Future trusted aggregate/admission/assignment consumers under genuine authority | Needed at actual admission/application time after the relevant software and authority exist. Never fabricate zero generations, future signatures or completed receipts to fill O0 placeholders |

Software prerequisites remain the pre-Augur evidence/access path, fresh founding
bridge, exact authority/currentness resolver, catalogue/runtime binding mapping,
structured usage transport, shared custody/budget integration and atomic assignment
consumer. They require separately selected implementation and proof. Completing
this card cannot implement them; genuine live acts are not a prerequisite to
writing separately authorized offline code with explicit refusal behavior.

CY/FC remain accepted and integrated within their offline scope. O1–O5 are still
deferred. All five operational flags remain false: deployment_approved,
enrollment_authorized, live_ready, activation and execution_authority.
