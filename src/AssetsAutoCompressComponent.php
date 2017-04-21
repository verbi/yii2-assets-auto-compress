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
use yii\web\JsExpression;
use yii\helpers\Url;
use yii\helpers\FileHelper;
use yii\helpers\ArrayHelper;
use yii\base\Event;
use yii\helpers\Inflector;
use verbi\yii2AssetsAutoCompress\libs\UglifyJs2;
use verbi\yii2AssetsAutoCompress\libs\UglifyCss;

class AssetsAutoCompressComponent extends \skeeks\yii2\assetsAuto\AssetsAutoCompressComponent {
    use \verbi\yii2Helpers\traits\ComponentTrait;

    public static $EVENT_AFTER_PROCESSING_KEYS = 'EVENT_AFTER_PROCESSING_KEYS';
    public $modelClass = '\verbi\yii2AssetsAutoCompress\models\AutoCompressAsset';
    public $saveData = true;
    /**
     * Enable compression and processing js before saving a file
     * @var bool
     */
    public $jsFileCompress = true;
    public $uglify = true;
    
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
                                    . '}'
                                    . 'dynamicScriptloader.getAssetsUrl=\''
                                    . \Yii::$app->getUrlManager()->createUrl([Inflector::camel2id($this->getComponentId()) . '/get-asset-urls'])
                                    . '\'';
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
            $model = $modelClass::find()->where($id)->one();
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
            if ($model->isNewRecord) {
                $model->contains = \json_encode($files);
                $bundles = \Yii::$app->getAssetManager()->bundles;
                $model->bundles = \json_encode(is_array($bundles)?array_keys($bundles):[]);
                $model->save();
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
        $fileName   =  md5( implode(array_keys($files)) . $this->getSettingsHash()) . '.js';
        $publicUrl  = \Yii::getAlias('@web/assets/js-compress/' . $fileName);

        $rootDir    = \Yii::getAlias('@webroot/assets/js-compress');
        $rootUrl    = $rootDir . '/' . $fileName;
        
        $excludedBundles = array_filter(\Yii::$app->getAssetManager()->bundles, function($bundle){
            return $bundle->hasMethod('getExcludeJs') && $bundle->getExcludeJs();
            return $bundle instanceOf \verbi\yii2Helpers\traits\assetBundles\ExcludableAssetBundleTrait && $bundle->getExcludeJs();
        });
        
//        $excludedBundleDepends = [];
//        foreach($excludedBaseBundles as $excludedBundle) {
//            $excludedBundleDepends = array_merge($excludedBundle->getAllDepends(), $excludedBundleDepends);
//        }
//        $excludedBundles = array_merge($excludedBundleDepends, $excludedBaseBundles);

        
        
        
        $am = \Yii::$app->getAssetManager();
        
        $excludedFiles = array_filter($files, function($file) use (&$excludedBundles, &$am) {
            foreach($excludedBundles as $bundle) {
                //die($bundle->baseUrl.'==='.$file);
                if ($bundle->baseUrl !== null && strpos($file, $bundle->baseUrl . '/') === 0) {
                    return true;
                    $asset = substr($file, strlin($file, $bundle->baseUrl . '/'));
                    $asset = $bundle->sourcePath . '/' . $asset;
                }
            }
            return false;
        },ARRAY_FILTER_USE_KEY);
//        $excludedFiles = [
//            "/goestingWeb/web/assets/e2bcba80/ckeditor.js" => isset($files["/goestingWeb/web/assets/e2bcba80/ckeditor.js"])?$files["/goestingWeb/web/assets/e2bcba80/ckeditor.js"]:null,
//        ];
        
        $filesDiff = \array_diff_key($files,$excludedFiles);
        if (!file_exists($rootUrl))
        {
            // If the uglifyer throws an exception, just proceed with the original logic
            try
            {
                $uglifyJs2 = new UglifyJs2();
                $filesToCompile = array_keys($filesDiff);
                if(!$this->jsFileRemouteCompile) {
                    $filesToCompile = array_filter($filesToCompile, function($fileCode) {
//                        die(print_r($fileCode,true));
                        return Url::isRelative($fileCode);
                    });
                }
                $content = $uglifyJs2->uglify(array_map(function($fileName){
                    return \Yii::getAlias('@webroot' . substr($fileName, strlen(\Yii::getAlias('@web'))));
                },$filesToCompile));
                if(true || $content) {
                    if (!is_dir($rootDir))
                    {
                        if (!FileHelper::createDirectory($rootDir, 0777))
                        {
                            $this->saveAssetContent(array_keys($files), 'js');
                            return $files;
                        }
                    }
                    
                    // Is this compression still usuful?
                    if ($this->jsFileCompress)
                    {
                        $content = \JShrink\Minifier::minify($content, ['flaggedComments' => $this->jsFileCompressFlaggedComments]);
                    }
                    
                    $file = fopen($rootUrl, "w");
                    fwrite($file, $content);
                    fclose($file);
                    
                    if (file_exists($rootUrl))
                    {
                        $publicUrl                  = $publicUrl . "?v=" . filemtime($rootUrl);
                        $resultFiles[$publicUrl]    = Html::jsFile($publicUrl, $this->jsOptions);
                        $this->saveAssetContent(array_keys($files), 'js');
                        return array_merge($excludedFiles, $resultFiles);
                    }
                }
            }
            catch(\Exception $e) {
            }
        }
        
        try{
            $jsFiles = parent::_processingJsFiles($filesDiff);
        }
        catch(\RuntimeException $e) {
            $this->jsFileCompress = false;
            $jsFiles = parent::_processingJsFiles($filesDiff);
        }
        
        $this->saveAssetContent(array_keys($files), 'js');
        return array_merge($excludedFiles, $jsFiles);
    }

