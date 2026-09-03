# Native Integration Remediation Batch 6A reader correction

`NATIVE_INTEGRATION_BATCH_6A_ORPHAN_READER_CORRECTED`.

The first terminal candidate passed 1942 tests / 44998 assertions, but further
reader review found a missing negative: legacy retirement without the native
journal was UNKNOWN_REPLAY_PROHIBITED to NativeReconstructor while the binding
reader returned BOUND_INACTIVE. No active result or retry was granted, but the
reader failed to report the damaged attempt state. Terminal acceptance was held.

The new regression first failed on the merged Batch 6 tree. NativeBindingReader
now requires independent ABSENT reconstruction before reporting an untouched
inactive descriptor. Orphaned retirement or unstable evidence therefore refuses
consistently. This correction adds no publication, live action or new authority.

The terminal draft was preserved separately; it will resume only after this
correction is tested and merged into clean local main. Full correction validation
is recorded below after the run. This is one additional correction batch beyond
the eight planned stages, not evidence that the first green terminal candidate
already deserved acceptance. Final full PHPUnit: **1939 tests, 44961 assertions
passed**. Focused correction set: 12 tests, 49 assertions. PHP lint and diff
whitespace checks pass. The regression was observed failing before the fix.

Reading ledger: NativeBindingReader::read absent branch, NativeReconstructor's
absent/orphanRetirement paths, NativeTransitionBatch4Test fixture harness and
NativeTransitionBatch6Test missing-retirement negative were followed. New
NativeTransitionBatch6ACorrectionTest was read in full. The previous preparation
and implementation reading ledgers remain applicable. No live state was read.

BOUND_INACTIVE, historical NOT_IMPLEMENTED and UNKNOWN_REPLAY_PROHIBITED remain.
Live provisioning, provider/credential/capability handling, external effects,
retry, Iron Gate and Lazaretto remain closed.
