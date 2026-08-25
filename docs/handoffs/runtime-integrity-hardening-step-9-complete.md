# Handoff: runtime-integrity hardening Step 9 complete

## Completed transition

Hardening Step 9 implements separately authorized forward recovery from a sealed provider-response envelope to the missing bounded cognition turn.

## Enforced invariants

- recovery requires one exact immutable authorization bound to both claim and envelope digests;
- recovery authority is unexpired, single-use, and consumed through `AuthorityConsumptionStore`;
- claim, journal, envelope, activation, and commission lineage is revalidated at recovery time;
- the response identity and exact six-field cognition payload are revalidated;
- an in-flight journal with a sealed envelope advances forward to response-identity sealed;
- the deterministic turn is written through `ImmutableRecordStore`;
- exact replay returns the existing turn without consuming a second authority; and
- the recovery service has no provider or credential dependency and records `provider_reinvoked: false`.

## Verification

Focused tests cover successful recovery, exact replay, one authority-consumption record, malformed payload refusal before consumption, expired authorization refusal, one immutable turn, and absence of provider authority in the recovered turn.

PHP is unavailable in the Codex environment. Operator-local PHPUnit confirmation remains required.

## Next bounded transition

Hardening Step 10 should begin recoverable terminal retirement: terminal record creation, custody retirement, and occupancy replacement must become one forward-resumable multi-store transition with fault injection after every durable write.
