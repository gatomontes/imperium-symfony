# Runtime integrity hardening Step 25 complete

Step 25 establishes the canonical record-reference validation primitive and begins bounded migration onto it.

## Delivered

- `RecordReferenceValidator` rejects paths outside `var/imperium` and traversal attempts;
- record reads retain caller-supplied absence errors;
- canonical digest verification is implemented once;
- callers may require intact records using their own chain/tamper error vocabulary;
- exact references validate both a bounded record identity and its digest; and
- the shared Steps 51–52 and Steps 67–68 mechanics now delegate reads, integrity checks, and source resolution to the primitive.

## Boundary retained

The validator proves storage location, record integrity, identity, and digest equality only. It does not decide whether evidence is sufficient, consume authority, infer lifecycle state, grant replay, or collapse Office-specific validation into generic persistence rules.

## Next

Migrate additional bounded service groups onto `RecordReferenceValidator`, starting where duplicate read/digest/source helpers are identical and covered by focused chain-error tests. Do not perform a system-wide mechanical rewrite without preserving each service's established failure vocabulary.
