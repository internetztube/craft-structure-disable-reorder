<?php

namespace internetztube\structureDisableReorder;

use Craft;
use craft\base\Element;
use craft\base\Plugin;
use craft\elements\Entry;
use craft\events\ElementEvent;
use craft\events\ElementStructureEvent;
use craft\events\TemplateEvent;
use craft\services\Elements;
use craft\services\Plugins;
use craft\events\PluginEvent;
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

        Event::on(Entry::class, Entry::EVENT_AFTER_MOVE_IN_STRUCTURE, function (ElementStructureEvent $elementStructureEvent) {
            global $internetztubeStructureDisableReorderSortStructureJobIsRunning;
            if (!$internetztubeStructureDisableReorderSortStructureJobIsRunning) {
                /** @var Entry $entry */
                $entry = $elementStructureEvent->sender;
                if ($entry->getIsDraft()) { return $elementStructureEvent; }
                $queue = Craft::$app->getQueue();
                $queue->push(new SortStructureJob([
                    'description' => sprintf('Updating the structure sorting of %s', $entry->section->handle),
                    'canonicalId' => $entry->canonicalId,
                    'siteId' => $entry->site->id,
                ]), 10);
            }
            return $elementStructureEvent;
        });

        Event::on(View::class, View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE, function (TemplateEvent $event) {
            if ($event->template === 'entries') {
                Craft::$app->view->registerAssetBundle(MainAssetBundle::class);
            }
            return $event;
        });
    }
}
