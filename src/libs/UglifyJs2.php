<?php

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-assets-auto-compress/
 * @license https://opensource.org/licenses/GPL-3.0
 */
namespace verbi\yii2AssetsAutoCompress\libs;

class UglifyJs2 {
    public function uglify(array $files, $outputFilename, array $options = []) {
        foreach($files as $filename) {
            if(!is_readable($filename)) {
                throw new \Exception("Filename " . $filename . " is not readable");
            }
        }
        $safeOutputFilename = escapeshellarg($outputFilename);
        $optionsString = $this->validateOptions($options);
        $fileNames = implode(' ', array_map('escapeshellarg', $files));
        $commandString = self::$location . " {$fileNames} --output {$safeOutputFilename} {$optionsString}";
        exec($commandString, $output, $returnCode);
        if($returnCode !== 0) {
            throw new UglifyJs2Exception("Failed to run uglifyjs, something went wrong... command: " . $commandString);
        }
        return $output;
    }
}