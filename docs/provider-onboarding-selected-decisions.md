> Current owner decision: [P1–P9 approval](provider-onboarding-policy-approval.md) is recorded.
> Earlier pending-value language is historical for those exact choices. Genuine
> evidence and final O0 review remain open; O1–O5 and live work remain deferred.

# O0 owner decisions — DeepSeek, fresh instance and bounded retries

Status: **OWNER_CHOICES_RECORDED; EXACT_POLICY_AND_EVIDENCE_PENDING**.
Recorded from the owner conversation on 2026-09-10. This record captures design
and resource choices; it is not a signed runtime policy, installed trust, live-call
authorization, O0 completion, implementation selection or permission to merge.

## Decisions actually supplied

| Item | Owner decision |
| --- | --- |
| Application | D2-A: bounded initial approval permits permitted assignment application; settings persist until explicit operator change; no automatic fallback or Augur self-replacement |
| Provider | DeepSeek via API key for the first implementation |
| Installation route | FRESH instance, beginning from the top; the existing installation is preserved. Actual fresh authority still requires evidence of an unconsumed founding window |
| Assessment scope presented | W1 provider comparison, W2 Courtthane fit, W3 Locksmith fit, performed by the bound Augur base model |
| Retries | Up to three retries per assessment call, interpreted explicitly to the owner as four attempts per call and twelve cognition attempts maximum |
| Per-attempt ceiling | 100,000 micro-USD ($0.10) |
| Total cognition ceiling | 1,200,000 micro-USD ($1.20), explicitly approved after the twelve-attempt explanation |
| Retry safety | Confirmed failures safe to retry only. Uncertain outcomes, including timeouts where the provider may have processed a request, pause for reconciliation |

Conversation provenance: after D2-A was recommended the owner said “Let's do it.”
The assistant identified D2-A as settled. The owner then selected “DeepSeek is fine
for now” and “Fresh instance. Let's take it from the top”. The initial workload
and limits were presented; the owner amended the retry policy to “3 retries”.
The assistant explicitly stated three retries per assessment call, twelve maximum
attempts, $0.10 each, $1.20 total and uncertainty fencing. The owner answered “yes”
to the $1.20 ceiling. These are chat decisions, not cryptographic institutional acts.

## Carried limits and remaining engineering

Retain the presented 60-second local deadline per attempt, 30-minute policy
lifetime, public-data scope, and existing concurrency-one proposal. Twelve attempts
produce at most 720,000 ms summed local deadline exposure; the separately proposed
single access observation adds at most 10,000 ms, for 730,000 ms and thirteen
external requests. The retry approval applies to cognition only, not access,
founding, qualification, cutover or assignment application.

The access observation remains the existing separately bounded proposal: at most
one request, zero-cost cap requiring evidence, no implicit retry. The aggregate
money ceiling stays $1.20; it creates no independent access budget. Token ceilings,
exact capability/Profile predicates, freshness/access evidence, candidate/revision
and configuration tuples still need the exact provider/policy appendix. The owner's
budget response is not blanket approval of every unpresented technical field in
the historical decision sheet. Prepare those fields concretely and identify only
indispensable unresolved choices; do not ask again for the provider, fresh route,
D2-A application mode, retry count or $1.20 ceiling.

Read the [retry amendment](../contracts/provider-onboarding-retries.md). The
previous F1/F2 acceptance remains historical acceptance of its reviewed v1.2 draft;
the retry amendment changes that draft and needs targeted consistency review.
CY/FC acceptance is unchanged. O1–O5 and live commissioning remain deferred.
