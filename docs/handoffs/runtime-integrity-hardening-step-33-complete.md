# Runtime Integrity Hardening Step 33 Complete

Delegate provider configuration is no longer an arbitrary pass-through array.

## DeepSeek contract

- The supported runtime model is exactly `deepseek-v4-flash`.
- The only supported configuration key is `temperature`.
- Temperature must be numeric and within `0.0`–`2.0` inclusive.
- Omission normalizes to `0.2`; integers normalize to floats.
- Unknown keys, unsupported models, wrong container types, wrong value types, and out-of-range values fail as `CT312_DELEGATE_MODEL_CONFIGURATION_INVALID`.

## Enforcement

The mission cognition gateway validates before calling the brokered invoker. The brokered invoker validates again before credential resolution, so direct internal invocation cannot bypass the configuration contract or cause provider I/O.

This remains an intentionally honest DeepSeek-specific adapter boundary. No provider-neutral claim is made.

## Verification

Focused tests cover normalization, every rejection family, gateway short-circuiting, and direct-invoker short-circuiting before credential access. The full Delegate flow remains the local/CI PHP 8.4 behavioral gate.
