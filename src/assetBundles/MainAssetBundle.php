<?php

namespace internetztube\structureDisableReorder\assetBundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class MainAssetBundle extends AssetBundle
{
    public function init()
    {
        $this->sourcePath = '@internetztube/structureDisableReorder/resources/main/';
        $this->depends = [CpAsset::class];
        $this->css = ['main.css'];
        parent::init();
    }
}
