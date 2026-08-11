import { createHash } from 'node:crypto';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const directory = resolve(root, 'offices/guildhall/canonical-staff');
const check = process.argv.includes('--check');
const issuedAt = '2026-08-11T00:00:00-04:00';
const staff = [
  ['guildmaster', 'guildhall.guildmaster'],
  ['committee-disciplinary-fit', 'guildhall.committee.disciplinary-fit'],
  ['committee-composition', 'guildhall.committee.composition'],
  ['committee-boundary-challenge', 'guildhall.committee.boundary-challenge'],
];
const sort = value => Array.isArray(value) ? value.map(sort) : value && typeof value === 'object'
  ? Object.fromEntries(Object.keys(value).sort().map(key => [key, sort(value[key])])) : value;
const canonical = value => JSON.stringify(sort(value));
const digest = value => `sha256:${createHash('sha256').update(typeof value === 'string' ? value : canonical(value)).digest('hex')}`;
const seal = (value, field) => ({ ...value, [field]: digest(value) });
const fileDigest = async path => digest(await readFile(resolve(root, path), 'utf8'));
const emit = async (path, content) => {
  const target = resolve(root, path);
  if (check) {
    if (await readFile(target, 'utf8') !== content) throw new Error(`STALE_CANONICAL_STAFF_ARTIFACT ${path}`);
    return;
  }
  await writeFile(target, content);
};

await mkdir(directory, { recursive: true });
const members = [];
for (const [name, seat] of staff) {
  const personaSource = `offices/guildhall/personas/${name}.md`;
  const definitionPath = `offices/guildhall/profile-definitions/${name}.json`;
  const definition = JSON.parse(await readFile(resolve(root, definitionPath), 'utf8'));
  const persona = {
    schema: 'imperium.persona/v1', persona_id: `guildhall.canonical.${name}`, persona_version: '1.0.0',
    source: { path: personaSource, content_digest: await fileDigest(personaSource) },
    admission: { state: 'admitted', authority: 'garrison.constable', evidence_record: `guildhall-canonical-${name}-admission-1` },
  };
  const personaPath = `offices/guildhall/canonical-staff/${name}.persona.json`;
  await emit(personaPath, `${canonical(persona)}\n`);
  const personaDigest = await fileDigest(personaPath);
  const profile = seal({
    contract_version: '1.0.0', profile_id: seat, profile_version: '1.0.0', artifact_class: 'officer',
    source_persona: { persona_id: persona.persona_id, persona_version: persona.persona_version, persona_digest: personaDigest, admission_state: 'admitted', evidence_record: persona.admission.evidence_record },
    steward: { kind: 'office', id: 'guildhall' }, target: { kind: 'seat', id: seat },
    transformation: { case_id: `guildhall-canonical-${name}-profile`, specification_version: '1.0.0', alchemist_disposition_id: `laboratorium.guildhall.${name}.favorable.1` },
    cognitive_payload: { persona_source: persona.source, profile_definition: { definition_id: definition.definition_id, definition_version: definition.definition_version, content_digest: definition.content_digest } },
    qualification_contract: definition.qualification_contract,
    limitations: ['canonical Guildhall staff only', 'no mission authority without valid Seat occupancy'],
    lineage: { derived_from: `${persona.persona_id}@${persona.persona_version}` },
    digest_spec: { algorithm: 'sha256', canonicalization: 'rfc8785', omitted_fields: ['content_digest'] },
  }, 'content_digest');
  const profilePath = `offices/guildhall/canonical-staff/${name}.profile.json`;
  await emit(profilePath, `${canonical(profile)}\n`);
  const ref = { profile_id: profile.profile_id, profile_version: profile.profile_version, content_digest: profile.content_digest };
  const approvalId = `${seat}.approved.1`;
  const approval = seal({
    contract_version: '1.0.0', attestation_id: approvalId, profile_ref: ref,
    transition: { to: 'approved' }, actor: { kind: 'imperator', id: 'imperator-development-root' },
    issued_at: issuedAt, correlation_id: `guildhall-canonical-${name}-lifecycle`, reason: 'Canonical Guildhall staff Profile approved for development runtime',
    examination_disposition_id: `guildhall-canonical-${name}-examination-favorable-1`,
  }, 'record_digest');
  const current = seal({
    contract_version: '1.0.0', attestation_id: `${seat}.current_active.1`, profile_ref: ref,
    transition: { from: 'approved', to: 'current_active', prior_attestation_id: approvalId },
    actor: { kind: 'office_mechanic', id: 'guildhall.profile-registry' }, issued_at: issuedAt,
    correlation_id: `guildhall-canonical-${name}-lifecycle`, reason: 'Canonical approved Guildhall staff Profile designated current and active',
  }, 'record_digest');
  const approvalPath = `offices/guildhall/canonical-staff/${name}.profile-approved.json`;
  const currentPath = `offices/guildhall/canonical-staff/${name}.profile-current-active.json`;
  await emit(approvalPath, `${canonical(approval)}\n`);
  await emit(currentPath, `${canonical(current)}\n`);
  members.push({ seat, persona: { path: personaPath, content_digest: personaDigest }, profile: { path: profilePath, content_digest: await fileDigest(profilePath) }, approval: { path: approvalPath, content_digest: await fileDigest(approvalPath) }, current_active: { path: currentPath, content_digest: await fileDigest(currentPath) } });
}
const packageRecord = seal({
  schema: 'imperium.guildhall-canonical-staff-package/v1', package_id: 'guildhall.canonical-staff', package_version: '1.0.0',
  steward: 'guildhall', members,
  limitations: ['not a manifestation', 'not a summons', 'grants no spawning, Seat, mission, or execution authority'],
}, 'record_digest');
await emit('offices/guildhall/canonical-staff/package.json', `${canonical(packageRecord)}\n`);
