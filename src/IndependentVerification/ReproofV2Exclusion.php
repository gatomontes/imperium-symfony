<?php

declare(strict_types=1);

namespace App\IndependentVerification;

use App\ReproofV2\Records;

/** Independent scanner; exact finite case validation additionally rejects opaque payload slots. */
final class ReproofV2Exclusion
{
    public function derive(array $sections): array
    {
        $hashes = [];
        foreach ($sections as $id => $data) { $this->scan($data); $hashes[$id] = Records::hash($data); }
        return ['policy' => 'strict-profile-and-bounded-decoding/v2', 'sections' => $hashes,
            'decode_depth' => 3, 'synthetic_negative_vectors' => $this->negativeObservations(),
            'retained_forbidden_value' => false];
    }

    private function negativeObservations(): array
    {
        $marker = 'REPROOF_SYNTHETIC_FORBIDDEN';
        $vectors = ['plain' => $marker, 'base64' => base64_encode($marker), 'hex' => bin2hex($marker),
            'percent' => '%52'.substr($marker, 1), 'split' => ['REPROOF_SYNTHETIC_', 'FORBIDDEN']];
        $observations = [];
        foreach ($vectors as $id => $value) {
            try { $this->scan($value); }
            catch (\RuntimeException) { $observations[$id] = 'REFUSED'; continue; }
            throw new \RuntimeException('REPROOF_EXCLUSION_SELF_CHECK_FAILED');
        }
        return $observations;
    }

    public function scan(mixed $value): void
    {
        $queue = [[$value, 0]];
        while ([] !== $queue) {
            [$item, $depth] = array_pop($queue);
            if ($depth > 40 || is_object($item) || is_resource($item) || is_float($item)) { $this->refuse(); }
            if (is_array($item)) {
                $text = '';
                foreach ($item as $key => $child) {
                    if (is_string($key) && preg_match('/secret|password|private[_-]?key|credential[_-]?value|environment[_-]?dump/i', $key)) { $this->refuse(); }
                    if (is_string($child)) { $text .= $child; }
                    $queue[] = [$child, $depth + 1];
                }
                $this->strings($text);
            } elseif (is_string($item)) { $this->strings($item); }
        }
    }

    private function strings(string $text): void
    {
        $pending = [[$text, 0]];
        while ([] !== $pending) {
            [$current, $level] = array_pop($pending);
            if (strlen($current) > 2000000 || preg_match('/Bearer\s*\S+|-----BEGIN[^-]*PRIVATE KEY-----|process-local-capability:\/\/|REPROOF_SYNTHETIC_FORBIDDEN/i', $current)) { $this->refuse(); }
            if ($level === 3 || '' === $current) { continue; }
            $decoded = rawurldecode($current);
            if ($decoded !== $current) { $pending[] = [$decoded, $level + 1]; }
            if (0 === strlen($current) % 2 && ctype_xdigit($current)) { $pending[] = [hex2bin($current), $level + 1]; }
            if (0 === strlen($current) % 4 && preg_match('/^[A-Za-z0-9+\/]+=*$/D', $current)) {
                $decoded = base64_decode($current, true);
                if (false !== $decoded && $decoded !== $current) { $pending[] = [$decoded, $level + 1]; }
            }
        }
    }

    private function refuse(): never { throw new \RuntimeException('REPROOF_INDEPENDENT_EXCLUSION_REFUSED'); }
}
