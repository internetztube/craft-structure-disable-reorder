<?php

namespace internetztube\structureDisableReorder\jobs;

use Craft;
use craft\elements\Entry;
use craft\events\ElementStructureEvent;
use craft\models\Structure;
use craft\queue\BaseJob;
use craft\services\Structures;
use yii\base\Event;

class SortStructureJob extends BaseJob
{
    public $canonicalId = null;
    public $siteId = null;

    public function execute($queue)
    {
        global $internetztubeStructureDisableReorderSortStructureJobIsRunning;
        $internetztubeStructureDisableReorderSortStructureJobIsRunning = true;

        $entry = Entry::find()->id($this->canonicalId)->siteId($this->siteId)->one();
        $siblings = collect($entry->getSiblings()->level($entry->level)->all());
        $siblings->push($entry);
        $parent = $entry->getParent();
        $level = $entry->level;

        $structureId = $entry->structureId;
        $structuresService = \Craft::$app->getStructures();

        $prevElement = null;
        $count = $siblings->count();
        $siblings->sortBy('title')->values()->each(function(Entry $entry, int $index) use ($parent, $structureId, $structuresService, $level, $count, $queue, &$prevElement) {
            $queue->setProgress($index * 100 / $count);
            // Don't move entry, when it's no longer has the same level or parent.
            if ($entry->level !== $level || $entry->getParent()->canonicalUid !== $parent->canonicalUid) { return; }
            if ($index === 0 && !$parent) {
                $structuresService->prependToRoot($structureId, $entry, Structures::MODE_UPDATE);
            } elseif (!$prevElement) {
                $structuresService->prepend($structureId, $entry, $parent, Structures::MODE_UPDATE);
            } else {
                $structuresService->moveAfter($structureId, $entry, $prevElement, Structures::MODE_UPDATE);
            }
            $prevElement = $entry;
        });
    }
}
