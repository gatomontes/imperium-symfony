# Executable bootstrap corpus

This directory contains the immutable development composition consumed by the
CLI-first Launcher. `manifest.json` pins the exact artifact bytes and is signed
under the development Charter root. It authorizes the complete T01-T09 primordial
bootstrap sequence; only the terminal T09 state means `READY`.

Regenerate the manifest only after an intentional artifact change:

```bash
node tools/build-bootstrap-manifest.mjs
```

The generator creates a new one-use signing root and discards its private key.
It is a development composition mechanism, not the eventual production key
ceremony.
