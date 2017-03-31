<?php

namespace verbi\yii2AssetsAutoCompress\models;

use Yii;

/**
 * This is the model class for table "auto_compress_asset".
 *
 * @property string $type
 * @property string $key
 * @property string $contains
 * @property string $bundles
 */
class AutoCompressAsset extends \verbi\yii2ExtendedActiveRecord\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'auto_compress_asset';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type', 'key', 'contains', 'bundles'], 'required'],
            [['contains', 'bundles'], 'string'],
            [['type', 'key'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'type' => 'Type',
            'key' => 'Key',
            'contains' => 'Contains',
            'bundles' => 'Bundles',
        ];
    }
}
