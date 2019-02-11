<?php

namespace LxLibs\Gae;

use LxLibs\Gae\Utils\Constants;

class Bucket
{
    /**
     * @var
     */
    private $filePath;

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var bool
     */
    private $isLocal;

    /**
     * @var string
     */
    private $error='';

    /**
     * Bucket constructor.
     *
     * @param $filePath
     * @param bool $isLocal
     */
    public function __construct($filePath, $isLocal = false)
    {
        $this->filePath = $filePath;
        $this->isLocal = $isLocal;
        $this->setOptions();
        if(!$this->isBucket()){
            $this->error = 'This is not bucket';
        }

    }

    /**
     * @param $name
     * @param $arguments
     * @return string
     */
    public function __call($name, $arguments)
    {
        if($this->error && $name !== 'setLocal'){
            return $this->error;
        }
    }

    /**
     * @return array
     */
    private function loadDefaultValues(){
        $expireDate = \DateTime();
        return [
            'readCacheExpirySeconds' => Constants::DEFAULT_READ_CACHE,
            'enableCache' => Constants::DEFAULT_CACHE_FLAG,
            'metadataExpires' => $expireDate->format('Y-m-d\TH:i:sT'),
            'metadataHits' => Constants::DEFAULT_CACHE_HITS_LIMIT

        ];
    }

    /**
     * @param array $options
     * @param array $options - readCacheExpirySeconds int
     * @param array $options - enableCache boleean
     * @param array $options - metadataExpires DateTime format Y-m-d\TH:i:sT
     * @param array $options - metadataHits int
     *
     * @return array
     */
    public function setOptions($options=[])
    {
        if(!empty($options['readCacheExpirySeconds'])){
            $options['readCacheExpirySeconds'] = $this->getDateToOptions($options['readCacheExpirySeconds']);
        }
        $this->options = array_merge($this->loadDefaultValues(),$options);

        return [
            'gs' => [
                'read_cache_expiry_seconds' => $this->options['readCacheExpirySeconds'],
                'enable_cache' => $this->options['enableCache'],
                'metadata' => [
                    'expires' => $this->options['metadataExpires'],
                    'hits' => $this->options['metadataHits']
                ]
            ],
        ];
    }

    /**
     * @param string $filePath
     *
     * @return string
     */
    public function get()
    {

        if (!file_exists($this->filePath)) {
            return null;
        }

        return file_get_contents($this->filePath);
    }

    /**
     * @param string $filePath
     *
     * @return bool
     */
    public function fileExists()
    {
        return file_exists($this->filePath);
    }

    /**
     * In Linux FS the directory is the same as a file
     *
     * @param string $filePath
     * @return bool
     */
    public function fileDirectoryExists()
    {
        return file_exists(dirname($this->filePath));
    }

    public function edit(){

    }

    /**
     * @param string $key
     * @param string $value
     * @param int $ttl
     * @param int $reads
     * @param string $metadata
     *
     * @return bool
     * @throws \Exception
     */
    public function put( $value )
    {
        $ctx = stream_context_create($this->setOptions());
        $r = file_put_contents($this->filePath, $value, Constants::DEFAULT_VALUE_FLAG_FILE_PUT_CONTENTS, $ctx);

        if ($r !== false) {
            return true;
        }

        return false;
    }

    /**
     * @param $timeSeconds
     * @return \LxLibs\Gae\DateTime
     */
    private function getDateToOptions($timeSeconds){
        $date = new DateTime();
        $date->add(new DateInterval("PT{$timeSeconds}S"));
        return $date;
    }

    /**
     * @param bool $local
     */
    private function isBucket(){
        if($this->isLocal OR $this->checkRegexFileGae()){
            return true;
        }
        return false;

    }

    /**
     * @return bool
     */
    private function checkRegexFileGae(){
        preg_match('/^gs:\/\//', $this->filePath, $matches, PREG_OFFSET_CAPTURE);
        if(empty($matches[0])){
            return false;
        }

        return true;

    }

    /**
     * @param bool $isLocal
     */
    public function setLocal($isLocal=true){
        $this->isLocal = $isLocal;
    }
}