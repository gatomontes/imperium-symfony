# Canonical Native Effect Process Custody and Formal Closure Remediation — terminal audit v1

`CANONICAL_NATIVE_EFFECT_PROCESS_CUSTODY_FORMAL_CLOSURE_REMEDIATION_COMPLETE`
`PROCESS_CUSTODY_AND_FORMAL_CLOSURE_ACCEPTED_BOUNDED_NO_LIVE_EFFECT`
`ZERO_CAMPAIGN_STAGES_REMAIN`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

## Claim

The campaign claims bounded closure of process-incarnation continuation custody,
first-execution/recovery separation and its missing formal evidence sequence. It
does not claim or authorize a real provider effect.

## Blackquill pressure test

The weak point in the predecessor was not subtle: an object registry that could
be serialized with its objects was theater masquerading as custody, and a
callback API that could silently become a receipt-recovery API was two powers
wearing one method name. Both defects are now cut at their actual seams.

Custody is bound to actual runtime PID, an issuer-private non-persistent nonce,
the exact issuer registry and the exact capability object. Serialization,
crafted unserialization and clone throw. Fresh interpreters cannot restore the
nonce; forked inherited memory changes PID and refuses. Authority-supplied
process labels remain provenance, not authentication.

First execution validates custody before callback-start publication and cannot
return an existing receipt or bind a sealed response. `reconstruct()` is
read-only. Forward mutation requires an exact, unexpired, sealed reconciliation
authority and derived claim, accepts no continuation/callback/payload/key, and
cannot invoke a provider or resolve a credential. Exact replay returns the one
receipt without callback reinvocation.

## Evidence

The staged commit/merge chain is retained from Preparation Batch 0 through Batch
4. Batch 5 began separately from clean merged Batch 4 `main` at `83fc4d6`.
Exact-candidate local proof passed focused `80 tests / 692 assertions` and full
`2371 tests / 50100 assertions` on Windows/PHP 8.4.14.

GitHub Actions [run 33826904446](https://github.com/gatomontes/imperium-symfony/actions/runs/33826904446),
job `100881403332`, independently tested exact pushed merge
`c47adc531d1d6191b3e00f20f056ed69975289d2` on Ubuntu/PHP 8.4.25. It passed the
full suite at `2371 tests / 50101 assertions`; the one additional assertion is
the supported Linux `pcntl_fork` child-refusal proof.

## Verdict and limits

The bounded campaign closes. The remaining boundaries are not euphemized:
multi-host/distributed custody, hostile process-memory access, compromise of the
trusted local immutable store and production of the upstream reconciliation
authority are outside this campaign. The authority producer is not fabricated
by a fixture and no provider route has been opened.

No provider, AgentMail, credential, network, mission, email, Iron Gate or
Lazaretto effect occurred. Batch 7 remains suspended and requires its own exact
future authorization. Zero stages remain in this remediation campaign.
