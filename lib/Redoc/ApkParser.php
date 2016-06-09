<?php
namespace Redoc;

use ApkParser\Manifest;
use ApkParser\Stream;
use ApkParser\XmlParser;
use Exception;

class ApkParser{
    protected $apkFilePath;
    protected $outputDir;
//    protected $currentApkFileTime;
    protected $options;

    const UN_HANDLE = "sorry, we still not handle this situation";

    const AAPT = "aapt";
    const ZIP = "7z";
    const MANIFEST = "AndroidManifest.xml";
    const PARSE_UPDATED_FILE = "Only parse updated file";

    const OUTPUT_DIR = "apk-info";

    const APP_ICON_NAME = "app-icon.png";

    const VERSION_NAME = "versionName";
    const VERSION_CODE = "versionCode";
    const PACKAGE_NAME = "packageName";
    const MIN_SDK = "minSDK";
    const MIN_SDK_LEVEL = "minSDKLevel";
    const TARGET_SDK = "targetSDK";
    const DATE = "date";
    const ICON_PATH = "iconPath";

    protected $icon;
    protected $manifest;
    protected $parsed;
    protected $extractFolder;

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
            $this->outputDir = self::OUTPUT_DIR;
        }

        if(!is_dir($this->outputDir) && !file_exists($this->outputDir)){
            mkdir($this->outputDir, 0777, true);
        }

        //save current apk file time, condition for ONLY PARSE UPDATED file
//        $this->currentApkFileTime = filemtime($apkFilePath);

        $this->options = $options;
        $this->parsed = false;

        $md5Input  = $this->apkFilePath + filemtime($this->apkFilePath);
        $this->extractFolder = $this->outputDir . DIRECTORY_SEPARATOR . md5($md5Input);

        $this->parse();
    }


    private function parse(){
        //mkdir before extract
//        $extractFolder = $this->outputDir . DIRECTORY_SEPARATOR . md5($this->apkFilePath);

        if(is_dir($this->extractFolder) || file_exists($this->extractFolder)){
            $this->parsed = true;
            //if parsed, read directly from $this->extractFolder
            if($this->parsed){
                $resource = fopen($this->extractFolder . DIRECTORY_SEPARATOR . self::MANIFEST, "r");
                $this->manifest = new Manifest(new XmlParser(new Stream($resource)));
                fclose($resource);
                $this->icon = new Icon($this->extractFolder . DIRECTORY_SEPARATOR . self::APP_ICON_NAME);
            }
            return;
        }

        if(!is_dir($this->extractFolder) && !file_exists($this->extractFolder)){
            mkdir($this->extractFolder, 0777, true);
        }

        //using aapt to get WHERE icon locate in apk file
        $aaptCommand = sprintf("%s d badging %s", self::AAPT, $this->apkFilePath);
        $badging = $this->cmd(self::AAPT, $aaptCommand, AaptException::class);

        //read out icon-path from $badging
        $badgingString = implode("\n", $badging);
        $pattern_icon = "/icon='(.+)'/isU";
        preg_match($pattern_icon, $badgingString, $m);
        $iconPath = end($m);

        //unzip apk, get out AndroidManifest.xml, icon base on $iconPath
        $zipCommand = sprintf("%s x %s -aoa -o%s %s %s", self::ZIP, $this->apkFilePath, $this->extractFolder, $iconPath,
            self::MANIFEST);
        $this->cmd(self::ZIP, $zipCommand, ZipException::class);

        //copy icon to $extractFolder, under APP_ICON_NAME
        $copiedIconPath = $this->extractFolder . DIRECTORY_SEPARATOR . self::APP_ICON_NAME;
        copy($this->extractFolder . DIRECTORY_SEPARATOR . $iconPath, $copiedIconPath);
        //now we have icon, AndroidManifest.xml
        $this->icon = new Icon($copiedIconPath);

        $resource = fopen($this->extractFolder . DIRECTORY_SEPARATOR . self::MANIFEST, "r");
        $this->manifest = new Manifest(new XmlParser(new Stream($resource)));
        fclose($resource);
    }

    /**
     * @param string $cmd
     * @param string $command
     * @param Exception $exceptionType
     * @return string
     * @throws Exception
     */
    private function cmd($cmd, $command, $exceptionType){
        exec($cmd, $out, $resultCode);
        if($resultCode == 1){
            //$cmd not installed or not added to environment variable
            $errMsg = sprintf("%s not installed or not added to environment variable", $cmd);
            throw new Exception($errMsg);
        }
        if($resultCode == 0 || $resultCode == 2){
            exec($command, $out, $resultCode);
            if($resultCode == 0){
                return $out;
            }else{
                throw new $exceptionType($resultCode);
            }
        }
        throw new Exception(self::UN_HANDLE);
    }

    public function getIcon(){
//        if(isset($this->icon)){
//            return $this->icon;
//        }
//        $errMsg = sprintf("parsed, apk file path: %s", $this->apkFilePath);
//        throw new Exception($errMsg);
        return $this->icon;

    }

    public function getManifest(){
//        if(isset($this->manifest)){
//            return $this->manifest;
//        }
//        $errMsg = sprintf("parsed, apk file path: %s", $this->apkFilePath);
//        throw new Exception($errMsg);
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

        return $info;
    }
}