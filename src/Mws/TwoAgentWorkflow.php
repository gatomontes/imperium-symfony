<?php

namespace App\Mws;

/**
 * A minimal synchronous, no-tools collaboration proof.
 * Persistence and provider wiring are separate milestones.
 */
final class TwoAgentWorkflow
{
    public function run(string $task, AgentResponder $agent): array
    {
        $task = trim($task);
        if ('' === $task || mb_strlen($task) > 6000) {
            throw new \InvalidArgumentException('Task must contain 1–6000 characters.');
        }

        $messages = [['role' => 'operator', 'text' => $task]];
        $instructions = [
            ['role' => 'coordinator', 'text' => "Summarize the task for a worker. Do not claim any work was performed. Task:\n".$task],
        ];

        try {
            $handoff = $this->respond($agent, 'coordinator', $instructions[0]['text']);
            $messages[] = ['role' => 'coordinator', 'text' => $handoff];

            $result = $this->respond($agent, 'worker', "Respond to the delegated task; use no tools or external effects:\n".$handoff);
            $messages[] = ['role' => 'worker', 'text' => $result];

            $final = $this->respond($agent, 'coordinator', "Summarize the worker's contribution without claiming verification:\n".$result);
            $messages[] = ['role' => 'coordinator', 'text' => $final];

            return ['status' => 'completed', 'messages' => $messages];
        } catch (\Throwable $error) {
            // Never include provider errors; these may contain credentials or private prompts.
            return ['status' => 'failed', 'messages' => $messages, 'error' => 'An agent reply failed.'];
        }
    }

    private function respond(AgentResponder $agent, string $role, string $instruction): string
    {
        $response = trim($agent->reply($role, $instruction));
        if ('' === $response || mb_strlen($response) > 12000) {
            throw new \UnexpectedValueException('Invalid agent reply.');
        }

        return $response;
    }
}
