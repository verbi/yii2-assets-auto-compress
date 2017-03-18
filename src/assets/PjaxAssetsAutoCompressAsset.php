<?php

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-assets-auto-compress/
 * @license https://opensource.org/licenses/GPL-3.0
 */
namespace verbi\yii2AssetsAutoCompress\assets;

use yii\web\AssetBundle;

class PjaxAssetsAutoCompressAsset extends AssetBundle {
    public $depends = [
        'verbi\yii2Helpers\widgets\assets\PjaxAsset',
    ];
    public $sourcePath = '@vendor/verbi/yii2-assets-auto-compress/src/assets/pjaxAssetsAutoCompressAsset';
    
    public $js = [
        'js/pjax-assets-auto-compress-asset.js',
    ];
}
