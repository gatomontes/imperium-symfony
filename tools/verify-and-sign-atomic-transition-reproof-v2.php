<?php

declare(strict_types=1);

// Batch 6 only, after separate operator approval. Inclusion never runs intake or signing.
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__) { return; }
if (3 !== $argc || '--approved-request' !== $argv[1]) {
    fwrite(STDERR, "Usage: php -n -d extension_dir=APPROVED_EXT_DIR -d extension=sodium tools/verify-and-sign-atomic-transition-reproof-v2.php --approved-request SHA256\n");
    exit(2);
}
$secret = null;
try {
    if (false !== php_ini_loaded_file() || false !== php_ini_scanned_files() || !extension_loaded('sodium')) {
        throw new RuntimeException('REPROOF_SIGNING_RUNTIME_REFUSED');
    }
    $request = json_decode(file_get_contents(dirname(__DIR__).'/docs/atomic-transition-reproof-v2-verification-signing-request.json'), true, flags: JSON_THROW_ON_ERROR);
    // Request fields are scalars; its sorted-object JSON is the specified canonical form.
    $sorted = $request; ksort($sorted, SORT_STRING);
    if (hash('sha256', json_encode($sorted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)) !== $argv[2]
        || $request['status'] !== 'REQUEST_NOT_AUTHORIZATION'
        || $request['maximum_verifications'] !== 1 || $request['maximum_signatures'] !== 1
        || $request['purpose'] !== 'imperium.atomic-transition-reproof.independent-report/v2'
        || $request['key_policy'] !== 'NEW_LOCAL_ED25519_OPERATOR_ONLY_ACL_NO_EXISTING_KEY_LOOKUP'
        || $request['signing_requires'] !== 'ALL_EIGHT_INDEPENDENT_DOMAINS_PASS') {
        throw new RuntimeException('REPROOF_APPROVED_REQUEST_MISMATCH');
    }
    foreach (['provider_authorized', 'network_authorized', 'mission_retry_authorized', 'live_runtime_state_write_authorized', 'admission_authorized', 'closure_authorized'] as $field) {
        if (false !== $request[$field]) { throw new RuntimeException('REPROOF_SCOPE_REFUSED'); }
    }
    if (PHP_VERSION !== $request['runtime_version'] || hash_file('sha256', PHP_BINARY) !== $request['php_binary_sha256']
        || hash_file('sha256', rtrim(ini_get('extension_dir'), '/\\').'/php_sodium.dll') !== $request['sodium_extension_sha256']
        || SODIUM_LIBRARY_VERSION !== $request['sodium_library_version']) {
        throw new RuntimeException('REPROOF_SIGNING_RUNTIME_MISMATCH');
    }
    $root = $request['verifier_checkout'];
    $files = ['src/Bootstrap/CanonicalJson.php', 'src/ReproofV2/Records.php',
        'src/IndependentVerification/ReproofV2CaseEvaluator.php', 'src/IndependentVerification/ReproofV2Exclusion.php',
        'src/IndependentVerification/ReproofV2SourceProof.php', 'src/IndependentVerification/ReproofV2Verifier.php'];
    $hashes = [];
    foreach ($files as $path) { $hashes[$path] = hash_file('sha256', $root.'/'.$path); }
    ksort($hashes, SORT_STRING);
    if (hash('sha256', json_encode($hashes, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)) !== $request['verifier_root']) {
        throw new RuntimeException('REPROOF_VERIFIER_PIN_MISMATCH');
    }
    foreach ($files as $path) { require_once $root.'/'.$path; }
    $allowed = [realpath(__FILE__)];
    foreach ($files as $path) { $allowed[] = realpath($root.'/'.$path); }
    $loaded = array_map('realpath', get_included_files()); sort($allowed); sort($loaded);
    if ($allowed !== $loaded || [] !== spl_autoload_functions()) { throw new RuntimeException('REPROOF_SIGNING_LOADER_REFUSED'); }

    $signing = realpath($request['new_signing_directory']);
    $receiptDirectory = realpath($request['receipt_directory']);
    // The approval's PowerShell preparation must first establish the operator-only ACL.
    if (false === $signing || false === $receiptDirectory || is_link($request['new_signing_directory'])
        || is_link($request['receipt_directory']) || $signing === $receiptDirectory
        || scandir($signing) !== ['.', '..']) { throw new RuntimeException('REPROOF_FRESH_SIGNING_CUSTODY_REQUIRED'); }
    $write = static function (string $path, string $bytes): void {
        $stream = @fopen($path, 'xb');
        if (false === $stream) { throw new RuntimeException('REPROOF_EXCLUSIVE_WRITE_REFUSED'); }
        try {
            $offset = 0;
            while ($offset < strlen($bytes)) {
                $written = fwrite($stream, substr($bytes, $offset));
                if (false === $written || 0 === $written) { throw new RuntimeException('REPROOF_WRITE_INCOMPLETE'); }
                $offset += $written;
            }
            if (!fflush($stream) || !fsync($stream)) { throw new RuntimeException('REPROOF_FLUSH_FAILED'); }
        } finally { fclose($stream); }
    };
    $json = static fn (array $record): string => json_encode($record, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
    $write($signing.'/reservation.json', $json(['request_digest' => $argv[2], 'state' => 'RESERVED_NO_RETRY']));

    // Operator-controlled fresh key custody is established before any private receipt intake.
    $pair = sodium_crypto_sign_keypair();
    $secret = sodium_crypto_sign_secretkey($pair);
    $public = sodium_crypto_sign_publickey($pair);
    sodium_memzero($pair);
    $write($signing.'/signing-key.ed25519', $secret);
    $now = time();
    $identity = \App\ReproofV2\Records::seal(['schema' => 'imperium.atomic-transition-reproof.public-identity/v2',
        'identity_id' => $request['identity_id'], 'purpose' => $request['purpose'], 'public_key' => bin2hex($public),
        'not_before' => gmdate('Y-m-d\TH:i:s\Z', $now), 'expires_at' => gmdate('Y-m-d\TH:i:s\Z', $now + $request['identity_validity_seconds']),
        'verifier_root' => $request['verifier_root']]);
    $write($signing.'/identity.json', $json($identity));
    $trust = ['proof_id' => $request['proof_id'], 'source_commit' => $request['source_commit'],
        'source_manifest_root' => $request['source_manifest_root'], 'authorization_digest' => $request['execution_authorization_digest'],
        'runtime_version' => $request['runtime_version'], 'verifier_root' => $request['verifier_root'],
        'identity_digest' => $identity['record_digest'], 'evidence_class' => 'OPERATOR_AUTHORIZED_LOCAL_EXECUTION'];
    $write($signing.'/trust.json', $json($trust));

    $read = static function (string $path): string {
        $size = @filesize($path);
        if (false === $size || $size > 8000000 || is_link($path)) { throw new RuntimeException('REPROOF_PRIVATE_INTAKE_REFUSED'); }
        $bytes = @file_get_contents($path, false, null, 0, 8000001);
        if (false === $bytes || strlen($bytes) > 8000000) { throw new RuntimeException('REPROOF_PRIVATE_INTAKE_REFUSED'); }
        return $bytes;
    };
    $manifestBytes = $read($receiptDirectory.'/finalized.json');
    if (hash('sha256', $manifestBytes) !== $request['finalization_manifest_sha256']) { throw new RuntimeException('REPROOF_FINALIZATION_PIN_MISMATCH'); }
    $manifest = json_decode($manifestBytes, true, flags: JSON_THROW_ON_ERROR);
    if (array_keys($manifest) !== ['proof_id', 'receipt_digest', 'candidate_digest'] || $manifest['proof_id'] !== $request['proof_id']) { throw new RuntimeException('REPROOF_MANIFEST_INVALID'); }
    $package = [];
    foreach (['receipt', 'candidate'] as $name) {
        $bytes = $read($receiptDirectory.'/'.$name.'.json');
        if (hash('sha256', $bytes) !== $manifest[$name.'_digest']) { throw new RuntimeException('REPROOF_PACKAGE_CHECKSUM_MISMATCH'); }
        $package[$name] = json_decode($bytes, true, flags: JSON_THROW_ON_ERROR);
        if (($package[$name]['record_digest'] ?? null) !== $request[$name.'_digest']) { throw new RuntimeException('REPROOF_PACKAGE_PIN_MISMATCH'); }
    }
    $report = (new \App\IndependentVerification\ReproofV2Verifier())->verify($package, $trust);
    unset($package, $bytes);
    $write($signing.'/report.json', $json($report));
    $domains = ['source_and_build', 'receipt_structure', 'origin_and_provenance', 'trusted_result',
        'dependency_graph', 'acceptance_matrix', 'complete_chain_exclusion', 'non_authority_perimeter'];
    if ('PASS' !== $report['disposition'] || $report['domain_outcomes'] !== array_fill_keys($domains, 'PASS')
        || $report['candidate_digest'] !== $request['candidate_digest'] || $report['receipt_digest'] !== $request['receipt_digest']
        || $report['trusted_identity_digest'] !== $identity['record_digest'] || $report['verifier_root'] !== $request['verifier_root']
        || $report['qualification_removed'] !== false || $report['campaign_closed'] !== false
        || time() >= $now + $request['identity_validity_seconds']) { throw new RuntimeException('REPROOF_NOT_SIGNABLE'); }
    $message = $request['purpose']."\0".$report['record_digest'];
    $signature = sodium_crypto_sign_detached($message, $secret);
    sodium_memzero($secret);
    if (!sodium_crypto_sign_verify_detached($signature, $message, $public)) { throw new RuntimeException('REPROOF_SIGNATURE_CHECK_FAILED'); }
    $attestation = \App\ReproofV2\Records::seal(['schema' => 'imperium.atomic-transition-reproof.detached-attestation/v2',
        'purpose' => $request['purpose'], 'report_digest' => $report['record_digest'], 'identity_digest' => $identity['record_digest'],
        'signature' => bin2hex($signature)]);
    $write($signing.'/attestation.json', $json($attestation));
    $write($signing.'/finalized.json', $json(['proof_id' => $request['proof_id'], 'identity_digest' => $identity['record_digest'],
        'report_digest' => $report['record_digest'], 'attestation_digest' => $attestation['record_digest']]));
    fwrite(STDOUT, "REPROOF_V2_VERIFIED_AND_ATTESTED_PENDING_ADMISSION\n");
} catch (Throwable) {
    if (is_string($secret)) { sodium_memzero($secret); }
    fwrite(STDERR, "REPROOF_V2_VERIFICATION_OR_SIGNING_REFUSED_NO_AUTOMATIC_RETRY\n");
    exit(1);
}
