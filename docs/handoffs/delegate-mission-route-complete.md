# Handoff: Delegate mission route complete

The Delegate mission lifecycle is implemented through terminal Step 69.

Steps 67–69 separately seal Curia's result disposition, Curia's exact return authorization, and Garrison's terminal physical transition. The final transition restores the Persona to Garrison custody, marks it available, unbinds the mission Seat, retires the temporary Manifestation, and prevents continuation or reuse.

Terminal checkpoint: `DELEGATE_MISSION_RETURNED_UNBOUND_CUSTODY_RESTORED_RETIRED_TERMINAL`

No provider, credential, cognition, tool, perimeter, external-action, execution, continuing-turn, redeployment, reuse, or supersession authority survives. The Delegate route is closed; any later mission requires a fresh lifecycle from a new capability demand.

Production verification is available through the read-only `imperium:delegate:audit-terminal <terminal-id>` command. It validates fourteen digest-bound operational and terminal records without replaying cognition, credentials, custody changes, or other side effects.

## Post-implementation Blackquill review

The route's governance model survives review, but the runtime implementation still requires integrity hardening before its recorded credential, invocation, persistence, and terminal guarantees can be treated as technically enforced under concurrency and failure.

The review is preserved as an ordered backlog in `todo/blackquill-todos.md`. Its critical findings are:

1. provider credentials remain reachable below a Clavium lease that is presently more attestational than mediating;
2. provider invocation occurs before a durable invocation claim/result boundary, allowing an unknown-outcome crash to risk replay;
3. filesystem state transitions lock writes but not the complete decision transaction;
4. terminal retirement spans records, custody, and binding without one recoverable atomic transition; and
5. the terminal audit validates fourteen operational records rather than claiming or proving all 69 transitions.

These are not additional Delegate mission steps. Step 69 remains terminal, and any fresh mission still begins from a new capability demand.

## Recommended next lifecycle

Begin the separately named **runtime-integrity hardening lifecycle**. Its first leg is credential mediation and durable provider-invocation claiming. Do not begin consolidation or cosmetic cleanup before this boundary is made real and proven under crash and concurrency tests.

See `docs/next-lifecycle-runtime-integrity-hardening.md` and `docs/handoffs/delegate-mission-runtime-integrity-review-complete.md`.
