# DeepSeek public-provider appendix — O0 v1.3.1

Status: PUBLIC_DOCUMENTATION_RETAINED; RUNNABLE_POLICY_BLOCKED.
This author preparation is not independent acceptance, account access evidence,
a tariff guarantee or live permission. Selected decisions remain DeepSeek/API key,
FRESH, D2-A, four attempts per W-group and $1.20 aggregate. Proposed technical
settings below still require approval and evidence. No provider/account API was
called. Only unauthenticated pages on api-docs.deepseek.com were retrieved.

## Reproducible source boundary

[Source index](provider-onboarding/deepseek-source-index.json) records each exact
URL, final URL, UTC retrieval timestamp, HTTP status/headers, byte length, SHA-256
of original HTML and SHA-256 of extracted UTF-8 text. Nine pages returned HTTP 200
at 2026-09-10 00:33:04–00:33:06 UTC. The evidence packet includes those original
HTML/text bytes, extraction/fetch script and manifest. The text digest identifies
the retained extraction, not a signed provider statement. Provider dates/version
labels are observations, not proof of a future effective interval or account scope.
Initial browser-tool pricing/error requests timed out; subsequent ordinary public
HTTPS retrieval succeeded. No source was silently substituted. Hyperlinks to
account, credential, CDN tokenizer, SDK or other API surfaces were not followed.

## Support and remaining evidence

