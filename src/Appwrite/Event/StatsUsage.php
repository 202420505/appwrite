<?php

namespace Appwrite\Event;

use Utopia\Database\Document;
use Utopia\Queue\Publisher;

class StatsUsage extends Event
{
    protected array $metrics = [];
    protected array $reduce  = [];
    protected array $disabled = [];

    public function __construct(protected Publisher $publisher)
    {
        parent::__construct($publisher);

        $this
            ->setQueue(Event::STATS_USAGE_QUEUE_NAME)
            ->setClass(Event::STATS_USAGE_CLASS_NAME);
    }

    /**
     * Add reduce.
     *
     * @param Document $document
     * @return self
     */
    public function addReduce(Document $document): self
    {
        $this->reduce[] = $document;

        return $this;
    }

    /**
     * Add metric.
     *
     * @param string $key
     * @param int $value
     * @return self
     */
    public function addMetric(string $key, int $value): self
    {
        if ($this->disabled[$key]) {
            return $this;
        }

        $this->metrics[] = [
            'key' => $key,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Set disabled metrics.
     *
     * @param string $key
     * @return self
     */
    public function disableMetric(string $key): self
    {
        $this->disabled[$key] = true;

        return $this;
    }

    /**
     * Prepare the payload for the event
     *
     * @return array
     */
    protected function preparePayload(): array
    {
        return [
            'project' => $this->getProject(),
            'reduce'  => $this->reduce,
            'metrics' => $this->metrics,
        ];
    }
}
