import { createHash } from 'node:crypto';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const check = process.argv.includes('--check');
const canonical = value => JSON.stringify(sort(value));
const sort = value => Array.isArray(value) ? value.map(sort) : value && typeof value === 'object' ? Object.fromEntries(Object.keys(value).sort().map(k => [k, sort(value[k])])) : value;
const digest = value => `sha256:${createHash('sha256').update(typeof value === 'string' ? value : canonical(value)).digest('hex')}`;
const seal = (value, field) => ({ ...value, [field]: digest(value) });
const fileDigest = async path => digest(await readFile(resolve(root, path), 'utf8'));
const emit = async (path, value) => { const content = `${canonical(value)}\n`; if (check) { if (await readFile(resolve(root, path), 'utf8') !== content) throw new Error(`STALE_AUTHORSHIP_ARTIFACT ${path}`); } else await writeFile(resolve(root, path), content); };

for (const specification of [
  { office: 'hagiography', role: 'sanctographer', seat: 'hagiography.sanctographer', personaSource: 'offices/hagiography/seat-resident-sanctographer.md', profileSource: 'offices/hagiography/profile-sanctographer.md' },
  { office: 'studium', role: 'chancellor', seat: 'studium.chancellor', personaSource: 'offices/studium/seat-resident-chancellor.md', profileSource: 'offices/studium/profile-chancellor.md' },
]) {
  const { office, role, seat, personaSource, profileSource } = specification;
  const dir = `offices/${office}/canonical-staff`; await mkdir(resolve(root, dir), { recursive: true });
  const persona = { schema: 'imperium.persona/v1', persona_id: `${office}.canonical.${role}`, persona_version: '1.0.0', source: { path: personaSource, content_digest: await fileDigest(personaSource) }, admission: { state: 'admitted', authority: 'garrison.constable', evidence_record: `${office}-canonical-${role}-admission-1` } };
  await emit(`${dir}/${role}.persona.json`, persona);
  const profile = seal({ contract_version: '1.0.0', profile_id: seat, profile_version: '1.0.0', artifact_class: 'officer', source_persona: { persona_id: persona.persona_id, persona_version: persona.persona_version, persona_digest: await fileDigest(`${dir}/${role}.persona.json`), admission_state: 'admitted', evidence_record: persona.admission.evidence_record }, steward: { kind: 'office', id: office }, target: { kind: 'seat', id: seat }, cognitive_payload: { profile_source: { path: profileSource, content_digest: await fileDigest(profileSource) } }, qualification_contract: { contract_id: `qualification.${seat}.v1`, criteria: ['exact canonical Persona and Profile installation', `${office} jurisdiction restraint`, 'version and provenance preservation'] }, limitations: [`canonical resident ${role} only`, 'no authority without valid Seat occupancy'], digest_spec: { algorithm: 'sha256', canonicalization: 'rfc8785', omitted_fields: ['content_digest'] } }, 'content_digest');
  await emit(`${dir}/${role}.profile.json`, profile);
  const ref = { profile_id: profile.profile_id, profile_version: profile.profile_version, content_digest: profile.content_digest };
  const approval = seal({ contract_version: '1.0.0', attestation_id: `${seat}.approved.1`, profile_ref: ref, transition: { to: 'approved' }, actor: { kind: 'imperator', id: 'imperator-development-root' }, issued_at: '2026-08-13T00:00:00-04:00', correlation_id: `${office}-canonical-${role}-lifecycle`, reason: `Canonical ${role} Profile approved for development runtime` }, 'record_digest');
  await emit(`${dir}/${role}.profile-approved.json`, approval);
  const current = seal({ contract_version: '1.0.0', attestation_id: `${seat}.current_active.1`, profile_ref: ref, transition: { from: 'approved', to: 'current_active', prior_attestation_id: approval.attestation_id }, actor: { kind: 'office_mechanic', id: `${office}.profile-registry` }, issued_at: '2026-08-13T00:00:00-04:00', correlation_id: `${office}-canonical-${role}-lifecycle`, reason: `Canonical approved ${role} Profile designated current and active` }, 'record_digest');
  await emit(`${dir}/${role}.profile-current-active.json`, current);
  const refs = {}; for (const name of ['persona', 'profile', 'profile-approved', 'profile-current-active']) refs[name] = { path: `${dir}/${role}.${name}.json`, content_digest: await fileDigest(`${dir}/${role}.${name}.json`) };
  await emit(`${dir}/package.json`, seal({ schema: 'imperium.authorship-canonical-staff-package/v1', package_id: `${office}.canonical-staff`, package_version: '1.0.0', steward: office, seat, role, artifacts: refs, limitations: ['not a manifestation', 'not an acceptance', 'grants no spawning, Seat, or execution authority'] }, 'record_digest'));
}
