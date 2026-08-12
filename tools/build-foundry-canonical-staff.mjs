import { createHash } from 'node:crypto';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const dir = resolve(root, 'offices/foundry/canonical-staff');
const check = process.argv.includes('--check');
const canonical = value => JSON.stringify(sort(value));
const sort = value => Array.isArray(value) ? value.map(sort) : value && typeof value === 'object' ? Object.fromEntries(Object.keys(value).sort().map(k => [k, sort(value[k])])) : value;
const digest = value => `sha256:${createHash('sha256').update(typeof value === 'string' ? value : canonical(value)).digest('hex')}`;
const seal = (value, field) => ({ ...value, [field]: digest(value) });
const fileDigest = async path => digest(await readFile(resolve(root, path), 'utf8'));
const emit = async (path, value) => { const content = `${canonical(value)}\n`; if (check) { if (await readFile(resolve(root, path), 'utf8') !== content) throw new Error(`STALE_FOUNDRY_ARTIFACT ${path}`); } else await writeFile(resolve(root, path), content); };

await mkdir(dir, { recursive: true });
const personaSource = 'offices/foundry/seat-resident-artificer.md';
const profileSource = 'offices/foundry/profile-artificer.md';
const persona = { schema: 'imperium.persona/v1', persona_id: 'foundry.canonical.artificer', persona_version: '1.0.0', source: { path: personaSource, content_digest: await fileDigest(personaSource) }, admission: { state: 'admitted', authority: 'garrison.constable', evidence_record: 'foundry-canonical-artificer-admission-1' } };
await emit('offices/foundry/canonical-staff/artificer.persona.json', persona);
const profile = seal({ contract_version: '1.0.0', profile_id: 'foundry.artificer', profile_version: '1.0.0', artifact_class: 'officer', source_persona: { persona_id: persona.persona_id, persona_version: persona.persona_version, persona_digest: await fileDigest('offices/foundry/canonical-staff/artificer.persona.json'), admission_state: 'admitted', evidence_record: persona.admission.evidence_record }, steward: { kind: 'office', id: 'foundry' }, target: { kind: 'seat', id: 'foundry.artificer' }, cognitive_payload: { profile_source: { path: profileSource, content_digest: await fileDigest(profileSource) } }, qualification_contract: { contract_id: 'qualification.foundry.artificer.v1', criteria: ['exact canonical Persona and Profile installation', 'Foundry jurisdiction restraint', 'version and provenance preservation'] }, limitations: ['canonical resident Artificer only', 'no authority without valid Seat occupancy'], digest_spec: { algorithm: 'sha256', canonicalization: 'rfc8785', omitted_fields: ['content_digest'] } }, 'content_digest');
await emit('offices/foundry/canonical-staff/artificer.profile.json', profile);
const ref = { profile_id: profile.profile_id, profile_version: profile.profile_version, content_digest: profile.content_digest };
const approval = seal({ contract_version: '1.0.0', attestation_id: 'foundry.artificer.approved.1', profile_ref: ref, transition: { to: 'approved' }, actor: { kind: 'imperator', id: 'imperator-development-root' }, issued_at: '2026-08-12T00:00:00-04:00', correlation_id: 'foundry-canonical-artificer-lifecycle', reason: 'Canonical Artificer Profile approved for development runtime' }, 'record_digest');
await emit('offices/foundry/canonical-staff/artificer.profile-approved.json', approval);
const current = seal({ contract_version: '1.0.0', attestation_id: 'foundry.artificer.current_active.1', profile_ref: ref, transition: { from: 'approved', to: 'current_active', prior_attestation_id: approval.attestation_id }, actor: { kind: 'office_mechanic', id: 'foundry.profile-registry' }, issued_at: '2026-08-12T00:00:00-04:00', correlation_id: 'foundry-canonical-artificer-lifecycle', reason: 'Canonical approved Artificer Profile designated current and active' }, 'record_digest');
await emit('offices/foundry/canonical-staff/artificer.profile-current-active.json', current);
const refs = {};
for (const name of ['persona', 'profile', 'profile-approved', 'profile-current-active']) refs[name] = { path: `offices/foundry/canonical-staff/artificer.${name}.json`, content_digest: await fileDigest(`offices/foundry/canonical-staff/artificer.${name}.json`) };
await emit('offices/foundry/canonical-staff/package.json', seal({ schema: 'imperium.foundry-canonical-staff-package/v1', package_id: 'foundry.canonical-staff', package_version: '1.0.0', steward: 'foundry', seat: 'foundry.artificer', artifacts: refs, limitations: ['not a manifestation', 'not an acceptance', 'grants no spawning, Seat, or execution authority'] }, 'record_digest'));
