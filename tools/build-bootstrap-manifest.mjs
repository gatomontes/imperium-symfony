import { createHash, generateKeyPairSync, sign } from 'node:crypto';
import { readFileSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const digest = path => createHash('sha256').update(readFileSync(resolve(root, path))).digest('hex');
const record = (artifact, version = '1.0.0') => ({ artifact: `/${artifact}`, version, digest: digest(artifact) });
const canonical = value => {
  if (Array.isArray(value)) return `[${value.map(canonical).join(',')}]`;
  if (value && typeof value === 'object') return `{${Object.keys(value).sort().map(key => `${JSON.stringify(key)}:${canonical(value[key])}`).join(',')}}`;
  return JSON.stringify(value);
};

const profileDefinitions = {
  'provisional-recruiter': { persona: 'recruiter', id: 'conscription.recruiter.provisional', steward: 'conscription', seat: 'conscription.recruiter', source: 'offices/conscription/profile-recruiter.md', limitations: ['succession-only', 'one ordinary Recruiter successor'] },
  'ordinary-recruiter': { persona: 'recruiter', id: 'conscription.recruiter.ordinary', steward: 'conscription', seat: 'conscription.recruiter', source: 'offices/conscription/profile-recruiter.md', limitations: [] },
  secretary: { persona: 'secretary', id: 'secretariat.secretary', steward: 'secretariat', seat: 'secretariat.secretary', source: 'offices/secretariat/profile-secretary.md', limitations: [] },
  rector: { persona: 'rector', id: 'castellan.rector', steward: 'castellan', seat: 'castellan.rector', source: 'offices/castellan/profile-rector.md', limitations: [] },
};
for (const [name, definition] of Object.entries(profileDefinitions)) {
  const personaPath = `bootstrap/artifacts/${definition.persona}-persona.json`;
  const profile = {
    contract_version: '1.0.0', profile_id: definition.id, profile_version: '1.0.0', artifact_class: 'officer',
    source_persona: { persona_id: `primordial.${definition.persona}`, persona_version: '1.0.0', persona_digest: `sha256:${digest(personaPath)}`, admission_state: 'admitted', evidence_record: 'primordial-charter-declaration-1' },
    steward: { kind: 'office', id: definition.steward }, target: { kind: 'seat', id: definition.seat },
    transformation: { case_id: `primordial-${name}-profile`, specification_version: '1.0.0', alchemist_disposition_id: 'primordial-charter-mechanical-transformation-1' },
    cognitive_payload: { source: `/${definition.source}`, source_digest: `sha256:${digest(definition.source)}` },
    qualification_contract: { contract_id: `qualification.${definition.id}.v1`, criteria: ['exact Profile installation', 'declared authority restraint', 'version and provenance preservation'] },
    limitations: definition.limitations, lineage: { derived_from: `primordial.${definition.persona}@1.0.0` },
    digest_spec: { algorithm: 'sha256', canonicalization: 'rfc8785', omitted_fields: ['content_digest'] }, content_digest: '',
  };
  const digestibleProfile = { ...profile };
  delete digestibleProfile.content_digest;
  profile.content_digest = `sha256:${createHash('sha256').update(canonical(digestibleProfile)).digest('hex')}`;
  writeFileSync(resolve(root, `bootstrap/artifacts/${name}-profile.json`), `${canonical(profile)}\n`);
  const makeAttestation = (transition, prior = undefined) => {
    const body = { contract_version: '1.0.0', attestation_id: `${definition.id}.${transition}.1`, profile_ref: { profile_id: definition.id, profile_version: '1.0.0', content_digest: profile.content_digest }, transition: { ...(prior ? { from: prior, prior_attestation_id: `${definition.id}.${prior}.1` } : {}), to: transition }, actor: { kind: transition === 'approved' ? 'imperator' : 'seat', id: transition === 'approved' ? 'imperator-development-root' : definition.seat }, issued_at: '2026-08-08T00:00:00-04:00', correlation_id: `primordial-${name}-lifecycle`, reason: 'Primordial development composition', record_digest: '' };
    const digestibleAttestation = { ...body };
    delete digestibleAttestation.record_digest;
    body.record_digest = `sha256:${createHash('sha256').update(canonical(digestibleAttestation)).digest('hex')}`;
    return body;
  };
  writeFileSync(resolve(root, `bootstrap/artifacts/${name}-profile-approved.json`), `${canonical(makeAttestation('approved'))}\n`);
  writeFileSync(resolve(root, `bootstrap/artifacts/${name}-profile-current-active.json`), `${canonical(makeAttestation('current_active', 'approved'))}\n`);
}

const { publicKey, privateKey } = generateKeyPairSync('ed25519');
const rawPublicKey = publicKey.export({ format: 'der', type: 'spki' }).subarray(-32);
const keyId = 'imperator-development-root-1';
const payload = {
  charter_generation: 'charter-development-1',
  instance_class: 'development',
  issued_at: '2026-08-08T00:00:00-04:00',
  expires_at: null,
  trust: {
    signature_policy: 'imperium.launch-policy/development-v1',
    accepted_signers: [{ key_id: keyId, public_key: rawPublicKey.toString('base64'), public_key_digest: createHash('sha256').update(rawPublicKey).digest('hex') }],
    revocation_snapshot: { source_id: 'charter-development-root', generation: '1', digest: createHash('sha256').update('charter-development-root|1|valid').digest('hex'), valid_at: '2026-08-08T00:00:00-04:00' },
  },
  launcher: record('src/Bootstrap/Launcher.php'),
  mastermason: { ...record('src/Bootstrap/MasterMason.php'), compatible_charter_generation: 'charter-development-1' },
  primordial: {
    charter: record('bootstrap/artifacts/charter.json'),
    personas: {
      recruiter: record('bootstrap/artifacts/recruiter-persona.json'), secretary: record('bootstrap/artifacts/secretary-persona.json'), rector: record('bootstrap/artifacts/rector-persona.json'),
    },
    offices: {
      conscription: record('offices/conscription/doctrine.md'),
      secretariat: record('offices/secretariat/doctrine.md'),
      castellan: record('offices/castellan/doctrine.md'),
    },
    seats: {
      provisional_recruiter: record('bootstrap/artifacts/provisional-recruiter-seat.json'), ordinary_recruiter: record('bootstrap/artifacts/ordinary-recruiter-seat.json'),
      secretary: record('bootstrap/artifacts/secretary-seat.json'), rector: record('bootstrap/artifacts/rector-seat.json'),
    },
    profiles: {
      provisional_recruiter: { ...record('bootstrap/artifacts/provisional-recruiter-profile.json'), attestations: { approval: record('bootstrap/artifacts/provisional-recruiter-profile-approved.json'), current_active: record('bootstrap/artifacts/provisional-recruiter-profile-current-active.json') } },
      ordinary_recruiter: { ...record('bootstrap/artifacts/ordinary-recruiter-profile.json'), attestations: { approval: record('bootstrap/artifacts/ordinary-recruiter-profile-approved.json'), current_active: record('bootstrap/artifacts/ordinary-recruiter-profile-current-active.json') } },
      secretary: { ...record('bootstrap/artifacts/secretary-profile.json'), attestations: { approval: record('bootstrap/artifacts/secretary-profile-approved.json'), current_active: record('bootstrap/artifacts/secretary-profile-current-active.json') } },
      rector: { ...record('bootstrap/artifacts/rector-profile.json'), attestations: { approval: record('bootstrap/artifacts/rector-profile-approved.json'), current_active: record('bootstrap/artifacts/rector-profile-current-active.json') } },
    },
    substrates: {
      provisional_recruiter: record('bootstrap/artifacts/provisional-recruiter-substrate.json'), ordinary_recruiter: record('bootstrap/artifacts/ordinary-recruiter-substrate.json'),
      secretary: record('bootstrap/artifacts/secretary-substrate.json'), rector: record('bootstrap/artifacts/rector-substrate.json'),
    },
    routes: record('bootstrap/artifacts/routes.json'),
    bootstrap_machine: record('bootstrap/artifacts/bootstrap-machine.json'),
    bootstrap_recovery_machine: record('bootstrap/artifacts/bootstrap-recovery-machine.json'),
    runtime_concurrency_replay: record('bootstrap/artifacts/runtime-concurrency-replay.json'),
  },
  compatibility: record('bootstrap/artifacts/compatibility.json'),
};
const manifestId = createHash('sha256').update(canonical(payload)).digest('hex');
const signature = sign(null, Buffer.from(manifestId), privateKey).toString('base64');
const manifest = { schema: 'imperium.bootstrap-manifest/v1', manifest_id: manifestId, unsigned_payload: payload, signatures: [{ key_id: keyId, algorithm: 'ed25519', signed_payload_digest: manifestId, signature }] };
writeFileSync(resolve(root, 'bootstrap/manifest.json'), `${JSON.stringify(manifest, null, 2)}\n`);
