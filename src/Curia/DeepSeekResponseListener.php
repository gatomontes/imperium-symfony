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
    }
}
