<?php

namespace App\Curia;

use Symfony\AI\Platform\Bridge\DeepSeek\DeepSeek;
use Symfony\AI\Platform\Event\ResultEvent;
use Symfony\AI\Platform\Result\RawHttpResult;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class DeepSeekResponseListener
{
    #[AsEventListener(priority: 100)]
    public function onResult(ResultEvent $event): void
    {
        $raw = $event->getDeferredResult()->getRawResult();
        if (!$event->getModel() instanceof DeepSeek || !$raw instanceof RawHttpResult) {
            return;
        }

        // The installed bridge does not classify every HTTP error (e.g. 402).
        // Let HttpClient retain the status before an error body is read as a reply.
        $raw->getObject()->getHeaders();
        if (($event->getOptions()['stream'] ?? false) || ['type' => 'json_object'] !== ($event->getOptions()['response_format'] ?? null)) {
            return;
        }
        $data = $raw->getData();
        if ('length' === ($data['choices'][0]['finish_reason'] ?? null)) {
            throw new InvalidSeneschalReply('Reply format: DeepSeek reached the output limit before finishing.');
        }
        $content = $data['choices'][0]['message']['content'] ?? null;
        if (null === $content || (\is_string($content) && '' === trim($content))) {
            throw new InvalidSeneschalReply('Reply format: DeepSeek returned empty content.');
        }
        if (!\is_string($content)) {
            throw new InvalidSeneschalReply('Reply format: expected text containing a JSON object.');
        }
        // Reject scalar JSON before the platform wraps it in an ObjectResult.
        if (!json_decode($content, flags: \JSON_THROW_ON_ERROR) instanceof \stdClass) {
            throw new InvalidSeneschalReply('Reply format: expected a JSON object.');
        }
    }
}
