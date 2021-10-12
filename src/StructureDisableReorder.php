<?php

namespace internetztube\structureDisableReorder;


use Craft;
use craft\base\Plugin;
use craft\elements\Entry;
use craft\events\ElementStructureEvent;
use craft\events\TemplateEvent;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\View;
use internetztube\structureDisableReorder\assetBundles\MainAssetBundle;
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
            if (!$isSuitable) { return $elementStructureEvent; }
            echo "not-allowed";
            die();
        });

        Event::on(View::class, View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE, function (TemplateEvent $event) {
            if ($event->template !== 'entries') { return $event; }
            Craft::$app->view->registerAssetBundle(MainAssetBundle::class);
            return $event;
        });
    }
}
