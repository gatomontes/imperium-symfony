# Handoff: operational-adoption disposition Step 10 complete

The exact current sole Seneschal consumes the Step 9 single-use authority and seals one `ADOPTED`, `ADOPTED_WITH_LIMITATIONS`, `NOT_ADOPTED`, or `UNRESOLVED` disposition with a required rationale. Conditional adoption requires explicit limitations.

All branches terminally close this operational-adoption lifecycle. Exact replay is idempotent; conflicting disposition, rationale, or limitations fail closed.

`ADOPTED` and `ADOPTED_WITH_LIMITATIONS` mean that Curia institutionally accepts the exact result under the sealed evaluation and reconciliation record. They do not amend a Mission Plan, authorize operational use, create a follow-up commission, activate tools or credentials, authorize external action, or permit execution.

The terminal adopted checkpoints end in `LIFECYCLE_CLOSED_NO_ACTION_AUTHORITY`; non-adopted and unresolved checkpoints end in `LIFECYCLE_CLOSED_NO_AUTHORITY`.

Any plan amendment, doctrine amendment, operational use, new commission, or action must begin a separately named downstream lifecycle with its own exact authority chain.
