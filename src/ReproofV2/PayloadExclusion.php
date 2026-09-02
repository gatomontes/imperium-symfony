<?php

declare(strict_types=1);

namespace App\ReproofV2;

final class PayloadExclusion
{
    public function prove(array $sections): array
    {
        $digests = [];
        foreach ($sections as $name => $value) {
            $this->check($value);
            $digests[$name] = Records::hash($value);
        }
        return ['policy' => 'strict-profile-and-bounded-decoding/v2', 'sections' => $digests,
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
            try { $this->check($value); }
            catch (\RuntimeException) { $observations[$id] = 'REFUSED'; continue; }
            throw new \RuntimeException('REPROOF_EXCLUSION_SELF_CHECK_FAILED');
        }
        return $observations;
    }

    public function check(mixed $value, int $depth = 0): void
    {
        if ($depth > 40 || is_object($value) || is_resource($value) || is_float($value)) {
            throw new \RuntimeException('REPROOF_PAYLOAD_REFUSED');
        }
        if (is_array($value)) {
            $joined = '';
            foreach ($value as $key => $child) {
                if (is_string($key) && preg_match('/secret|password|private[_-]?key|credential[_-]?value|environment[_-]?dump/i', $key)) {
                    throw new \RuntimeException('REPROOF_PAYLOAD_REFUSED');
                }
                $this->check($child, $depth + 1);
                if (is_string($child)) {
                    $joined .= $child;
                }
            }
            $this->text($joined, 0);
        } elseif (is_string($value)) {
            $this->text($value, 0);
        }
    }

    private function text(string $text, int $depth): void
    {
        if (strlen($text) > 2000000 || preg_match('/Bearer\s*\S+|-----BEGIN[^-]*PRIVATE KEY-----|process-local-capability:\/\/|REPROOF_SYNTHETIC_FORBIDDEN/i', $text)) {
            throw new \RuntimeException('REPROOF_PAYLOAD_REFUSED');
        }
        if ($depth >= 3 || '' === $text) {
            return;
        }
        $decoded = rawurldecode($text);
        if ($decoded !== $text) { $this->text($decoded, $depth + 1); }
        if (strlen($text) % 2 === 0 && ctype_xdigit($text)) { $this->text(hex2bin($text), $depth + 1); }
        if (strlen($text) % 4 === 0 && preg_match('/^[A-Za-z0-9+\/]+=*$/D', $text)) {
            $decoded = base64_decode($text, true);
            if (false !== $decoded && $decoded !== $text) { $this->text($decoded, $depth + 1); }
        }
    }
}