    protected function _processingCssFiles($files = []) {
        $cssFiles = $this->__processCssFiles($files);
        $this->saveAssetContent(array_keys($files), 'css');
        return $cssFiles;
    }
    
    protected function __processCssFiles($files = []) {
        $fileName   =  md5( implode(array_keys($files)) . $this->getSettingsHash() ) . '.css';
        $publicUrl  = \Yii::getAlias('@web/assets/css-compress/' . $fileName);

        $rootDir    = \Yii::getAlias('@webroot/assets/css-compress');
        $rootUrl    = $rootDir . '/' . $fileName;

        if (file_exists($rootUrl))
        {
            $resultFiles        = [];

            foreach ($files as $fileCode => $fileTag)
            {
                if (Url::isRelative($fileCode))
                {

                } else
                {
                    if (!$this->cssFileRemouteCompile)
                    {
                        $resultFiles[$fileCode] = $fileTag;
                    }
                }

            }

            $publicUrl                  = $publicUrl . "?v=" . filemtime($rootUrl);
            $resultFiles[$publicUrl]    = Html::cssFile($publicUrl, $this->cssOptions);
            return $resultFiles;
        }

        //Reading the contents of the files
        try
        {
            $resultContent  = [];
            $resultFiles    = [];
            foreach ($files as $fileCode => $fileTag)
            {
                if (Url::isRelative($fileCode))
                {
                    $contentTmp         = trim($this->fileGetContents( Url::to(\Yii::getAlias($fileCode), true) ));

                    $fileCodeTmp = explode("/", $fileCode);
                    unset($fileCodeTmp[count($fileCodeTmp) - 1]);
                    $prependRelativePath = implode("/", $fileCodeTmp) . "/";

                    $contentTmp    = \Minify_CSS::minify($contentTmp, [
                        "prependRelativePath" => $prependRelativePath,

                        'compress'          => true,
                        'removeCharsets'    => true,
                        'preserveComments'  => false,
                    ]);

                    //$contentTmp = \CssMin::minify($contentTmp);

                    $resultContent[] = $contentTmp;
                } else
                {
                    if ($this->cssFileRemouteCompile)
                    {
                        //Пытаемся скачать удаленный файл
                        $resultContent[] = trim($this->fileGetContents( $fileCode ));
                    } else
                    {
                        $resultFiles[$fileCode] = $fileTag;
                    }
                }
            }
        } catch (\Exception $e)
        {
            \Yii::error($e->getMessage(), static::className());
            return $files;
        }

        if ($resultContent)
        {
            $content = implode($resultContent, "");
            if (!is_dir($rootDir))
            {
                if (!FileHelper::createDirectory($rootDir, 0777))
                {
                    return $files;
                }
            }

            if ($this->cssFileCompress)
            {
                $content = \CssMin::minify($content);
            }

            $page = \Yii::$app->request->absoluteUrl;
            $useFunction = function_exists('curl_init') ? 'curl extension' : 'php file_get_contents';
            $filesString = implode(', ', array_keys($files));

            \Yii::info("Create css file: {$publicUrl} from files: {$filesString} to use {$useFunction} on page '{$page}'", static::className());


            $file = fopen($rootUrl, "w");
            fwrite($file, $content);
            fclose($file);
        }


        if (file_exists($rootUrl))
        {
            $publicUrl                  = $publicUrl . "?v=" . filemtime($rootUrl);
            $resultFiles[$publicUrl]    = Html::cssFile($publicUrl, $this->cssOptions);
            return $resultFiles;
        } else
        {
            return $files;
        }
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
                $jsKeys = is_array($view->jsKeys) ? $view->jsKeys : [];
                $jsKeys = array_merge($jsKeys, [$this->getAssetContentKey($keys)]);
                $view->jsKeys = $jsKeys;
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

//        foreach ($view->assetBundles as $pos => $bundle)
//        {
//            if ($bundle)
//            {
//                $view->jsFiles[$pos] = $this->_processingJsFiles($files);
//            }
//        }

        parent::_processing($view);
    }

