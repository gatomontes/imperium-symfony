# Principal Activation Decision Authority Provenance Remediation — Batch 2 validation

## Result

BATCH_2_FAIL_CLOSED_VALIDATORS_AND_IMMUTABLE_FIXTURE_STORES_COMPLETE

Batch 2 adds pure validation and three segregated immutable evidence paths for
caller-supplied offline fixtures. It produces none of the records it accepts.

## Fail-closed validation

The scope-grant validator enforces the exact v1 field order and digest, Operator
Root identity shape, one-field scope delta, source-to-successor generation
continuity, unchanged identity reference, exact preservation of all five
existing scope values, single use, expiry, revocation, consumption state and
non-continuation.

The successor validator requires the exact grant reference, adjacent
generations, identical source and successor references, the authorized scope
delta and preserved scope, PENDING_ACTIVATION, a separate activation authority,
one transition winner, consumed grant evidence and non-mutation of the source.

The issuance-authorization validator requires the exact pending successor and
its effective activation disposition. The disposition must be ACTIVATE,
effective by validation time, preserve scope and attribution, and permit later
caller-authority issuance. The authorization is bound by immutable digest to the
exact principal attestation, provider-assurance admission, execution boundary,
decision identifier and activation-authority identifier. Expired, revoked,
consumed, continuing, changed-lineage or mismatched-instance fixtures refuse.

## Offline custody only

The immutable fixture store has separate paths for scope grants, scope
successors and issuance authorizations. Exact replay is idempotent and changed
content conflicts through the existing immutable-record primitive.

These paths are no live registry. They fetch nothing, identify no live Operator
Root or active principal, and do not establish current lifecycle truth. Fixture
acceptance does not grant scope, create or activate a successor, issue or
consume authority, produce a decision, or modify the existing combined
activation winner.

No credential or capability is handled. No provider is invoked, no external I/O
occurs, no retry is authorized, and Iron Gate and Lazaretto remain closed.
Provider Effect Principal and Binding Activation remains paused.
UNKNOWN_REPLAY_PROHIBITED remains binding.
