<?php

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-assets-auto-compress/
 * @license https://opensource.org/licenses/GPL-3.0
 */

namespace verbi\yii2AssetsAutoCompress;

use \verbi\yii2Helpers\widgets\Pjax;
use verbi\yii2Helpers\events\GeneralFunctionEvent;
use yii\web\Response;
use verbi\yii2Helpers\Html;
use verbi\yii2WebView\web\View;
use verbi\yii2AssetsAutoCompress\behaviors\AssetsAutoCompressBehavior;
use verbi\yii2AssetsAutoCompress\assets\PjaxAssetsAutoCompressAsset;
//use verbi\yii2AssetsAutoCompress\models\AutoCompressAsset;
use yii\web\Application;
use yii\web\JsExpression;
use yii\helpers\Url;
use yii\helpers\Json;
use yii\helpers\FileHelper;
use yii\helpers\ArrayHelper;
use yii\base\Event;

class AssetsAutoCompressComponent extends \skeeks\yii2\assetsAuto\AssetsAutoCompressComponent {

    public static $EVENT_AFTER_PROCESSING_KEYS = 'EVENT_AFTER_PROCESSING_KEYS';
    public $modelClass = '\verbi\yii2AssetsAutoCompress\models\AutoCompressAsset';
    public $saveData = true;
    public $controllerMap = [
        'assets-auto-compress' => 'verbi\yii2AssetsAutoCompress\controllers\AssetsAutoCompressController',
    ];

    public function bootstrap($app) {
        if ($app instanceof \yii\web\Application) {
            $compressComponent = $this;
            $app->view->on(Pjax::EVENT_END_PJAX_HTML, function(GeneralFunctionEvent $e) use ($compressComponent) {
                /**
                 * @var $pjax Pjax
                 */
                $pjax = $e->sender;
                if ($compressComponent->enabled && $pjax instanceof Pjax
                //&& $app->response->format == Response::FORMAT_HTML
                //&& !$app->request->isAjax
                //&& !$app->request->isPjax
                ) {
                    \Yii::beginProfile('Compress assets for pjax');
                    $compressComponent->_pjax_processing($pjax);
                    \Yii::endProfile('Compress assets for pjax');
                }

                if ($compressComponent->enabled) {
                    $pjax->jsFiles = null;
                }
            });
            
            
            

            $app->view->on(View::EVENT_END_BODY, function(Event $e) use ($app) {
                /**
                 * @var $view View
                 */
                $view = $e->sender;

                if ($this->enabled && $view instanceof View && $app->response->format == Response::FORMAT_HTML && !$app->request->isAjax && !$app->request->isPjax) {
                    if ($view->getAssetManager()->bundles !== false && isset($view->getAssetManager()->bundles['verbi\yii2Helpers\widgets\assets\PjaxAsset'])) {
                        PjaxAssetsAutoCompressAsset::register($view);
                        
                        
                        $view->on('EVENT_AFTER_PROCESSING_KEYS', function($event) use ($view) {
                            $includes = [];
                            if($view->jsKeys) {
                                $includes['loadedKeys']['js'] = $view->jsKeys;
                            }
                            if($view->cssKeys) {
                                $includes['loadedKeys']['css'] = $view->cssKeys;
                            }
                            if(sizeof($includes)) {
                                $js = 'var dynamicScriptloader=new PjaxDynamicScriptLoader();'
                                    . 'if (typeof dynamicScriptloader.addLoadedFiles == \'function\') {'
                                        . 'dynamicScriptloader.addLoadedFiles(' . json_encode($includes) . ');'
                                    . '}';
                                $view->registerJs(new JsExpression($js));
                            }
                        });
                        
                        
                        
                        
                        
                        
                        
                        
                    }
                }
            });

            //Html compressing
//            $app->response->on(\yii\web\Response::EVENT_BEFORE_SEND, function (\yii\base\Event $event) use ($app)
//            {
//                $response = $event->sender;
//
//                if ($this->enabled && $this->htmlCompress && $response->format == \yii\web\Response::FORMAT_HTML && !$app->request->isAjax && !$app->request->isPjax)
//                {
//                    if (!empty($response->data))
//                    {
//                        $response->data = $this->_processingHtml($response->data);
//                    }
//
//                    if (!empty($response->content))
//                    {
//                        $response->content = $this->_processingHtml($response->content);
//                    }
//                }
//            });
            
            
            
        }

        $this->addControllerToMap($app);

        return parent::bootstrap($app);
    }

    public function loadModel($id = null) {
        $modelClass = $this->modelClass;
        if ($id !== null) {
            $model = $modelClass::find($id)->one();
            if ($model !== null) {
                return $model;
            }
        }
        $model = new $modelClass;
        $model->setAttributes($id);
        return $model;
    }

    protected function getAssetContentKey($files) {
        sort($files);
        return md5(implode($files) . $this->getSettingsHash());
    }

    protected function getAssetContentId($files, $id = [
        'type' => 'js',
    ]) {
        $id['key'] = $this->getAssetContentKey($files);
        return $id;
    }

    protected function saveAssetContent($files, $type = 'js') {
        if ($this->saveData) {
            $id = $this->getAssetContentId($files, [ 'type' => $type,]);
            $model = $this->loadModel($id);
            if ($model->isNewrecord) {
                $model->contains = \json_encode($files);
                return $model->save();
            }
            return true;
        }
        return false;
    }

