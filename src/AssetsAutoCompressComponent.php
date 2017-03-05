<?php
/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
namespace verbi\yii2AssetsAutoCompress;

class AssetsAutoCompressComponent extends skeeks\yii2\AssetsAutoCompressComponent
{
    protected function _processingJsFiles($files = [])
    {
        ksort($files);
        return parent::_processingJsFiles($files);
    }
    
    protected function _processingCssFiles($files = [])
    {
        ksort($files);
        return parent::_processingCssFiles($files);
    }
}