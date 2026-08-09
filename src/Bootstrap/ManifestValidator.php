<?php

declare(strict_types=1);

namespace App\Bootstrap;

final readonly class ManifestValidator
{
    public function __construct(private string $projectDir)
    {
    }

    public function validate(?string $manifestPath = null): ValidationReceipt
    {
        $path = $manifestPath ?? $this->projectDir.'/bootstrap/manifest.json';
        $manifest = $this->decode($this->read($path), 'manifest');
        $this->requireExactKeys($manifest, ['schema', 'manifest_id', 'unsigned_payload', 'signatures'], 'manifest');

        if (($manifest['schema'] ?? null) !== 'imperium.bootstrap-manifest/v1') {
            throw new ValidationException('Unsupported bootstrap Manifest schema.');
        }

        $payload = $manifest['unsigned_payload'] ?? null;
        if (!is_array($payload)) {
            throw new ValidationException('Manifest unsigned_payload must be an object.');
        }

        $manifestId = hash('sha256', CanonicalJson::encode($payload));
        if (!hash_equals($manifestId, (string) ($manifest['manifest_id'] ?? ''))) {
            throw new ValidationException('Manifest identifier does not match its canonical unsigned payload.');
        }

        $this->validateTime($payload);
        $this->validateSignature($manifest, $manifestId);

        $artifacts = $this->flattenArtifacts($payload);
        $observed = [];
        $successionCommission = null;
        $secretaryCommission = null;
        $rectorCommission = null;
        foreach ($artifacts as $identity => $record) {
            $this->validateArtifactRecord($identity, $record);
            $absolutePath = $this->resolve((string) $record['artifact']);
            $digest = hash_file('sha256', $absolutePath);
            if (!hash_equals((string) $record['digest'], $digest)) {
                throw new ValidationException(sprintf('Artifact digest mismatch: %s.', $identity));
            }
            $observed[$identity] = $digest;
            if ('primordial.succession_commission' === $identity) {
                $successionCommission = $this->decode($this->read($absolutePath), 'succession commission');
            }
            if ('primordial.assembly_commissions.secretary' === $identity) {
                $secretaryCommission = $this->decode($this->read($absolutePath), 'Secretary assembly commission');
            }
            if ('primordial.assembly_commissions.rector' === $identity) {
                $rectorCommission = $this->decode($this->read($absolutePath), 'Rector assembly commission');
            }
        }
        ksort($observed, SORT_STRING);

        $launcher = $payload['launcher'];
        $masterMason = $payload['mastermason'];
        if (!is_array($successionCommission)) {
            throw new ValidationException('Manifest is missing the Recruiter succession commission.');
        }
        if (!is_array($secretaryCommission) || !is_array($rectorCommission)) {
            throw new ValidationException('Manifest is missing one or both triad assembly commissions.');
        }

        return new ValidationReceipt(
            $manifestId,
            (string) $payload['charter_generation'],
            hash('sha256', CanonicalJson::encode($observed)),
            (string) $launcher['digest'],
            (string) $masterMason['digest'],
            $successionCommission,
            $secretaryCommission,
            $rectorCommission,
            $manifest,
        );
    }

    private function validateTime(array $payload): void
    {
        $issuedAt = new \DateTimeImmutable((string) ($payload['issued_at'] ?? ''));
        if ($issuedAt > new \DateTimeImmutable()) {
            throw new ValidationException('Manifest is not yet valid.');
        }
        if (null !== ($payload['expires_at'] ?? null) && new \DateTimeImmutable((string) $payload['expires_at']) < new \DateTimeImmutable()) {
            throw new ValidationException('Manifest has expired.');
        }
    }

    private function validateSignature(array $manifest, string $manifestId): void
    {
        $trust = $manifest['unsigned_payload']['trust'] ?? null;
        $signatures = $manifest['signatures'] ?? null;
        if (!is_array($trust) || !is_array($signatures) || 1 !== count($signatures)) {
            throw new ValidationException('The v1 launch policy requires exactly one signature.');
        }

        $signature = $signatures[0];
        $signer = $trust['accepted_signers'][0] ?? null;
        if (!is_array($signer) || ($signature['key_id'] ?? null) !== ($signer['key_id'] ?? null)) {
            throw new ValidationException('Manifest signer is not accepted by the launch policy.');
        }
        if (($signature['algorithm'] ?? null) !== 'ed25519' || ($signature['signed_payload_digest'] ?? null) !== $manifestId) {
            throw new ValidationException('Manifest signature declaration is invalid.');
        }

        $publicKey = base64_decode((string) ($signer['public_key'] ?? ''), true);
        $signatureBytes = base64_decode((string) ($signature['signature'] ?? ''), true);
        if (false === $publicKey || false === $signatureBytes || !function_exists('sodium_crypto_sign_verify_detached')) {
            throw new ValidationException('Ed25519 signature verification is unavailable or malformed.');
        }
        if (!hash_equals((string) ($signer['public_key_digest'] ?? ''), hash('sha256', $publicKey))) {
            throw new ValidationException('Trusted signer public-key digest mismatch.');
        }
        if (!sodium_crypto_sign_verify_detached($signatureBytes, $manifestId, $publicKey)) {
            throw new ValidationException('Manifest signature verification failed.');
        }
    }

    private function flattenArtifacts(array $payload): array
    {
        $required = ['launcher', 'mastermason', 'runtime', 'primordial', 'compatibility'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $payload)) {
                throw new ValidationException(sprintf('Manifest is missing %s.', $key));
            }
        }

        $flat = ['launcher' => $payload['launcher'], 'mastermason' => $payload['mastermason']];
        $walk = function (array $node, string $prefix) use (&$walk, &$flat): void {
            if (isset($node['artifact'], $node['version'], $node['digest'])) {
                $flat[$prefix] = $node;
                foreach ($node as $key => $value) {
                    if (is_array($value)) {
                        $walk($value, $prefix.'.'.$key);
                    }
                }
                return;
            }
            foreach ($node as $key => $value) {
                if (!is_array($value)) {
                    throw new ValidationException(sprintf('Invalid artifact tree at %s.%s.', $prefix, $key));
                }
                $walk($value, $prefix.'.'.$key);
            }
        };
        $walk($payload['runtime'], 'runtime');
        $walk($payload['primordial'], 'primordial');
        $walk($payload['compatibility'], 'compatibility');

        return $flat;
    }

    private function validateArtifactRecord(string $identity, mixed $record): void
    {
        if (!is_array($record)) {
            throw new ValidationException(sprintf('Invalid artifact record: %s.', $identity));
        }
        foreach (['artifact', 'version', 'digest'] as $field) {
            if (!isset($record[$field]) || !is_string($record[$field]) || '' === $record[$field]) {
                throw new ValidationException(sprintf('Artifact %s is missing %s.', $identity, $field));
            }
        }
    }

    private function resolve(string $reference): string
    {
        if (!str_starts_with($reference, '/')) {
            throw new ValidationException('Artifact references must be repository-root absolute.');
        }
        $path = $this->projectDir.$reference;
        $real = realpath($path);
        $root = realpath($this->projectDir);
        if (false === $real || false === $root) {
            throw new ValidationException(sprintf('Artifact cannot be resolved: %s.', $reference));
        }

        $rootPrefix = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        if (!str_starts_with($real, $rootPrefix) && $real !== $root) {
            throw new ValidationException(sprintf('Artifact cannot be resolved: %s.', $reference));
        }

        return $real;
    }

    private function read(string $path): string
    {
        $contents = @file_get_contents($path);
        if (false === $contents) {
            throw new ValidationException(sprintf('Cannot read %s.', $path));
        }
        return $contents;
    }

    private function decode(string $json, string $label): array
    {
        try {
            $value = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ValidationException(sprintf('Invalid %s JSON: %s', $label, $exception->getMessage()), previous: $exception);
        }
        if (!is_array($value)) {
            throw new ValidationException(sprintf('%s must be a JSON object.', ucfirst($label)));
        }
        return $value;
    }

    private function requireExactKeys(array $value, array $keys, string $label): void
    {
        $actual = array_keys($value);
        sort($actual);
        sort($keys);
        if ($actual !== $keys) {
            throw new ValidationException(sprintf('%s contains missing or unknown top-level fields.', ucfirst($label)));
        }
    }
}
