# Runtime Integrity Hardening Step 34 Complete

The Delegate provider registry now says exactly what it is: DeepSeek-specific.

## Contract

- The generic-looking `DelegateSymfonyPlatformAdapter` boundary is retired.
- `DeepSeekDelegatePlatformAdapter` canonically names the provider, platform service, runtime model, credential reference, and broker operation.
- The service registry aliases only that explicit contract to `DeepSeekSymfonyPlatformAdapter`.
- The brokered invoker derives its claim-scope and credential-operation checks from the same constants.
- The concrete adapter revalidates model configuration immediately before constructing the Symfony AI platform.

## Neutrality rule

Imperium does not claim provider neutrality. A second provider must first supply its own strict adapter and configuration contract, then pass equivalent contract tests. Only proven shared behavior may later be extracted.

## Verification

Focused contract coverage proves the exact DeepSeek registry identity, implementation relationship, and shared model/configuration contract. The full Delegate flow remains the local PHP 8.4 gate.
