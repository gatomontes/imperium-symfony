# Canonical Native Effect Reconciliation Issuance Authority and Revocation-at-Use Remediation — revocation-race matrix v1

`PREPARATION_BATCH_0_RACE_MATRIX_ONLY`
`RESOLVE_REVOKE_CONSUME_COUNTEREXAMPLE_PRESENT`

Common sequence for every row:

1. at `t0`, issue existing reconciliation evidence and call
   `resolver->resolve(authorityId, t0)`;
2. after capability delivery, make the listed source change effective;
3. before capability expiry at `t1`, call claim admission/derivation;
4. `resolver->consume(capability, t1)` validates object/process/digests but does
   not re-resolve source currentness; and
5. one deterministic claim can be published. Later forward completion may
   refuse, which does not retroactively authorize the claim.

| ID | Change after resolution | Resolution-time check | Consume-time check | Current result | Required Batch 4 proof |
| --- | --- | --- | --- | --- | --- |
| RR01 | Operator Root trust anchor `revoked=true` | `NativeRootActs::verify()` requires false | none | stale capability remains consumable | claim publication refuses inside governed use cut |
| RR02 | Root anchor expires before `t1` while capability expiry is later/equal | Root/act time checked at `t0` | capability expiry only | no independent Root recheck | boundary-time refusal; no derived record |
| RR03 | Root anchor identity/key substituted | signature/anchor equality at `t0` | none | stored authority/issuance digests unchanged | substitution refuses before consumption |
| RR04 | Native principal effective `REVOKE` event | `NativePrincipal::load()` scans revocations | none | stale capability remains consumable | post-resolution native revocation refuses |
| RR05 | Native principal becomes not current through activation timing/expiry | activation/revocation/time at `t0` | capability expiry only | source state not re-read | exact boundary refusal |
| RR06 | Higher source Imperator principal generation becomes effective | `NativePrincipal::source()` scans higher generations | none | stale generation can derive claim | supersession refuses |
| RR07 | Source principal lifecycle disposition becomes `SUSPEND` | v2 lifecycle reconstruction must be ACTIVE | none | stale capability can derive claim | suspended source refuses |
| RR08 | Lifecycle becomes `SUPERSEDE` or `REVOKE` | same | none | stale capability can derive claim | both refuse distinctly |
| RR09 | Lifecycle becomes `EXPIRE` or `RETIRE` | same | none | stale capability can derive claim | both refuse distinctly |
| RR10 | v3 source gains lifecycle record requiring migration | loader refuses at resolution | none | record introduced after resolution is unseen | consume refuses with migration/currentness error |
| RR11 | Reconciliation authority expires at `t1` | capability stores authority expiry | yes, `t1 >= expiresAt` | refuses `CNE624` | preserve exact boundary |
| RR12 | Authority/issuance record bytes substituted | digests captured at resolution | exact digest equality | refuses `CNE624` | preserve |
| RR13 | Claim already published by competitor | resolve checks claim absent; derive checks again under lock | claim absence under authority lock | one local winner | preserve contention proof |
| RR14 | Source revoked after claim publication but before receipt use | not applicable | `forwardComplete()` performs full `inspect()` | receipt mutation refuses | preserve; distinguish from missing claim-publication check |
| RR15 | Source revoked after receipt publication | reconstruction inspects at historical admitted time | no new authority action | read-only history reconstructs | preserve historical evidence; no continuing power |

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
