<?php

namespace App\Curia;

final readonly class ProposalDraft
{
    /**
     * @param list<string> $steps
     * @param list<string> $acceptanceCriteria
     * @param list<string> $resourceRequirements
     * @param list<string> $limits
     * @param list<string> $unresolvedAssumptions
     */
    public function __construct(
        public string $objective,
        public string $deliverable,
        public array $steps,
        public array $acceptanceCriteria,
        public array $resourceRequirements,
        public array $limits,
        public array $unresolvedAssumptions,
    ) {
    }

    /** @return array{objective:string,deliverable:string,steps:list<string>,acceptanceCriteria:list<string>,resourceRequirements:list<string>,limits:list<string>,unresolvedAssumptions:list<string>} */
    public function toArray(): array
    {
        return [
            'objective' => $this->objective,
            'deliverable' => $this->deliverable,
            'steps' => $this->steps,
            'acceptanceCriteria' => $this->acceptanceCriteria,
            'resourceRequirements' => $this->resourceRequirements,
            'limits' => $this->limits,
            'unresolvedAssumptions' => $this->unresolvedAssumptions,
        ];
    }
}
