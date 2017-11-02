<?php


namespace SamIT\Yii2\StaticAssets;


class AssetManager extends \yii\web\AssetManager
{
    public function init()
    {
        parent::init();
        $this->hashCallback = StaticAssets::hashCallback();
    }


}