| Source / locator | Retained observation | Preparation consequence |
| --- | --- | --- |
| [Pricing](https://api-docs.deepseek.com/quick_start/pricing/), Model Details / Pricing / Deduction Rules | Flash and Pro request IDs accompany version labels DeepSeek-V4-Flash-0731 and DeepSeek-V4-Pro-0813; 1M context and 384K maximum output advertised. Peak cache-miss input/output USD per million: Flash 0.44/1.32, Pro 1.32/3.96. Prices may change. | Proposed finite universe remains these two text IDs. Vision experimental, other APIs and future catalogue additions excluded. Neither advertised context nor a version label proves exact tokenizer accounting or immutable dispatch revision. |
| [Chat reference](https://api-docs.deepseek.com/api/create-chat-completion/), Request / Responses | POST /chat/completions accepts model, messages, max_tokens, stream and JSON response format. JSON mode needs a JSON instruction and may truncate at length. Response describes id/model/created/system_fingerprint, finish_reason and usage meters including prompt cache split, completion/total and reasoning details. | Fixed local validation must bind full response to original custody/request and reject incomplete or contradictory usage. JSON syntax does not prove rubric correctness, institutional authority or genuine evidence. |
| [Thinking guide](https://api-docs.deepseek.com/guides/thinking_mode/), Toggle / Input and Output Parameters | Thinking defaults enabled/high; sampling parameters have no effect in that mode. Explicit disabled thinking is documented. | Propose disabled thinking with temperature 0 for this public workload, requiring Profile-fit review. Do not assume legacy temperature 0.2 constrains current default thinking behavior. |
| [Token guide](https://api-docs.deepseek.com/quick_start/token_usage/), Calculate token usage offline | Character ratios are approximations; usage is returned by the model. A demo tokenizer ZIP is linked. | No demo downloaded/executed. Exact tokenizer revision, chat framing overhead and conservative preflight bound for these version labels remain unestablished. A character estimate cannot satisfy the input ceiling. |
| [Model list](https://api-docs.deepseek.com/api/list-models/), Responses | GET /models lists available IDs/ownership data. | No authenticated listing performed. No free-of-charge guarantee, exact account invoke entitlement or immutable revision-pinning guarantee established by this page. Listing cannot close invoke-access eligibility. |
| [First call](https://api-docs.deepseek.com/), API configuration | Documents API-key bearer authentication and https://api.deepseek.com base URL. | Select only HTTPS port 443, GET /models and POST /chat/completions. Bearer secret exists only inside eventual approved custody callback; no key is in templates or requested now. No redirects/alternate hosts/Anthropic/Responses endpoint. |
| [Errors](https://api-docs.deepseek.com/quick_start/error_codes/), code table | 400/401/402/422 describe request/auth/balance/parameter failures; 429 rate limiting; 500/503 recommend retrying. | Operational suggestions do not establish attributable completion, charge settlement or safe replay. Do not follow provider suggestions to switch providers or top up. Empty retry allowlist below. |
| [Rate limits](https://api-docs.deepseek.com/quick_start/rate_limit/), Keep-Alive Mechanism | Non-streaming requests can send empty lines while waiting. Server closes after ten minutes if inference has not begun. | A proposed 60-second total local deadline must include whitespace/read time and cannot be extended by keepalive. Neither this closure rule nor local timeout proves remote cancellation or zero billing. |
| [Cache](https://api-docs.deepseek.com/guides/kv_cache/), usage / hit behavior | Describes cache-hit/miss accounting and caching behavior. | No cache benefit is guaranteed here; every comparison input token uses peak miss pricing. |

No source establishes a request revision-pinning field for the observed version
labels. Keep exact advertised ID, observed label/time and returned fingerprint
separate. They do not prove the same underlying weights on later requests. The
owner must explicitly accept the exact unpinnable alias limitation, or provide
supported immutable request revision evidence. Alias acceptance alone cannot
waive access, capability, usage or B1 gates.

## Exact configuration and serialization proposal

[Public preparation](provider-onboarding/o0-public-preparation.json) contains two
finite configuration candidates. Proposed body fields: model (one named ID),
messages (system then user), max_tokens 4096, stream false, thinking disabled,
temperature integer 0, response_format json_object. No tools, stop sequences,
reasoning_effort, top_p, streaming options or user_id. Content-Type is
application/json. No SDK defaults or retry middleware may add semantics. The
Idempotency-Key header in current source has no documented exactly-once guarantee
in this snapshot; local one-use custody provides the boundary, never that header.

Resolve the exact approved public Profile, holder, catalogue/tariff/capability
and predecessor-group results into the fixed workload before computing canonical
input/request/configuration digests. Preserve strings and array order, sort object
keys, reject unknown keys/duplicate keys/nonfinite numbers, encode UTF-8 without
BOM or insignificant whitespace. Actual runtime canonicalization must match
repository CanonicalJson and retain original bytes. These template-file SHA-256s
are evidence identities, not the final policy's canonical semantic digest. The
preparation wrapper is deliberately invalid as a runtime policy and has no
signature, trust receipt, command claim or manufactured public act.

All attempts in one group use the same frozen semantic request inputs/model/
configuration/holder. Correlation binds each unique claim and response separately;
a new correlation ID does not authorize another call. Responses are untrusted
input: retain original bytes, integer nonnegative usage, exact request/claim
association, actual model/fingerprint and finish reason. Proposed complete usage
requires prompt=hit+miss and total=prompt+completion, with reasoning accounted
inside completion rather than added twice. Missing detail must not become zero.
The reference's minimal examples omit fields present in its response schema;
examples cannot waive required evidence. Exact accounting inclusions and settlement
rounding still need review before a conformance claim.

## Fixed retry evidence policy for this snapshot

`retryable_failure_allowlist = []`. There is NO admitted DeepSeek failure code
that currently permits a retry. Slots 1–3 remain unused unless a future separately
reviewed policy establishes sufficient provider-specific predicates. Do not update
an active immutable policy's allowlist in place or use a new policy to clear old
unknown exposure.

A future admission would need retained original request/claim and custody lineage,
full attributable final response, independently verified final classification,
complete settled usage or explicitly preserved exposure, an exact provider-supported
reason and a verifier proving no unresolved predecessor. These predicates are
necessary, not a present authorization. Even an insufficient_system_resource
finish reason is not admitted by this snapshot. Generic HTTP statuses, exception
messages, model assertions, elapsed time and empty response are insufficient.

Local policy/authority/currentness refusals do not consume a retry. After dispatch,
timeout, lost response or missing attribution/usage is OUTCOME_UNKNOWN and retains
the full reservation; no new slot/sequence/command bypass. A final attributable
invalid assessment terminates progression while retaining any unsettled exposure.
A successful valid report with no fitting candidates can end its group but never
qualify an assignment. Evidence-only resume recognizes original outcomes; it does
not dispatch or renew expired authority. The v1.3.1 graph proves only a proposed
protocol: no runtime support or safe retry has been demonstrated here.

## Conditional cost evidence, not eligibility

[Exact integer arithmetic](provider-onboarding/o0-cost-comparison.json) uses the
proposed 16,384 input / 4,096 total generated token maxima. Ceiling each meter to
micro-USD; use peak miss rates for all input and peak output, without discounts.

| Candidate | One attempt | Three-success baseline | Twelve-attempt ceiling |
| --- | ---: | ---: | ---: |
| deepseek-v4-flash | $0.012616 | $0.037848 | $0.151392 |
| deepseek-v4-pro | $0.037848 | $0.113544 | $0.454176 |

Both arithmetic rows are below $0.10/$1.20 under their stated assumptions.
Neither is an eligible winner: exact tokenizer/framing, supported generated-token
bound, effective tariff interval, fee/rounding/account/data scope and B1-compatible
remote semantics remain UNKNOWN. Zero-cost GET access is also unproved. There is
no paid probe or automatic fee allowance. The policy reserves maxima, retains
unsettled maxima plus settled actuals under one lock, and never relies on these
conditional estimates to release uncertainty. Approved count/cost choices remain
12 cognition attempts/$1.20, with separately proposed one access request, 13 total
requests, 730,000ms exposure and 30-minute policy lifetime.

## Existing source is not this adapter

[Configuration normalizer](../src/Imperium/Runtime/Citadel/DeepSeekDelegateModelConfiguration.php)
allows only flash and temperature. [Platform adapter](../src/Imperium/Runtime/Citadel/DeepSeekSymfonyPlatformAdapter.php)
returns asText through generic Symfony AI and sets an Idempotency-Key header.
It does not expose this proposed closed configuration, attributable usage or
remote-bound protocol. Pro is not enabled by naming it in a JSON template.
No source/config/dependency changed and no PHP/SDK code was executed. Genuine
producers/consumers in the [prerequisite map](provider-onboarding-prerequisites.md)
remain prerequisites. This appendix does not turn an unimplemented design into
conforming infrastructure.
