# Current campaign and flow — O3-B0 integrated; O3-B1 selected

O3-B0 is accepted and integrated through PR #792 at `32bb8d76424d6b7447cc423cb7932af35b1f074e`. The accepted tree passed fresh full CI: 3,612 tests / 56,618 assertions / four skips in 17:16.006. [Acceptance](reviews/provider-onboarding-o3-b0-correction-acceptance.md).

Next: [O3-B1 FRESH Augur binding and governed cognition](next-campaign-provider-onboarding-o3-augur-cognition.md). [Prompt and pull commands](handoffs/provider-onboarding-o3-augur-cognition-ready.md).

| Stage | Current state |
| --- | --- |
| O0, O1 and O2 | Integrated within their approved offline scope |
| O3-B0 adapter and custody | Complete, corrected and integrated |
| O3-B1 FRESH Augur binding/cognition | Selected for one local implementation run |
| O4-B0 atomic target assignments | Follows O3-B1 acceptance/integration |
| O5-B0 offline CLI journey | Follows O4-B0 |
| Live commissioning and existing-installation cutover | Separately deferred |

Current flow: admitted originals → eligible least-cost base proposal → exact approved runtime map → legitimate constitutional FRESH holder → separately authorized W1 → validated W2 → validated W3 → **stop before O4 application**. The bridge preserves O2/O3 one-use custody, same-source credential binding, shared exposure, frozen inputs and evidence-only recovery. No provider request or installation is performed by this preparation. Three planned implementation batches remain; all five operational flags stay false and actual retries remain disabled.

---

## Historical content (prior statuses/counts below are superseded above)

# Next local campaign — O3-B0 DeepSeek adapter and API-key custody

Ad Imperium.

O2 is integrated in PR #790, merge `9b50d53362e44af1d628034ad6153985b9a24d1c`. This selects **one direct O3-B0 implementation and validation run** from the published preparation. No further planning-only batch is required. [Contract](../contracts/provider-onboarding-deepseek-adapter.md), [local handoff](handoffs/provider-onboarding-o3-deepseek-adapter-ready.md), [prompt](handoffs/provider-onboarding-o3-b0-local-prompt.txt).

## Ordered work

1. Start in an isolated clean worktree; record entry commit/tree and inspect applicable repository instructions. Read accepted O2 correction and approved provider/policy sources.
2. Map the four actual O2 interfaces to fixed adapter/custody/transport/storage responsibilities. Declare exact internal evidence and response projections in a short design note. Separate missing production evidence from offline implementation capability.
3. Implement access verification and the dormant credential/HTTP/response path. Implement cognition wire/usage parsing as components without fabricating an Augur bridge.
4. Exercise actual O2 registration, admission, custody, settlement and recovery using original synthetic authority producers and a mock HTTP client. Add fault/concurrency cases at the newly owned boundaries.
5. Run targeted tests, existing O1/O2/FC/regression/inventory gates, changed PHP lint, kernel smoke and all 86 static specification checks. Capture exact tested PHP source once stable. Update report/steps once after implementation; do not repeat preparation.
6. Return one public all-deliverables ZIP and individual report/instructions. Stop for source review/full CI; do not start O3-B1.

## Required proof matrix

| Area | Required positive and negative evidence |
| --- | --- |
| Request construction | Exact approved GET/POST bytes and options; model/configuration substitutions, unknown fields, alternate destination, redirect/retry middleware, bad token/tariff evidence refuse |
| Real access integration | Actual B0 synthetic signing/admission and O2 coordinator invoke the new adapter with fake credential material and recording HTTP; no Augur needed, no generic fixture record relabeled as production authority |
| Authority and custody | Missing/forged/foreign/expired/revoked grant or binding; operation swap; wrong/copied/used capability; duplicate callback; credential rotation/currentness between checkpoints; zero unauthorized transport entries |
| Responses | Valid fixtures and duplicate keys, truncation, byte limit, conflicting model/usage/fingerprint, missing meters, arithmetic overflow, unsupported finish/HTTP cases; no absent value becomes zero |
| Time and errors | Total deadline includes keepalive/read time; 401/402/429/500/503, exceptions and timeouts yield documented refusal/unknown outcomes; no retry/fallback or released unknown exposure |
| Storage and recovery | Exact claim/path binding, immutable replay/conflict, atomic retention, traversal/symlink refusal, concurrent retain; interruption before/after metadata/envelope/settlement; restart reads evidence only |
| Budget and replay | O2 shared FC/onboarding exposure, same-ID replay, changed-ID retry attempts, revocation and expiry keep existing fences and maxima; all four R1–R3 reviewer tests remain green |
| Scope and secrecy | Constructor defaults stay dormant; no service/CLI activation; synthetic secret excluded from captured outputs and public files; raw evidence never silently rewritten |
| Cognition boundary | Exact approved wire plus usage parser unit/component tests; missing actual founding/holder/commission still refuses through O2; no positive Augur journey claimed |

Original test sources and approved policy/workload bytes are immutable. Existing regression selections in the correction report remain required. Discover actual new test names from implemented code; do not claim planned test classes already exist.

## Validation and delivery

Use PHP >=8.4 and locked Composer dependencies. Record commands, versions, exit codes and complete public logs. Rerun selected tests only after relevant source changes, then bind final passing results to exact committed PHP bytes. Record documentation-only post-test changes separately. Keep baseline/development failures and prior CI clearly separate from final evidence. Full hosted CI remains the later integration gate; targeted results are not full-suite results.

The ZIP must contain README, report/instructions, all canonical changed files, exact implementation patch, incremental Git bundle with entry prerequisite, entry/tested/final commit/tree identities, final commands/logs/exits, tested-PHP hashes and byte snapshot, protected-original comparisons, and the new proof evidence. Include a checksummed inner review ZIP and complete payload manifest. No outer checksum; avoid recursively embedding historical archives. Never include installed state, real credentials, private keys or private evidence.

Four planned batches remain including this one: O3-B0, O3-B1, O4-B0, O5-B0. Live commissioning and existing-installation transitions remain separately deferred. O3-B0 acceptance must not be represented as account access, provider qualification or permission to activate.
