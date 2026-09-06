<?php
declare(strict_types=1);
namespace App\SourceReview;

interface Transport
{
    /** Return the unmodified model content, or throw; never retry. */
    public function send(string $secret, string $payload): string;
}
