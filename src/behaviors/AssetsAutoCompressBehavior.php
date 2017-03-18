<?php

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-assets-auto-compress/
 * @license https://opensource.org/licenses/GPL-3.0
 */
namespace verbi\yii2AssetsAutoCompress\behaviors;

use verbi\yii2Helpers\behaviors\base\Behavior;

class AssetsAutoCompressBehavior extends Behavior {

    public $jsKeys;
    public $cssKeys;

    public function _getAssetsArray() {
        $array = [];
        
        if ($this->owner->jsKeys) {
            $array['loadedKeys']['js'] = $this->owner->jsKeys;
        } elseif ($this->owner->jsFiles) {
            foreach ($this->owner->jsFiles as $position => $jsfiles) {
                if ($jsfiles) {
                    $array[$position]['jsFiles'] = $jsfiles;
                }
            }
        }
        $scripts = [];
        if ($this->owner->js) {
            foreach ($this->owner->js as $position => $js) {
                if ($js) {
                    $array[$position]['js'] = implode("", $js);
                }
            }
        }
        if ($this->owner->cssKeys) {
            $array['loadedKeys']['css'] = $this->owner->cssKeys;
//                foreach ($this->cssKeys as $position => $cssKeys) {
//                    if ($cssKeys) {
//                        $array['cssKeys'] = $cssKeys;
//                    }
//                }
        } elseif ($this->owner->cssFiles) {
            foreach ($this->owner->cssFiles as $key => $cssFile) {
                if ($cssFile) {
                    $array['cssFiles'][$key] = $cssFile;
                }
            }
        }
        if ($this->owner->css) {
            foreach ($this->owner->css as $key => $css) {
                if ($css) {
                    $array['css'][$key] = $css;
                }
            }
        }
        return $array;
    }

}
