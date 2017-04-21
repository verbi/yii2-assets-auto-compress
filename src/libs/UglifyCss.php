<?php

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-assets-auto-compress/
 * @license https://opensource.org/licenses/GPL-3.0
 */
namespace verbi\yii2AssetsAutoCompress\libs;

class UglifyCss {
    public static $location = '/vendor/npm/uglifycss/uglifycss';
    
    public function uglify(array $files, array $options = [
    ]) {
        $basePath = \Yii::$app->basePath;
        //die(\Yii::$app->baseUri);
        
        foreach($files as $filename) {
            
            if(!is_readable($filename)) {
                throw new \RuntimeException("Filename '" . $filename . "' is not readable");
            }
        }
        $optionsString = $this->createOptionsString($options);
        $fileNames = implode(' ', array_map('escapeshellarg', $files));
        $commandString = $basePath.self::$location . " {$fileNames} {$optionsString}";
        exec($commandString, $output, $returnCode);
        if($returnCode !== 0) {
            throw new \RuntimeException("Failed to run uglifycss, something went wrong... command: " . $commandString);
        }
        return implode('',$output);
    }
    
    public function createOptionsString($options) {
        $str = '';
        foreach($options as $optionName => $optionValue) {
            if(is_numeric($optionName)) {
                $str .= ' --' . $optionValue;
            }
            else {
                $str .= ' --' . $optionName . '=' . is_numeric($optionValue) ? $optionValue : '\'' . addSlashes($optionValue) . '\'';
            }
        }
        return $str;
    }
}