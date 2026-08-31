# Next campaign: Provider Binding Successor Production Realization

## Selection

Provider Binding Successor Production Realization is separately selected after
the offline production-adoption readiness campaign closed at
`PROVIDER_BINDING_SUCCESSOR_PRODUCTION_ADOPTION_CAMPAIGN_COMPLETE_PRE_PRODUCTION_ONLY`.

Begin Provider Binding Successor Production Realization Preparation Batch 0 only.

Selection does not issue a production decision, create or custody authority,
create a successor, implement v3 execution admission, activate a binding or
perform live adoption.

## Preparation Batch 0 inventory

Preparation Batch 0 must inventory and classify:

- the exact competent production-decision owner and executor principal;
- the production decision issuer and immutable decision lineage;
- single-use successor-creation authority issuance and custody;
- authority consumption and immutable successor creation as one atomic winner;
- the v3 execution-admission seam and explicit adoption target;
- crash recovery, replay, contention, expiry, revocation and reconstruction;
- process-local capability identity and durable authority separation;
- credential, secret and provider-effect exclusion;
- threat-model assumptions, candidate boundary postures and non-authorities.

Each finding must be classified as `EXISTS_CANONICALLY`,
`EXISTS_FRAGMENTED`, `ABSENT` or `DEFERRED_BOUNDARY`.

## Closed perimeter

Preparation Batch 0 may not define a runtime contract or change runtime
behavior. It may not produce a decision, issue or consume authority, create a
successor, implement v3 admission or adopt the successor.

It may not activate a principal or provider binding.
It may not handle or resolve a credential or capability.
It may not invoke a provider.
It may not perform external I/O.
It may not migrate a live command.
It may not open Iron Gate or Lazaretto.

The provider binding remains BOUND_INACTIVE.
The required v3 execution admission remains NOT_IMPLEMENTED.
UNKNOWN_REPLAY_PROHIBITED remains binding.
