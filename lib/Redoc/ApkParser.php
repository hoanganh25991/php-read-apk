<?php
namespace Redoc;

use ApkParser\Manifest;
use ApkParser\Stream;
use ApkParser\XmlParser;
use Exception;

class ApkParser{
    private $apkFilePath;
    private $outputDir;
    private $options;

    const UN_HANDLE = "sorry, we still not handle this situation";

    const AAPT = "aapt";
    const ZIP = "7z";
    const MANIFEST = "AndroidManifest.xml";
    const PARSE_UPDATED_FILE = "Only parse updated file";

    const OUTPUT_DIR = "apk-info";

    const APP_ICON = "app-icon.png";

    const VERSION_NAME = "versionName";
    const VERSION_CODE = "versionCode";
    const PACKAGE_NAME = "packageName";
    const MIN_SDK = "minSDK";
    const MIN_SDK_LEVEL = "minSDKLevel";
    const TARGET_SDK = "targetSDK";
    const DATE = "date";
    const ICON_PATH = "iconPath";
    const PLAT_FORM = "platForm";

    /** @var  Icon */
    private $icon;
    /** @var  Manifest */
    private $manifest;
    private $parsed;
    private $extractFolder;

    public function __construct($apkFilePath, $outputDir = "", $options = array()){
        //check apk file exist
        if(!file_exists($apkFilePath)){
            $errMsg = sprintf("apk file not exist\nfile path: %s", $apkFilePath);
            throw new Exception($errMsg);
        }
        $this->apkFilePath = $apkFilePath;

        //check output is directory
        $this->outputDir = self::OUTPUT_DIR;
        if(!empty($outputDir)){
            $this->outputDir = $this->removeSpace($outputDir);
        }

        if(!is_dir($this->outputDir) && !file_exists($this->outputDir)){
            mkdir($this->outputDir, 0777, true);
        }

        $this->options = $options;
        $this->parsed = false;

        $md5Input = $this->apkFilePath + filemtime($this->apkFilePath);
        $this->extractFolder = $this->outputDir . DIRECTORY_SEPARATOR . md5($md5Input);
    }


    public function parse(){
        $this->checkParse();

        if($this->parsed){
            $androidManifestPath = $this->extractFolder . DIRECTORY_SEPARATOR . self::MANIFEST;
            $iconPath = $this->extractFolder . DIRECTORY_SEPARATOR . self::APP_ICON;
            if(!file_exists($androidManifestPath) || !file_exists($iconPath)){
                return false;
            }
        }


        if(!$this->parsed){
            if(!is_dir($this->extractFolder) && !file_exists($this->extractFolder)){
                mkdir($this->extractFolder, 0777, true);
            }

            //using aapt to get WHERE icon locate in apk file
            $aaptCommand = "aapt d badging {$this->apkFilePath}";
            $badging = $this->cmd($aaptCommand);

            if(!$badging){
                return false;
            }

            //read out icon-path from $badging
            $badgingString = implode("\n", $badging);
            $pattern_icon = "/icon='(.+)'/isU";
            preg_match($pattern_icon, $badgingString, $m);
            $iconPath = end($m);

            //unzip apk, get out AndroidManifest.xml, icon base on $iconPath
            $zipCommand = "7z x {$this->apkFilePath} -aoa -o{$this->extractFolder} {$iconPath} AndroidManifest.xml";
            $result = $this->cmd( $zipCommand);
            if(!$result){
                return false;
            }
            //if above command success
            $androidManifestPath = $this->extractFolder . DIRECTORY_SEPARATOR . self::MANIFEST;

            //copy icon to $extractFolder, under APP_ICON_NAME
            $copiedIconPath = $this->extractFolder . DIRECTORY_SEPARATOR . self::APP_ICON;
            $copyStatus = copy($this->extractFolder . DIRECTORY_SEPARATOR . $iconPath, $copiedIconPath);
            if($copyStatus === 1){
                $iconPath = $copiedIconPath;
            }else{
                return false;
            }

        }
        //now we have icon, AndroidManifest.xml
        if(isset($iconPath) && isset($androidManifestPath)){
            $this->icon = new Icon($iconPath);

            $resource = fopen($this->extractFolder . DIRECTORY_SEPARATOR . self::MANIFEST, "r");
            $this->manifest = new Manifest(new XmlParser(new Stream($resource)));
            fclose($resource);
            return true;
        }
        return false;
    }

    /**
     * @param string $command
     * @throws Exception
     * @return string $out
     */
    private function cmd($command){
        exec($command, $out, $resultCode);
        if($resultCode != 0){
            return false;
        }
        return $out;
    }

    public function getIcon(){
        return $this->icon;

    }

    public function getManifest(){
        return $this->manifest;

    }

    public function getBasicInfo(){
        //in case parsed = false, we already have $this->manifest, $this->icon
        $info = array();
        $info[self::VERSION_NAME] = $this->manifest->getVersionName();
        $info[self::VERSION_CODE] = $this->manifest->getVersionCode();
        $info[self::PACKAGE_NAME] = $this->manifest->getPackageName();
        $info[self::MIN_SDK] = $this->manifest->getMinSdk();
        $info[self::MIN_SDK_LEVEL] = $this->manifest->getMinSdkLevel();
        $dateString = date("dMY H:iA", filemtime($this->apkFilePath));
        $info[self::DATE] = $dateString;
        $info[self::ICON_PATH] = $this->icon->getPath();
        $info[self::PLAT_FORM] = $this->manifest->getMinSdk()->platform;

        return $info;
    }

    private function removeSpace($name){
        return preg_replace('/\s+/', '-', $name);
    }

    /**
     * Basically, file name depend on created-date
     * if created-date changed > file name changed
     * folder already exist > parsed
     * folder not exist > !parsed
     */
    protected function checkParse(){
        if(is_dir($this->extractFolder) || file_exists($this->extractFolder)){
            $this->parsed = true;
        }
    }
}