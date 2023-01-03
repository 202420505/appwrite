<?php

namespace Appwrite\Migration\Version;

use Appwrite\Migration\Migration;
use Utopia\CLI\Console;
use Utopia\Database\Database;
use Utopia\Database\Document;

class V17 extends Migration
{
    public function execute(): void
    {
        /**
         * Disable SubQueries for Performance.
         */
        foreach (['subQueryIndexes', 'subQueryPlatforms', 'subQueryDomains', 'subQueryKeys', 'subQueryWebhooks', 'subQuerySessions', 'subQueryTokens', 'subQueryMemberships', 'subqueryVariables'] as $name) {
            Database::addFilter(
                $name,
                fn () => null,
                fn () => []
            );
        }

        Console::log('Migrating Project: ' . $this->project->getAttribute('name') . ' (' . $this->project->getId() . ')');

        Console::info('Migrating Collections');
        $this->migrateCollections();

        // Console::info('Migrating Documents');
        // $this->forEachDocument([$this, 'fixDocument']);
    }

    /**
     * Migrate all Collections.
     *
     * @return void
     */
    protected function migrateCollections(): void
    {
        foreach ($this->collections as $collection) {
            $id = $collection['$id'];

            Console::log("Migrating Collection \"{$id}\"");

            $this->projectDB->setNamespace("_{$this->project->getInternalId()}");

            switch ($id) {
                case 'files':
                    try {
                        /**
                         * Update 'mimeType' attribute size (127->255)
                         */
                        $this->projectDB->updateAttribute($id, 'mimeType', Database::VAR_STRING, 255, true, false);
                        $this->projectDB->deleteCachedCollection($id);
                    } catch (\Throwable $th) {
                        Console::warning("'mimeType' from {$id}: {$th->getMessage()}");
                    }
                    break;
                case 'builds':
                    try {
                        /**
                         * Create 'size' attribute
                         */
                        $this->createAttributeFromCollection($this->projectDB, $id, 'size');
                        $this->projectDB->deleteCachedCollection($id);
                    } catch (\Throwable $th) {
                        Console::warning("'size' from {$id}: {$th->getMessage()}");
                    }

                    try {
                        /**
                         * Delete 'endTime' attribute (use startTime+duration if needed)
                         */
                        $this->projectDB->deleteAttribute($id, 'endTime');
                        $this->projectDB->deleteCachedCollection($id);
                    } catch (\Throwable $th) {
                        Console::warning("'endTime' from {$id}: {$th->getMessage()}");
                    }

                    try {
                        /**
                         * Rename 'outputPath' to 'path'
                         */
                        $this->projectDB->renameAttribute($id, 'outputPath', 'path');
                        $this->projectDB->deleteCachedCollection($id);
                    } catch (\Throwable $th) {
                        Console::warning("'path' from {$id}: {$th->getMessage()}");
                    }

                    try {
                        /**
                         * Create 'size'
                         */
                        $this->createAttributeFromCollection($this->projectDB, $id, 'size');
                        $this->projectDB->deleteCachedCollection($id);
                    } catch (\Throwable $th) {
                        Console::warning("'size' from {$id}: {$th->getMessage()}");
                    }
                    break;
                default:
                    break;
            }

            usleep(50000);
        }
    }

    /**
     * Fix run on each document
     *
     * @param \Utopia\Database\Document $document
     * @return \Utopia\Database\Document
     */
    protected function fixDocument(Document $document)
    {
        return $document;
    }
}