    public function _pjax_processing(Pjax $pjax) {
        $this->_processAssetFiles($pjax);
    }

    public function _processAssetFiles($view) {
        $view->attachBehavior(AssetsAutoCompressBehavior::className(), AssetsAutoCompressBehavior::className());
        $this->_processingKeys($view);
        if ($view->jsFiles && $this->jsFileCompile) {
            \Yii::beginProfile('Compress js files');
            foreach ($view->jsFiles as $pos => $files) {
                if ($files) {
                    $view->jsFiles[$pos] = $this->_processingJsFiles($files);
                }
            }
            \Yii::endProfile('Compress js files');
        }

        if ($view->js && $this->jsCompress) {
            \Yii::beginProfile('Compress js code');
            foreach ($view->js as $pos => $parts) {
                if ($parts) {
                    $view->js[$pos] = $this->_processingJs($parts);
                }
            }
            \Yii::endProfile('Compress js code');
        }


        if ($view->cssFiles && $this->cssFileCompile) {
            \Yii::beginProfile('Compress css files');
            $view->cssFiles = $this->_processingCssFiles($view->cssFiles);
            \Yii::endProfile('Compress css files');
        }

        if ($view->css && $this->cssCompress) {
            \Yii::beginProfile('Compress css code');

            $view->css = $this->_processingCss($view->css);

            \Yii::endProfile('Compress css code');
        }
        if ($view->css && $this->cssCompress) {
            \Yii::beginProfile('Compress css code');

            $view->css = $this->_processingCss($view->css);

            \Yii::endProfile('Compress css code');
        }


        if ($view->cssFiles && $this->cssFileBottom) {
            \Yii::beginProfile('Moving css files bottom');

            if ($this->cssFileBottomLoadOnJs) {
                \Yii::beginProfile('load css on js');

                $cssFilesString = implode("", $view->cssFiles);
                $view->cssFiles = [];

                $script = Html::script(new JsExpression(<<<JS
        document.write('{$cssFilesString}');
JS
                ));

                if (ArrayHelper::getValue($view->jsFiles, View::POS_END)) {
                    $view->jsFiles[View::POS_END] = ArrayHelper::merge($view->jsFiles[View::POS_END], [$script]);
                } else {
                    $view->jsFiles[View::POS_END][] = $script;
                }

                \Yii::endProfile('load css on js');
            } else {
                if (ArrayHelper::getValue($view->jsFiles, View::POS_END)) {
                    $view->jsFiles[View::POS_END] = ArrayHelper::merge($view->cssFiles, $view->jsFiles[View::POS_END]);
                } else {
                    $view->jsFiles[View::POS_END] = $view->cssFiles;
                }

                $view->cssFiles = [];
            }

            \Yii::endProfile('Moving css files bottom');
        }
    }
    
    public function addControllerToMap($app) {
        $app->controllerMap = array_merge([
            Inflector::camel2id($this->getComponentId()) => [
                'class' => 'verbi\yii2AssetsAutoCompress\controllers\AssetsAutoCompressController',
                'componentId' => $this->getComponentId(),
                'modelClass' => $this->modelClass,
                ]
        ],$app->controllerMap);
    }
}
