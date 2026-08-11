# Bootstrap composition

`artifacts/manifest.json` is generated from the immutable development composition by `tools/build-bootstrap-manifest.mjs` and verified by `tools/verify-bootstrap-manifest.mjs`.

The composition mechanically establishes Conscription, replaces the provisional Recruiter with an ordinary Recruiter, assembles the Seneschal–Chamberlain governing pair, creates one Curia runtime, binds that pair atomically, attaches Isolde independently as provisional Curial Secretary, verifies closed Curian routes, and finally commits `CURIA_READY` with the Imperator entrypoint.

Do not edit generated Profiles, attestations, or the manifest directly. Change their source definitions or the generator and regenerate the set.
