<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Onboarding\ResponseValidation;

use App\Imperium\Runtime\Onboarding\Selection\{CapacityOrder, RecordRef, Shape};

/** Parsed, context-consistent claims. NOT admission, verified fitness, RoleInput or an effect.
 * Later genuine admission must verify the supplied context, evidence and substantive claims
 * before explicitly constructing selector inputs. This type provides no such conversion.
 */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class ParsedResponse
{
    private function __construct(
        public string $rawBytes, public string $rawDigest, public string $schema, public string $groupId,
        public string $inputDigest, public RecordRef $holderRef, public RecordRef $profileRef,
        public array $candidateRows, public array $contradictions, public array $unknowns,
        public array $evidenceRefs, public ?CapacityOrder $capacityOrder,
    ) {}

    /** @throws \InvalidArgumentException for malformed or context-inconsistent responses. */
    public static function parse(string $bytes, ExpectedContext $context): self
    {
        $keys = ['schema', 'group_id', 'input_digest', 'holder_ref', 'profile_ref', 'candidate_rows', 'contradictions', 'unknowns', 'evidence_refs'];
        if ($context->groupId !== 'W1') { $keys[] = 'capacity_order'; }
        $v = ResponseShape::object(RawJsonDecoder::decode($bytes), $keys);
        $schema = $context->groupId === 'W1' ? 'imperium.bootstrap-assessment-result/v1' : 'imperium.bootstrap-role-assessment-response/v2';
        $holder = ResponseShape::ref($v['holder_ref']); $profile = ResponseShape::ref($v['profile_ref']);
        if ($v['schema'] !== $schema || $v['group_id'] !== $context->groupId || $v['input_digest'] !== $context->inputDigest
            || $holder->key() !== $context->holderRef->key() || $profile->key() !== $context->profileRef->key()) {
            throw new \InvalidArgumentException('RESPONSE_CONTEXT_MISMATCH');
        }
        $evidence = ResponseShape::index($context->evidenceRefs);
        $candidates = ResponseShape::index($context->candidateRefs);
        $rows = []; $seen = []; $fit = [];
        foreach (Shape::list($v['candidate_rows']) as $raw) {
            $row = CandidateClaim::parse($raw, $evidence); $key = $row->bindingRef->key();
            if (!isset($candidates[$key]) || isset($seen[$key])) { throw new \InvalidArgumentException('INVALID_CANDIDATE_COVERAGE'); }
            $seen[$key] = true; $rows[] = $row;
            if ($row->fit === 'FIT') { $fit[$key] = $row->bindingRef; }
        }
        if (count($seen) !== count($candidates)) { throw new \InvalidArgumentException('INVALID_CANDIDATE_COVERAGE'); }
        $order = $context->groupId === 'W1' ? null : self::capacity($v['capacity_order'], $evidence, $fit);
        return new self($bytes, 'sha256:'.hash('sha256', $bytes), $schema, $context->groupId, $context->inputDigest,
            $holder, $profile, $rows, ResponseShape::texts($v['contradictions']), ResponseShape::texts($v['unknowns']),
            ResponseShape::refs($v['evidence_refs'], $evidence), $order);
    }

    private static function capacity(mixed $value, array $evidence, array $fit): CapacityOrder
    {
        if (!$value instanceof JsonObject) { throw new \InvalidArgumentException('INVALID_OBJECT'); }
        $kind = $value->members['kind'] ?? null;
        $refsArray = static fn (array $refs): array => array_map(ResponseShape::refArray(...), $refs);
        if ($kind === 'unknown') {
            $v = ResponseShape::object($value, ['kind', 'reason', 'evidence_refs']);
            return CapacityOrder::decode(['kind' => 'unknown', 'reason' => ResponseShape::text($v['reason']),
                'evidence_refs' => $refsArray(ResponseShape::refs($v['evidence_refs'], $evidence))]);
        }
        if ($kind !== 'ranked') { throw new \InvalidArgumentException('INVALID_CAPACITY_ORDER'); }
        $v = ResponseShape::object($value, ['kind', 'tiers']); $tiers = []; $seen = [];
        foreach (Shape::list($v['tiers'], true) as $raw) {
            $tier = ResponseShape::object($raw, ['binding_refs', 'rationale', 'evidence_refs']);
            $bindings = ResponseShape::refs($tier['binding_refs'], $fit, true);
            foreach ($bindings as $ref) {
                if (isset($seen[$ref->key()])) { throw new \InvalidArgumentException('INVALID_CAPACITY_COVERAGE'); }
                $seen[$ref->key()] = true;
            }
            $tiers[] = ['binding_refs' => $refsArray($bindings), 'rationale' => ResponseShape::text($tier['rationale']),
                'evidence_refs' => $refsArray(ResponseShape::refs($tier['evidence_refs'], $evidence, true))];
        }
        if (count($seen) !== count($fit)) { throw new \InvalidArgumentException('INVALID_CAPACITY_COVERAGE'); }
        // Only now project checked JSON objects/lists into the existing capacity value parser.
        return CapacityOrder::decode(['kind' => 'ranked', 'tiers' => $tiers]);
    }
}
