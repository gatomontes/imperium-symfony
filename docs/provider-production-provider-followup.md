# Provider evidence follow-up — public observations only

Observed on 2026-09-13 while selecting PPC1. Only unauthenticated public documentation was consulted. No provider/account API, credentials, billing dashboard or installed state was accessed. The earlier O0 originals and approved policy remain unchanged.

| Source | Observation and limit |
| --- | --- |
| [DeepSeek model-list reference](https://api-docs.deepseek.com/api/list-models/) | The retrieved response example names `deepseek-flash` and `deepseek-v4-pro`. Repository `DeepSeek/Wire.php` and approved P1–P9 pin `deepseek-v4-flash` and `deepseek-v4-pro`. This is a documented-example mismatch, not proof that the old alias has stopped working or permission to replace it. The page documents listing shape; it does not establish this deployment's account entitlement or a zero-fee guarantee. |
| [DeepSeek public homepage](https://api-docs.deepseek.com/) | Search surfaced a V4.1-Flash announcement directing use of `deepseek-flash`. The observation reinforces the need for later source reconciliation; this preparation does not select that model or infer an immutable underlying revision. |
| [DeepSeek pricing page](https://api-docs.deepseek.com/quick_start/pricing/) | Direct retrieval timed out. Search returned an older crawled result with the approved V4 aliases. That cached result is not a newly verified current tariff and supplies no account-specific billing evidence. No price facts were refreshed into the runtime. |

Before a provider-dependent implementation or live proposal, reconcile the exact permitted alias universe with current primary sources and actual account evidence. A new alias requires an explicit change to the already-approved finite universe; prior acceptance of unpinnable weights does not authorize arbitrary aliases. Preserve originals and record source dates and conflicts rather than silently normalizing the mismatch.

The zero-cost access design also remains an evidence requirement: one fixed GET `/models`, zero input/output tokens and zero micro-USD under the accepted grant. Neither documentation silence nor operator acceptance of risk proves the fee is zero. If supportable evidence is unavailable, keep the path blocked and present a concrete policy amendment for owner decision; do not invent a fee or loosen a maximum inside an adapter.

These observations are follow-up notes, not an authenticated source packet, fresh tariff, model selection or runnable admission evidence. PPC1 addresses internal constitution and Profile/binding facts and does not depend on resolving these external facts to write and test its software.
