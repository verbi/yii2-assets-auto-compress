<?php

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-assets-auto-compress/
 * @license https://opensource.org/licenses/GPL-3.0
 */
namespace verbi\yii2AssetsAutoCompress\controllers;

use yii\rest\Controller;
use yii\base\InvalidConfigException;

class AssetsAutoCompressController extends Controller {
    use \verbi\yii2Helpers\traits\ComponentTrait;
    use \verbi\yii2Helpers\traits\ControllerTrait;
    
    public $modelClass = '\verbi\yii2AssetsAutoCompress\models\AutoCompressAsset';
    public $componentId;

//    public function behaviors()
//    {
//        $behaviors = parent::behaviors();
//        unset($behaviors['access']);
//        return $behaviors;
//    }

    public function loadModel($id = null) {
        $modelClass = $this->modelClass;
        if ($id !== null) {
            $model = $modelClass::find()->where($id)->one();
            if ($model === null) {
                throw new \yii\web\NotFoundHttpException('Not Found');
            }
            return $model;
        }
        return new $modelClass;
    }

    public function getAssetFilesFromKeys(&$type, Array $keys, Array $ignoreFiles = []) {
        $array = [];
        foreach ($keys as $key) {
            $model = $this->loadModel([
                'key' => $key,
                'type' => $type,
            ]);
            foreach(json_decode($model->bundles) as $bundleName) {
                \Yii::$app->getAssetManager()->getBundle($bundleName);
            }
            $array = array_merge($array,json_decode($model->contains,true));
        }
        return array_diff($array, $ignoreFiles);
    }

    public function actionGetAssetUrls(Array $loadKeys = [], Array $loaded = []) {
        $object = $this;
        $loadedFiles = $loaded;
        array_walk($loadedFiles,function(&$keys, $type) use ($object) {
            $keys = $object->getAssetFilesFromKeys($type, $keys);
        });
        $filesToLoad = $loadKeys;
        array_walk($filesToLoad, function(&$keys, $type) use ($object, $loadedFiles) {
            $keys = $object->getAssetFilesFromKeys($type, $keys, isset($loadedFiles[$type])?$loadedFiles[$type]:[]);
        });
        $component = $this->getComponent(false);
        if($component) {
            $view = \Yii::$app->getView();
            
            $loadedAndLoadingJsFiles = array_merge(isset($filesToLoad['js'])?$filesToLoad['js']:[],isset($loadedFiles['js'])?$loadedFiles['js']:[]);
            $view->jsFiles = [array_combine($loadedAndLoadingJsFiles,$loadedAndLoadingJsFiles)];
            $loadedAndLoadingCssFiles = array_merge(isset($filesToLoad['css'])?$filesToLoad['css']:[],isset($loadedFiles['css'])?$loadedFiles['css']:[]);
            $view->cssFiles = array_combine($loadedAndLoadingCssFiles,$loadedAndLoadingCssFiles);
            $component->_processAssetFiles($view);
            $keys = [];
            if($view->jsKeys && sizeof($view->jsKeys))
            {
                $keys['js'] = $view->jsKeys;
            }
            if($view->cssKeys && sizeof($view->cssKeys))
            {
                $keys['css'] = $view->cssKeys;
            }
            
            $view->jsFiles = [sizeof($filesToLoad['js'])?array_combine($filesToLoad['js'],$filesToLoad['js']):[]];
            $view->cssFiles = sizeof($filesToLoad['css'])?array_combine($filesToLoad['css'],$filesToLoad['css']):[];
            $component->_processAssetFiles($view);
            
            $jsFiles = [];
            array_walk($view->jsFiles, function(&$item, $key) use (&$jsFiles) {
                $jsFiles = array_merge($jsFiles, $item);
            });
            $filesToLoad['js'] = array_keys($jsFiles);
            $filesToLoad['css'] = array_keys($view->cssFiles);
            if(sizeof($filesToLoad['js'])) {
                if(isset($keys['js']) && sizeof($keys['js'])) {
                    $filesToLoad['keys']['js'] = $keys['js'];
                }
            }
            else{
                unset($filesToLoad['js']);
            }
            if(sizeof($filesToLoad['css'])) {
                if(isset($keys['css']) && sizeof($keys['css'])) {
                    $filesToLoad['keys']['css'] = $keys['css'];
                }
            }
            else {
                unset($filesToLoad['css']);
            }
        }
        return $filesToLoad;
    }

    public function getComponent($throwException = true) {
        if($this->componentId) {
            return \Yii::$app->get($this->componentId, $throwException);
        }
        if($throwException) {
            throw new InvalidConfigException("Unknown component ID: " . $this->componentId);
        }
        return null;
    }
}
