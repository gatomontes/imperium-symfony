# Atomic Transition Reproof Batch 1: v2 contracts

`REPROOF_BATCH_1_AUTHORITY_EMPTY_CONTRACTS_COMPLETE`

The operator's continuation instruction authorizes implementation and synthetic
PHPUnit validation. This does not select private evidence or signing material.
The exact-source execution gate and separate verification/signing gate remain.

`src/ReproofV2/Contract.php` defines eleven distinct v2 schemas with exact field
sets. Its constants describe data; no constructor or method grants authority.
All eight ordered case IDs and all eight independently derived domains are
mandatory. Additional, omitted, reordered and duplicated cases are invalid.

Each input retains full primary/comparison snapshots, explicit null comparison,
cut, mutation path and replacement (including null), full read-only plan and
synthetic auxiliary reference payloads. Expectations precede execution. Each
observation binds exact input, expectation and executor implementation digests
and retains classification, directive, comparison, safe validator code and findings.
Ordered lists of these digests define the three matrix roots.

Canonical records use UTF-8 JSON, sorted object keys, preserved list order,
unescaped slash/Unicode, no floating point values and SHA-256. `record_digest`
is omitted from its own digest. Raw source bytes are base64 transported and
hashed as bytes, never normalized. Git SHA-1 commit/tree/blob framing and tree
membership bind the selected artifacts to the pinned commit. The manifest
root commits to the sorted path/blob/SHA-256 list. This is a source build
identity, not evidence of an installed vendor tree. The runner uses a finite
explicit local loader; no Composer bootstrap or environment files are needed.

Seal order: inputs/expectations/source -> origin -> observations -> matrix ->
receipt -> candidate -> independent report -> detached attestation -> admission
-> terminal audit. The origin binds an externally approved execution record's
digest, never a label hash presented as authorization. Later verification pins
that digest, source, proof identity, runtime and verifier independently of the receipt.

Private: input/expectation/observation bytes, receipt, source snapshot, graph,
exclusion scope and local custody/paths. Public: only strict sanitized candidate,
report, purpose-bound public identity and detached attestation. Forbidden in
either: live secret/capability material, environment dumps, key seeds, private
keys, encoded/split substitutes and unfiltered exceptions. Signing custody is
separate from evidence custody. Contract descriptions are not evidence admission.

Publication reserves a unique directory once, writes receipt then candidate,
and publishes a final digest manifest last. A preexisting reservation always
refuses execution, even if empty. Missing/truncated/mismatched finalization is
unadmitted; no automatic retry, overwrite or reconstruction of execution.
Synthetic crash tests use disposable test directories. The eight semantic
cuts are read-only snapshots, not evidence of runtime durable transactions or
concurrent locks. Unknown replay remains prohibited.

Signing uses Ed25519 over the purpose plus NUL plus exact sanitized report digest.
Unsigned/null signature shapes are descriptions only. PASS alone is not admission;
the signer and later admission must validate trusted identity, purpose, validity,
verifier implementation, source/receipt/case roots and report binding. None of
those operations occurs in Batch 1.

`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`
remains controlling. V1 remains refused and unchanged. Provider binding remains
`BOUND_INACTIVE`; v3 admission remains `NOT_IMPLEMENTED`.
