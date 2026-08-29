# Governed Tool and Provider Separation Batch 6 complete

## Result

Batch 6 adds the inactive provider-neutral evidence route: preserve exact raw bytes, invoke only the
decoder named by the exact provider binding, produce a normalized tool result, and admit only that
normalized result without reinterpreting provider content inside Lazaretto.

No provider was invoked. No credential was resolved. No external I/O occurred. Existing result,
Lazaretto, reconstruction and command consumers were not migrated. Runtime behavior is unchanged.

## Authorized continuation

Only Batch 7 may next be considered: migrate read-only reconstruction to preserve tool authority,
provider binding, credential eligibility/attempt, raw evidence, decoder identity and normalized result.

Batch 7 may not invoke a provider, resolve credentials, perform external I/O or change the live
command. Batch 7 is not authorized by completion alone.
