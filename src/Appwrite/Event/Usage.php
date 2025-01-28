<?php

namespace Appwrite\Event;

use Utopia\Database\Document;
use Utopia\Queue\Connection;

class Usage extends Event
{
    protected array $metrics = [];
    protected array $reduce  = [];

    public function __construct(protected Connection $connection)
    {
        parent::__construct($connection);

        $this
            ->setQueue(Event::USAGE_QUEUE_NAME)
            ->setClass(Event::USAGE_CLASS_NAME);
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

        $this->metrics[] = [
            'key' => $key,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Prepare the payload for the usage event.
     *
     * @return array
     */
    protected function preparePayload(): array
    {
        return [
            'project' => $this->project,
            'reduce'  => $this->reduce,
            'metrics' => $this->metrics,
        ];
    }

    /**
     * Sends metrics to the usage worker.
     *
     * @return string|bool
     */
    public function trigger(): string|bool
    {
        parent::trigger();
        $this->metrics = [];
        return true;
    }
}
