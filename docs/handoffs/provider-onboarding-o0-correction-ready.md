# O0 correction — review handoff

Read the [correction report](provider-onboarding-o0-correction-report.md),
[current decision/evidence card](../provider-onboarding-current-decision-evidence-card.md),
[preceding independent review](../reviews/provider-onboarding-o0-provider-independent-review.md)
and [current campaign](../next-campaign-provider-onboarding-o0.md).

Review the exact final commit/tree in the accompanying identities.json and
reconstruct the local candidate from the evidence bundle in a disposable checkout.
The correction starts at 2212281ea33ea19c8c70b048b8e482bbe23dfa12. Verify ancestry,
source manifests, changed paths and post-check records. The checker is now tracked
at tools/check_provider_onboarding_spec.py. Run with ordinary Python 3 from the
candidate root, without dependencies or network:

```text
python tools/check_provider_onboarding_spec.py .
```

It retains the original 43 named examples and adds 19 checks (18 rejection cases
and one public-ref shape acceptance example). The fixture ref is in-memory data,
not genuine authority. Inspect both V1 regressions and the declared preparation
variants; keep authentic runtime refs distinct from unresolved descriptors.
Neither 62 passing checks nor synthetic retry outcomes prove a production runtime.

The current decision/evidence card consolidates P1–P9 and separates later genuine
acts from O0 preparation. Verify that approved choices are not reopened, unapproved
values are not silently accepted, and the empty safe-retry allowlist is preserved.
The next owner decision is approval/amendment of those concrete policy rows;
missing genuine inputs must retain their competent producer and required stage.
No further provider comparison or broad new preparation campaign is requested.

Retain CY/FC offline acceptance, DEFER_ENROLLMENT, unresolved B1 and all five false
operational flags. O1–O5, account API calls, live commissioning, push and merge
remain deferred. This handoff requests review only, not activation or source changes.

For every later handoff, include all produced public deliverables in ONE
<campaign>-all-deliverables.zip with README and the evidence packet plus its own
checksum. Verify completeness/CRC and give the exact local path. No outer checksum
is needed. Also provide individual report/card/instruction links when ZIP downloads
fail. Do not include credentials or private runtime material.
