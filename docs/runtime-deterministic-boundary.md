# Deterministic Boundary Execution

This runtime path realizes fully specified outbound work without creating cognition outside Imperium.

```text
Imperium authorized request
  -> Iron Gate dispatch
  -> exact payload digest check
  -> exact credential capability check
  -> CredentialBroker consumes one bounded capability
  -> DeterministicTransport performs one external operation
  -> raw provider response
  -> Lazaretto admission
  -> provenance-bound artifact
```

The transport receives credential material only inside infrastructure execution. The `OutboundRequest`, `BoundaryDispatch`, admitted artifact, and provenance record contain capability identity but no credential secret.

`EnvironmentCredentialBroker` is the current development infrastructure implementation. It resolves an `env:` credential reference only at consumption time and consumes the capability before attempting the provider operation so a failed external attempt cannot replay a one-use credential capability.

`BearerJsonPostTransport` is the first concrete transport. It performs exactly one HTTPS POST with an `Authorization: Bearer` header and a JSON body for the `http.post.json` operation. Provider-specific transports can implement `DeterministicTransport` without changing Iron Gate, credential custody, or Lazaretto.
