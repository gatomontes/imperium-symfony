# Canonical Native Effect Reconciliation Issuance Authority and Revocation-at-Use Remediation — revocation-race matrix v1

`PREPARATION_BATCH_0_RACE_MATRIX_ONLY`
`RESOLVE_REVOKE_CONSUME_COUNTEREXAMPLE_PRESENT`
`POST_RECEIPT_RECONSTRUCTION_REVOCATION_SOURCE_SPLIT_REQUIRED`

Where the listed condition can change independently before capability expiry,
the common sequence is:

1. at `t0`, issue existing reconciliation evidence and call
   `resolver->resolve(authorityId, t0)`;
2. after capability delivery, make the listed source change effective;
3. before capability expiry at `t1`, call claim admission/derivation;
4. `resolver->consume(capability, t1)` validates object/process/digests but does
   not re-resolve source currentness; and
5. one deterministic claim can be published. Later forward completion may
   refuse, which does not retroactively authorize the claim.

RR02, RR05 and RR11 are time-bound preservation cases. They cannot use the
common sequence to publish a stale claim. A native principal's Root act cannot
outlive its anchor; the native decision expires with the principal;
reconciliation expiry is bounded to that decision/principal expiry; activation
cannot become invalid after being valid at `t0`; and capability consumption
rejects at `t1 >= expiresAt`.

| ID | Change after resolution | Resolution-time check | Consume-time check | Current result | Required Batch 4 proof |
| --- | --- | --- | --- | --- | --- |
| RR01 | Operator Root trust anchor `revoked=true` | `NativeRootActs::verify()` requires false | none | stale capability remains consumable | claim publication refuses inside governed use cut |
| RR02 | Operator Root anchor reaches ordinary time-based expiry after resolution | native Root act cannot outlive its anchor; native decision/principal and reconciliation expiry are transitively bounded | `t1 >= capability->expiresAt` refuses before a stale use | no `t1` exists where the Root anchor is expired while the capability remains usable | preserve the transitive expiry proof; no additional at-use remediation |
| RR03 | Root anchor identity/key substituted | signature/anchor equality at `t0` | none | stored authority/issuance digests unchanged | substitution refuses before consumption |
| RR04 | Native principal effective `REVOKE` event | `NativePrincipal::load()` scans revocations | none | stale capability remains consumable | post-resolution native revocation refuses |
| RR05 | Native principal reaches ordinary time-based expiry after resolution | native-principal expiry bounds the decision and reconciliation expiry | `t1 >= capability->expiresAt` refuses | stale claim cannot publish through expiry; activation cannot reverse after valid `t0` | preserve existing boundary proof; no additional at-use remediation |
| RR06 | Higher source Imperator principal generation becomes effective | `NativePrincipal::source()` scans higher generations | none | stale generation can derive claim | supersession refuses |
| RR07 | Source principal lifecycle disposition becomes `SUSPEND` | v2 lifecycle reconstruction must be ACTIVE | none | stale capability can derive claim | suspended source refuses |
| RR08 | Lifecycle becomes `SUPERSEDE` or `REVOKE` | same | none | stale capability can derive claim | both refuse distinctly |
| RR09 | Lifecycle becomes `EXPIRE` or `RETIRE` | same | none | stale capability can derive claim | both refuse distinctly |
| RR10 | v3 source gains lifecycle record requiring migration | loader refuses at resolution | none | record introduced after resolution is unseen | consume refuses with migration/currentness error |
| RR11 | Reconciliation authority expires at `t1` | capability stores authority expiry | yes, `t1 >= expiresAt` | refuses `CNE624` | preserve exact boundary |
| RR12 | Authority/issuance record bytes substituted | digests captured at resolution | exact digest equality | refuses `CNE624` | preserve |
| RR13 | Claim already published by competitor | resolve checks claim absent; derive checks again under lock | claim absence under authority lock | one local winner | preserve contention proof |
| RR14 | Source revoked after claim publication but before receipt use | not applicable | `forwardComplete()` performs full `inspect()` | receipt mutation refuses | preserve; distinguish from missing claim-publication check |
| RR15A | Operator Root trust anchor becomes `revoked=true` after receipt publication | reconstruction passes historical admitted time to `inspect()` | `NativeRootActs::verify()` still reads the current untimestamped `revoked` flag | reconstruction refuses `NIR_ROOT_INELIGIBLE`; no new power is created | prove and retain this reconstruction limitation; do not claim historical success |
| RR15B | Timestamped native/source lifecycle revocation becomes effective after receipt publication | reconstruction inspects at historical admitted time | historical lifecycle reconstruction is time-indexed | read-only history may reconstruct when the Root anchor remains currently eligible | prove each timestamped revocation source separately; preserve no continuing power |

## Exact prior-test gap

The prior Batch 2 test `testRevokedRootAfterIssuanceRefusesFreshResolution()`
performs issue -> Root revoke -> **fresh resolve** and proves `NIR_ROOT_INELIGIBLE`.
It never retains a capability from before revocation and never invokes
`consume()` afterward. The prior concurrency proof likewise resolves and derives
without inserting a source lifecycle change between those calls. Therefore no
existing test proves resolve -> revoke -> consume refusal.

## Lifecycle/store inventory

| State | Store/read path | Used at resolve? | Used at consume? | Classification |
| --- | --- | --- | --- | --- |
| Root trust identity/revocation | `NativeState::TRUST/identity.json` | yes | no | `EXISTS_FRAGMENTED` |
| Native principal version | native transition `principals` | yes | no | `EXISTS_FRAGMENTED` |
| Native activation | native transition `activations` | yes | no | `EXISTS_FRAGMENTED` |
| Native revocation | native transition `revocations` | yes | no | `EXISTS_FRAGMENTED` |
| Source Imperator principal version | `NativeState::SOURCES['principal']` | yes | no | `EXISTS_FRAGMENTED` |
| Source lifecycle dispositions | `NativeState::SOURCES['lifecycle']` | yes | no | `EXISTS_FRAGMENTED` |
| Source generation scan | all source principal files | yes | no | `EXISTS_FRAGMENTED` |
| Reconciliation authority/issuance | immutable authority directories | yes | digest only | `EXISTS_CANONICALLY` |
| Reconciliation claim existence | claim directory | yes | under derivation lock | `EXISTS_CANONICALLY` |
| Capability registry/incarnation | resolver memory/private binding | issued at resolve | yes | `EXISTS_CANONICALLY` |

The target is not to serialize currentness into the capability. That would make
the stale snapshot look official. The target is present-tense re-resolution in
the same governed cut that consumes authority and publishes the derived record.

## Post-receipt reconstruction distinction

Historical `at` does not make every dependency historical. Native and source
lifecycle records are time-indexed, but Operator Root trust revocation is stored
as a current untimestamped boolean. Therefore read-only reconstruction is
conditional on current Root eligibility. This is an audit reachability
limitation, not continuing authority and not a reason to authorize Batch 1.
