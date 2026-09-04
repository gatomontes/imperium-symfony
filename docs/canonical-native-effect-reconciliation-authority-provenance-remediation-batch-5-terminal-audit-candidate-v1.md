# Canonical native-effect reconciliation authority provenance remediation — Batch 5 terminal audit candidate

Status: `BATCH_5_TERMINAL_AUDIT_CANDIDATE_LOCAL_PROOF_PASSED_CI_PENDING`

The separately sequenced audit began from clean merged Batch 4 `main` at
`98ba984c7cb808bd2195b5637f61c079bd47a22f`. No Batch 5 runtime correction was
needed. The full suite exposed three frozen-inventory omissions caused by the new
consumption contract and exact claim consumer; the governed inventory was updated
and its focused tripwire suite passed.

## Independent reconstruction

The admitted chain is now:

1. canonical effect admission identifies its committed native root;
2. the commit identifies its already-consumed native transition authority;
3. `NativeAuthority::load()` and `NativePrincipal::load()` revalidate current
   Imperator competence and the signed Operator Root act;
4. the deterministic reconciliation issuer writes a v2 authority and separate
   immutable issuance evidence;
5. the resolver replays that source chain and delivers only exact, process-bound,
   non-serializable and non-cloneable custody;
6. one capability ID wins the immutable authority-to-claim publication;
7. the exact claim digest is consumed for one deterministic receipt;
8. receipt replay converges and receipt-to-Root reconstruction is read-only.

The old counterexample is not merely discouraged. The public admission parameter
is a `NativeEffectReconciliationAuthorityCapability`; an arbitrary array fails at
the language boundary. Public labels and digests cannot mint that exact object,
and direct durable records are re-resolved against separate issuance and Rooted
source evidence.

## Blackquill findings

- Claim: a valid digest authenticates issuance. Verdict: false; it authenticates
  bytes only. Separate issuance and resolved source competence now do the work.
- Claim: a record in a trusted directory is trusted ingress. Verdict: false;
  resolver reconstruction is mandatory.
- Claim: repeated receipt return means reusable authorization. Verdict: false;
  the claim has one durable source/consumer binding and reconstruction is read-only.
- Claim: green local tests prove GitHub CI. Verdict: nonsense. Current CI evidence
  is pending until the exact merged candidate SHA runs successfully.

## Local evidence

- campaign-focused: `99 tests, 856 assertions`, passed;
- first full run: `2474 tests, 48834 assertions`, three mechanical frozen-inventory
  failures and no behavioral failure;
- corrected tripwire focus: `10 tests, 3884 assertions`, passed;
- corrected full run: `2474 tests, 51016 assertions`, passed in `05:50.805`.

No provider, credential, mission, email, Iron Gate, Lazaretto or live effect was
invoked. Batch 7 remains suspended. Terminal closure is withheld until exact-SHA
GitHub CI evidence exists.

