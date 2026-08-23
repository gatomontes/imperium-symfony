import { createHash, createPublicKey, verify } from 'node:crypto';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const canonical = value => {
  if (Array.isArray(value)) return `[${value.map(canonical).join(',')}]`;
  if (value && typeof value === 'object') return `{${Object.keys(value).sort().map(key => `${JSON.stringify(key)}:${canonical(value[key])}`).join(',')}}`;
  return JSON.stringify(value);
};
const sha = bytes => createHash('sha256').update(bytes).digest('hex');
const manifest = JSON.parse(readFileSync(resolve(root, 'bootstrap/manifest.json')));
const manifestId = sha(canonical(manifest.unsigned_payload));
if (manifestId !== manifest.manifest_id) throw new Error('Manifest identifier mismatch');
const signer = manifest.unsigned_payload.trust.accepted_signers[0];
const rawKey = Buffer.from(signer.public_key, 'base64');
if (sha(rawKey) !== signer.public_key_digest) throw new Error('Public-key digest mismatch');
const spkiPrefix = Buffer.from('302a300506032b6570032100', 'hex');
const publicKey = createPublicKey({ key: Buffer.concat([spkiPrefix, rawKey]), format: 'der', type: 'spki' });
if (!verify(null, Buffer.from(manifestId), publicKey, Buffer.from(manifest.signatures[0].signature, 'base64'))) throw new Error('Signature mismatch');

const visit = node => {
  if (!node || typeof node !== 'object') return;
  if (node.artifact && node.digest) {
    const contents = readFileSync(resolve(root, node.artifact.slice(1)));
    if (sha(contents) !== node.digest) throw new Error(`Artifact mismatch: ${node.artifact}`);
  }
  for (const value of Object.values(node)) visit(value);
};
visit(manifest.unsigned_payload);
console.log(`VALID ${manifestId}`);
