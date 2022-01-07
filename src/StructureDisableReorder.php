<?php

namespace internetztube\structureDisableReorder;

use Craft;
use craft\base\Plugin;
use craft\elements\Entry;
use craft\events\ElementStructureEvent;
use craft\events\ModelEvent;
use craft\events\TemplateEvent;
use craft\web\View;
use internetztube\structureDisableReorder\assetBundles\MainAssetBundle;
use internetztube\structureDisableReorder\jobs\SortStructureJob;
use yii\base\Event;


class StructureDisableReorder extends Plugin
{
    public static $plugin;
    public $schemaVersion = '1.0.0';
    public $hasCpSettings = false;
    public $hasCpSection = false;

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(Entry::class, Entry::EVENT_BEFORE_MOVE_IN_STRUCTURE, function (ElementStructureEvent $elementStructureEvent) {
            $isSuitable = \Craft::$app->controller->id === 'structures'
                && \Craft::$app->controller->action->id === 'move-element'
                && \Craft::$app->request->isCpRequest;
            if ($isSuitable) { echo "not-allowed" && die(); }
            return $elementStructureEvent;
        });


        $getEntryIdentifier = function (Entry $entry): string {
            $result = ['id' => $entry->id, 'title' => $entry->title, 'parentId' => $entry->getParent()->id ?? null];
            return json_encode($result);
        };

        $entryIdentifierList = [];
        Event::on(Entry::class, Entry::EVENT_AFTER_SAVE, function (ModelEvent $event) use (&$entryIdentifierList, $getEntryIdentifier) {
            global $internetztubeStructureDisableReorderSortStructureJobIsRunning;
            if (!$internetztubeStructureDisableReorderSortStructureJobIsRunning) {
                /** @var Entry $entry */
                $entry = $event->sender;
                if ($entry->getIsDraft()) { return; }
                $entryIdentifier = $getEntryIdentifier($entry);
                // Don't push task into queue, when `id`, `parent` and `title` have not changed.
                if (in_array($entryIdentifier, $entryIdentifierList)) { return; }
                $job = new SortStructureJob([
                    'description' => sprintf('Updating the structure sorting of %s', $entry->section->handle),
                    'canonicalId' => $entry->canonicalId,
                    'siteId' => $entry->site->id,
                ]);
                Craft::$app->getQueue()->push($job);
            }
        });

        Event::on(Entry::class, Entry::EVENT_BEFORE_SAVE, function (ModelEvent $event) use (&$entryIdentifierList, $getEntryIdentifier) {
            /** @var Entry $entry */
            $entry = $event->sender;
            $notSavedEntry = Craft::$app->getEntries()->getEntryById($entry->id, $entry->siteId);
            $entryIdentifierList[] = $getEntryIdentifier($notSavedEntry);
        });

        Event::on(View::class, View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE, function (TemplateEvent $event) {
            if ($event->template === 'entries') {
                Craft::$app->view->registerAssetBundle(MainAssetBundle::class);
            }
            return $event;
        });
    }
}
