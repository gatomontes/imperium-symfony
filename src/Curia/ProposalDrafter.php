<?php

namespace App\Curia;

use App\Entity\Interview;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ProposalDrafter
{
    private const MAX_SCALAR_LENGTH = 12000;
    private const MAX_ITEMS = 50;
    private const MAX_ITEM_LENGTH = 3000;

    public function __construct(
        #[Autowire(service: 'ai.agent.proposal_drafter')]
        private AgentInterface $agent,
        #[Autowire(env: 'DEEPSEEK_API_KEY')]
        #[\SensitiveParameter]
        private string $apiKey,
    ) {
    }

    public function assertConfigured(): void
    {
        if ('' === trim($this->apiKey)) {
            throw new \DomainException('Set DEEPSEEK_API_KEY in .env.local before generating a proposal.');
        }
    }

    public function draft(Interview $interview): ProposalDraft
    {
        if (Interview::DRAFT_AUTHORIZED !== $interview->getStatus()) {
            throw new \DomainException('Drafting permission is required before generating a proposal.');
        }

        $messages = new MessageBag(Message::ofUser(json_encode([
            'missionAlias' => $interview->getAlias(),
            'authorizedInterviewVersion' => $interview->getVersion(),
            'transcript' => $this->sourceTranscript($interview),
        ], \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE)));

        $result = $this->agent->call($messages, [
            'response_format' => ['type' => 'json_object'],
            'max_tokens' => 3072,
            'thinking' => ['type' => 'disabled'],
        ])->getContent();

        if (!\is_array($result)) {
            throw new InvalidProposalDraft('Proposal format: expected a JSON object.');
        }

        $objective = $this->stringField($result, 'objective');
        $deliverable = $this->stringField($result, 'deliverable');

        return new ProposalDraft(
            $objective,
            $deliverable,
            $this->listField($result, 'steps', required: true),
            $this->listField($result, 'acceptanceCriteria', required: true),
            $this->listField($result, 'resourceRequirements'),
            $this->listField($result, 'limits'),
            $this->listField($result, 'unresolvedAssumptions'),
        );
    }

    /** @return list<array{role:string,text:string}> */
    private function sourceTranscript(Interview $interview): array
    {
        $transcript = [];
        $permissionSuffix = "\n\n".Interview::PERMISSION_QUESTION;
        foreach ($interview->getExchanges() as $exchange) {
            if ('decision' === $exchange['role']) {
                continue;
            }
            $text = $exchange['text'];
            if ('assistant' === $exchange['role'] && str_ends_with($text, $permissionSuffix)) {
                $text = substr($text, 0, -\strlen($permissionSuffix));
            }
            $transcript[] = ['role' => $exchange['role'], 'text' => $text];
        }

        return $transcript;
    }

    /** @param array<string, mixed> $result */
    private function stringField(array $result, string $field): string
    {
        if (!isset($result[$field]) || !\is_string($result[$field])) {
            throw new InvalidProposalDraft('Proposal format: "'.$field.'" must be a string.');
        }
        $value = trim($result[$field]);
        if ('' === $value || mb_strlen($value) > self::MAX_SCALAR_LENGTH) {
            throw new InvalidProposalDraft('Proposal format: "'.$field.'" is empty or too long.');
        }

        return $value;
    }

    /** @param array<string, mixed> $result
     *  @return list<string>
     */
    private function listField(array $result, string $field, bool $required = false): array
    {
        if (!array_key_exists($field, $result) || !\is_array($result[$field]) || array_is_list($result[$field]) === false) {
            throw new InvalidProposalDraft('Proposal format: "'.$field.'" must be an array.');
        }
        if (\count($result[$field]) > self::MAX_ITEMS || ($required && [] === $result[$field])) {
            throw new InvalidProposalDraft('Proposal format: "'.$field.'" has an invalid number of items.');
        }

        $items = [];
        foreach ($result[$field] as $item) {
            if (!\is_string($item)) {
                throw new InvalidProposalDraft('Proposal format: "'.$field.'" must contain only strings.');
            }
            $item = trim($item);
            if ('' === $item || mb_strlen($item) > self::MAX_ITEM_LENGTH) {
                throw new InvalidProposalDraft('Proposal format: "'.$field.'" contains an empty or overlong item.');
            }
            $items[] = $item;
        }

        return $items;
    }
}
