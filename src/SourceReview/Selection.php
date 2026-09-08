<?php

declare(strict_types=1);

namespace App\SourceReview;

/** Derives excerpts from one verified local read; validates sealed selections without originals. */
final class Selection
{
    public static function ranges(mixed $ranges, int $lines): void
    {
        if (!is_array($ranges) || !array_is_list($ranges) || count($ranges) < 1 || count($ranges) > 30) {
            throw new \RuntimeException('SR_RANGE_INVALID');
        }
        $previous = 0;
        foreach ($ranges as $range) {
            if (!is_array($range) || !array_is_list($range) || count($range) !== 2
                || !is_int($range[0]) || !is_int($range[1]) || $range[0] <= $previous
                || $range[1] < $range[0] || $range[1] > $lines) {
                throw new \RuntimeException('SR_RANGE_INVALID');
            }
            $previous = $range[1];
        }
    }

    public static function derive(array $selection, string $original): array
    {
        Proposal::keys($selection, ['path', 'sha256', 'ranges']);
        Proposal::path($selection['path']);
        Proposal::text($original);
        if (!is_string($selection['sha256']) || !preg_match('/^[a-f0-9]{64}$/D', $selection['sha256'])
            || !hash_equals($selection['sha256'], hash('sha256', $original))) {
            throw new \RuntimeException('SR_ORIGINAL_HASH_MISMATCH');
        }
        if (strlen($original) > Proposal::LIMITS['source_bytes']) { throw new \RuntimeException('SR_INPUT_SIZE'); }
        $count = Proposal::lines($original);
        self::ranges($selection['ranges'], $count);
        // LF is the sole line separator. Retain CRLF and the exact final newline state.
        $lines = explode("\n", $original);
        $last = array_pop($lines);
        $lines = array_map(static fn (string $line): string => $line."\n", $lines);
        if ($last !== '') { $lines[] = $last; }
        $segments = [];
        foreach ($selection['ranges'] as [$start, $end]) {
            $segments[] = ['start_line' => $start, 'end_line' => $end,
                'bytes_base64' => base64_encode(implode('', array_slice($lines, $start - 1, $end - $start + 1)))];
        }
        return ['path' => $selection['path'], 'original_sha256' => $selection['sha256'],
            'original_bytes' => strlen($original), 'original_lines' => $count, 'segments' => $segments];
    }

    /** Reconstruct all metadata from the sealed segment bytes and explicit original coordinates. */
    public static function describe(array $file): array
    {
        Proposal::keys($file, ['path', 'original_sha256', 'original_bytes', 'original_lines', 'segments']);
        Proposal::path($file['path']);
        if (!is_string($file['original_sha256']) || !preg_match('/^[a-f0-9]{64}$/D', $file['original_sha256'])
            || !is_int($file['original_bytes']) || $file['original_bytes'] < 1 || $file['original_bytes'] > 131072
            || !is_int($file['original_lines']) || $file['original_lines'] < 1 || $file['original_lines'] > $file['original_bytes']
            || !is_array($file['segments']) || !array_is_list($file['segments'])) {
            throw new \RuntimeException('SR_SELECTION_INVALID');
        }
        $ranges = [];
        foreach ($file['segments'] as $segment) {
            if (!is_array($segment)) { throw new \RuntimeException('SR_SELECTION_INVALID'); }
            Proposal::keys($segment, ['start_line', 'end_line', 'bytes_base64']);
            $ranges[] = [$segment['start_line'], $segment['end_line']];
        }
        self::ranges($ranges, $file['original_lines']);
        $segments = []; $data = []; $omitted = []; $next = 1; $total = 0;
        foreach ($file['segments'] as $segment) {
            $bytes = is_string($segment['bytes_base64']) ? base64_decode($segment['bytes_base64'], true) : false;
            if ($bytes === false || base64_encode($bytes) !== $segment['bytes_base64']) { throw new \RuntimeException('SR_BASE64_INVALID'); }
            Proposal::text($bytes);
            $start = $segment['start_line']; $end = $segment['end_line'];
            $count = $end - $start + 1;
            if (Proposal::lines($bytes) !== $count || ($end < $file['original_lines'] && !str_ends_with($bytes, "\n"))) {
                throw new \RuntimeException('SR_SEGMENT_LINES_INVALID');
            }
            if ($start > $next) { $omitted[] = [$next, $start - 1]; }
            $next = $end + 1; $total += strlen($bytes);
            $metadata = ['start_line' => $start, 'end_line' => $end, 'segment_start_line' => 1,
                'segment_end_line' => $count, 'bytes' => strlen($bytes), 'sha256' => hash('sha256', $bytes)];
            $segments[] = $metadata;
            $data[] = [...$metadata, 'content' => $bytes];
        }
        if ($next <= $file['original_lines']) { $omitted[] = [$next, $file['original_lines']]; }
        if ($total > $file['original_bytes']) { throw new \RuntimeException('SR_SELECTION_INVALID'); }
        // When all original bytes are present, their identity must also match the original hash.
        if ($omitted === [] && ($total !== $file['original_bytes']
            || hash('sha256', implode('', array_column($data, 'content'))) !== $file['original_sha256'])) {
            throw new \RuntimeException('SR_ORIGINAL_HASH_MISMATCH');
        }
        $identity = ['path' => $file['path'], 'original_sha256' => $file['original_sha256'],
            'original_bytes' => $file['original_bytes'], 'lines' => $file['original_lines'],
            'selection' => $omitted === [] ? 'whole_file' : 'excerpts', 'omitted_ranges' => $omitted];
        return ['bytes' => $total, 'manifest' => [...$identity, 'segments' => $segments],
            'data' => [...$identity, 'segments' => $data]];
    }
}
