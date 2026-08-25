# Handoff: Delegate mission route complete

The Delegate mission lifecycle is implemented through terminal Step 69.

Steps 67–69 separately seal Curia's result disposition, Curia's exact return authorization, and Garrison's terminal physical transition. The final transition restores the Persona to Garrison custody, marks it available, unbinds the mission Seat, retires the temporary Manifestation, and prevents continuation or reuse.

Terminal checkpoint: `DELEGATE_MISSION_RETURNED_UNBOUND_CUSTODY_RESTORED_RETIRED_TERMINAL`

No provider, credential, cognition, tool, perimeter, external-action, execution, continuing-turn, redeployment, reuse, or supersession authority survives. The Delegate route is closed; any later mission requires a fresh lifecycle from a new capability demand.

Production verification is available through the read-only `imperium:delegate:audit-terminal <terminal-id>` command. It validates fourteen digest-bound operational and terminal records without replaying cognition, credentials, custody changes, or other side effects.
