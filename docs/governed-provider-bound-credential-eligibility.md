# Governed provider-bound credential eligibility

## Status

`BATCH_5_PROVIDER_CREDENTIAL_COMPATIBILITY_ENFORCED_INACTIVE`

Clavium now assesses an opaque credential capability against one intact provider binding before any
credential resolution. Provider identity, credential family, exact credential-reference syntax,
authorization target, operation, expiry and single-use scope must all agree.

The resulting immutable eligibility record persists only a credential-reference digest. It remains
`ELIGIBLE_INACTIVE`, records `credential_resolved: false` and grants no external I/O. Provider,
family, reference, target or operation substitution fails before a credential broker can be called.

The existing credential brokers and live AgentMail corridor are unchanged. Runtime behavior is unchanged.
