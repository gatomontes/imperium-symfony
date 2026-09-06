<?php
declare(strict_types=1);
namespace App\SourceReview;

use App\Imperium\Runtime\Clavium\OperationalClaimBoundCredentialBroker;
use App\Imperium\Runtime\Clavium\ProviderInvocationJournalService;
use App\Imperium\Runtime\Clavium\ProviderResponseEnvelopeService;
use App\Imperium\Runtime\Clock;

/** Uses existing operational claim and journal; introduces no authority issuer. */
final readonly class Gateway
{
    public function __construct(private OperationalClaimBoundCredentialBroker $broker, private ProviderInvocationJournalService $journal, private ProviderResponseEnvelopeService $responses, private Transport $transport, private Clock $clock, private \App\Imperium\Runtime\Persistence\ImmutableRecordStore $records) {}
    public function execute(array $authorization, array $manifestation): array
    {
        $p = Proposal::validate($authorization['input']['source_review']);
        Proposal::preflight($p, $this->clock->now());
        if (($authorization['input_digest'] ?? null) !== Proposal::digest($authorization['input'])) { throw new \RuntimeException('SR_INPUT_CHANGED'); }
        $claim = $this->broker->claimFor($authorization, $manifestation, $this->clock->now());
        if (($claim['input_digest'] ?? null) !== $authorization['input_digest'] || ($claim['model'] ?? null) !== Proposal::MODEL
            || ($claim['model_configuration'] ?? null) !== ['temperature' => 0.2]
            || ($claim['resource_ceiling'] ?? null) !== self::ceiling()) { throw new \RuntimeException('SR_AUTHORIZED_SETTINGS_CHANGED'); }
        $this->journal->reserveOperational($claim, $this->clock->now());
        $started = false;
        try {
            $response = $this->broker->consume($claim, $this->clock->now(), function (mixed $secret) use ($claim, $p, &$started): string {
                if (!is_string($secret) || $secret === '') { throw new \RuntimeException('SR_CREDENTIAL_UNAVAILABLE'); }
                $this->journal->startReservedOperational($claim, $this->clock->now());
                $started = true;
                // The already validated immutable in-memory string is sent, with no second read.
                $response = $this->transport->send($secret, $p['payload']);
                $this->responses->seal($claim, $response, $this->clock->now());
                $this->journal->sealResponse($claim, $response, $this->clock->now());
                return $response;
            });
        } catch (\Throwable) {
            if ($started) { $this->journal->markUnknown($claim, $this->clock->now()); }
            else { $this->journal->failReservedOperational($claim, 'SOURCE_REVIEW_PRE_IO_FAILURE', $this->clock->now()); }
            throw new \RuntimeException($started ? 'SR_UNKNOWN_REPLAY_PROHIBITED' : 'SR_PRE_IO_FAILURE_REPLAY_PROHIBITED');
        }
        try { $review = Result::parse($response, $p); } catch (\Throwable) {
            $this->records->put('var/imperium/mission/source-review/results', $claim['claim_id'], ['status' => 'RESULT_INVALID', 'authorization_id' => $authorization['authorization_id'], 'response_sha256' => hash('sha256', $response), 'automatic_retry_permitted' => false]);
            throw new \RuntimeException('SR_RESULT_INVALID');
        }
        $result = ['review' => $review, 'provenance' => ['proposal_id' => $p['proposal_id'], 'input_digest' => $authorization['input_digest'], 'manifest_digest' => $p['manifest_digest'], 'behavior_digest' => $p['behavior_digest'], 'payload_digest' => $p['payload_digest'], 'authorization_id' => $authorization['authorization_id'], 'authorization_digest' => $authorization['record_digest'], 'claim_id' => $claim['claim_id'], 'response_sha256' => hash('sha256', $response)], 'provider_invoked' => true];
        return $this->records->put('var/imperium/mission/source-review/results', $claim['claim_id'], ['status' => 'RESULT_VALIDATED_STATIC_ONLY', ...$result]);
    }
    public static function ceiling(): array { return ['maximum_input_tokens' => 32000, 'maximum_output_tokens' => 4000, 'maximum_cost_microusd' => 1000000]; }
}
