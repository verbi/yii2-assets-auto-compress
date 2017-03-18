<?php

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-assets-auto-compress/
 * @license https://opensource.org/licenses/GPL-3.0
 */

namespace verbi\yii2AssetsAutoCompress\controllers;

use verbi\yii2WebController\Controller;

class AssetsAutoCompressController extends \yii\rest\Controller {

    public $modelClass = '\verbi\yii2AssetsAutoCompress\models\AutoCompressAsset';

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
                throw new \yii\web\NotFoundHttpException\NotFoundHttpException;
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
        

        return $filesToLoad;
    }

}
