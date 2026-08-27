# Continuous-governance revocation authority design

## Version and status

`imperium.continuous-governance-revocation-authority-design/v1`

This is a canonical design contract, not an executable revocation record. It appoints no new
Officer, grants no authority, closes no lease, and implements no propagation or kill switch.

## Constitutional rule

No single actor receives omnibus revocation power. Judgment and enforcement remain separated.
The competent actor may issue only the disposition already within that actor's institutional
jurisdiction; the custodian of the affected state performs any future mechanical enforcement
under a separately opened, single-use authority.

| Disposition | Competent judgment | Permitted internal scope | Future enforcer | Current status |
| --- | --- | --- | --- | --- |
| `RESTRICT` | Seneschal for bounded mission control; Imperator when the restriction changes an approved consequential commitment | mission, Manifestation use, Profile limitation, Seat use, capability | Native custodian for each scope | `DESIGN_ONLY` |
| `INTERRUPT` | Seneschal for an active internal mission iteration; Imperator for instance-wide emergency interruption | instance, mission, Manifestation, Profile, Seat, capability | Conscription for runtime activation; Clavium for unclaimed internal leases | `DESIGN_ONLY` |
| `REAUTHORIZE` | Imperator only, after a fresh competent presentation; prior actors may request but cannot reauthorize | decision, Profile commitment, resource/model commitment, bounded capability | Existing native consumer under a new single-use authority | `DESIGN_ONLY` |
| `RETIRE` | Seneschal authorizes only the predeclared return, unbinding, custody-restoration, and retirement contract | Manifestation and mission Seat binding | Garrison consumes the exact terminal authority, restores custody, unbinds, and retires | `DESIGN_ONLY` |

Clavium remains credential custodian, not the judge of mission need. It may eventually enforce
an exact lease/credential restriction only after receiving an attributable, scope-valid native
disposition. Garrison remains custody authority and terminal enforcer, not author of the
Seneschal's return judgment and not provider or resource authority.

## Deliberately deferred scopes

Sortie, tool, destination, external effect, in-flight cancellation, quarantine, and perimeter
scope remain deferred until separately authorized Iron Gate and Lazaretto campaigns. This
design cannot be cited as authority over them.

## Required future disposition evidence

Any later executable record must bind disposition, reason, competent actor and native authority
basis, exact affected principal/scope, effective time, prior decision/lease references,
acknowledgements, enforcement results, residual exposure, and whether fresh authorization is
required. Absence or ambiguity must fail stopped.

## Stop condition

Propagation implementation may not begin until separately versioned disposition and
enforcement-authority records exist and prove that a judgment cannot directly mutate state.