    protected function getAssetcontent($id) {
        $model = $this->loadModel($id);
        return $model->contains ? json_decode($model->contains, true) : [];
    }

    protected function _processingJsFiles($files = []) {
        $jsFiles = parent::_processingJsFiles($files);
        $this->saveAssetContent(array_keys($files), 'js');
        return $jsFiles;
    }

    protected function _processingCssFiles($files = []) {
        $cssFiles = parent::_processingCssFiles($files);
        $this->saveAssetContent(array_keys($files), 'css');
        return $cssFiles;
    }

    protected function _processingKeys($view) {
        if (is_array($view->jsFiles)) {
            $keys = [];
            foreach ($view->jsFiles as $pos => $files) {
                if ($files) {
                    $keys = array_merge($keys, array_keys($files));
                }
            }
            if (sizeof($keys)) {
                $id = $this->getAssetContentId($keys, [ 'type' => 'js',]);
//                    if($this->getAssetcontent($id)) {
                $jsKeys = is_array($view->jsKeys) ? $view->jsKeys : [];
                $jsKeys = array_merge($jsKeys, [$this->getAssetContentKey($keys)]);
//                        die(print_r($jsKeys,true));
                $view->jsKeys = $jsKeys;
//                    }
            }
        }
        if ($view->cssFiles && $this->cssFileCompile) {
            $keys = array_keys($view->cssFiles);
            $id = $this->getAssetContentId($keys, [ 'type' => 'css',]);
//                if($this->getAssetcontent($id)) {
            $view->cssKeys = array_merge(is_array($view->cssKeys) ? $view->cssKeys : [], [$this->getAssetContentKey($keys)]);
//                }
        }
//            if($view->cssKeys && $view->jsKeys) {
//                $js = '';
//                new JsExpression($js);
//            }
    }

    protected function _processing(\yii\web\View $view) {
        if ($view instanceof View) {
            $view->attachBehavior(AssetsAutoCompressBehavior::className(), AssetsAutoCompressBehavior::className());
            $this->_processingKeys($view);
            $view->trigger(static::$EVENT_AFTER_PROCESSING_KEYS);




//            foreach(\Yii::$app->getAssetManager() as $view) {
//                if($view->cssKeys && $view->jsKeys) {
//                    $js = '';
//                    new JsExpression($js);
//                }
//            }
        }

        //TODO: add keys to pjax (probably using an event or something)

        parent::_processing($view);
    }

    public function _pjax_processing(Pjax $pjax) {
        $pjax->attachBehavior(AssetsAutoCompressBehavior::className(), AssetsAutoCompressBehavior::className());
        $this->_processingKeys($pjax);
        if ($pjax->jsFiles && $this->jsFileCompile) {
            \Yii::beginProfile('Compress js files');
            foreach ($pjax->jsFiles as $pos => $files) {
                if ($files) {
                    $pjax->jsFiles[$pos] = $this->_processingJsFiles($files);
                }
            }
            \Yii::endProfile('Compress js files');
        }

        if ($pjax->js && $this->jsCompress) {
            \Yii::beginProfile('Compress js code');
            foreach ($pjax->js as $pos => $parts) {
                if ($parts) {
                    $pjax->js[$pos] = $this->_processingJs($parts);
                }
            }
            \Yii::endProfile('Compress js code');
        }


        if ($pjax->cssFiles && $this->cssFileCompile) {
            \Yii::beginProfile('Compress css files');
            $pjax->cssFiles = $this->_processingCssFiles($pjax->cssFiles);
            \Yii::endProfile('Compress css files');
        }

        if ($pjax->css && $this->cssCompress) {
            \Yii::beginProfile('Compress css code');

            $pjax->css = $this->_processingCss($pjax->css);

            \Yii::endProfile('Compress css code');
        }
        if ($pjax->css && $this->cssCompress) {
            \Yii::beginProfile('Compress css code');

            $pjax->css = $this->_processingCss($pjax->css);

            \Yii::endProfile('Compress css code');
        }


        if ($pjax->cssFiles && $this->cssFileBottom) {
            \Yii::beginProfile('Moving css files bottom');

            if ($this->cssFileBottomLoadOnJs) {
                \Yii::beginProfile('load css on js');

                $cssFilesString = implode("", $pjax->cssFiles);
                $pjax->cssFiles = [];

                $script = Html::script(new JsExpression(<<<JS
        document.write('{$cssFilesString}');
JS
                ));

                if (ArrayHelper::getValue($pjax->jsFiles, View::POS_END)) {
                    $pjax->jsFiles[View::POS_END] = ArrayHelper::merge($pjax->jsFiles[View::POS_END], [$script]);
                } else {
                    $pjax->jsFiles[View::POS_END][] = $script;
                }

                \Yii::endProfile('load css on js');
            } else {
                if (ArrayHelper::getValue($pjax->jsFiles, View::POS_END)) {
                    $pjax->jsFiles[View::POS_END] = ArrayHelper::merge($pjax->cssFiles, $pjax->jsFiles[View::POS_END]);
                } else {
                    $pjax->jsFiles[View::POS_END] = $pjax->cssFiles;
                }

                $pjax->cssFiles = [];
            }

            \Yii::endProfile('Moving css files bottom');
        }
    }

    public function addControllerToMap($app) {
        $app->controllerMap = array_merge($this->controllerMap,$app->controllerMap);
    }
}
