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
     * Bucket constructor.
     *
     * @param $filePath
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * @param string $filePath
     *
     * @return string
     */
    public function getData()
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

    /**
     * @param \DateTime $expireDate
     * @param int $hits
     *
     * @return array
     */
    protected function buildMetaData($expireDate= null, $hits=0)
    {
        $expireDateString = $expireDate;
        if ($expireDate instanceof \DateTime) {
            $expireDateString = $expireDate->format('Y-m-d\TH:i:sT');
        }

        return [
            'gs' => [
                'read_cache_expiry_seconds' => 0,
                'enable_cache' => true,
                'metadata' => [
                    'expires' => $expireDateString,
                    'hits' => $hits
                ]
            ],
        ];
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
    public function setData( $value, $ttl = 0, $reads = 0, $metadata = null)
    {
        $options = $this->mountOptions($ttl, $reads , $metadata);
        $ctx = stream_context_create($options);
        $r = file_put_contents($this->filePath, $value, Constants::DEFAULT_VALUE_FLAG_FILE_PUT_CONTENTS, $ctx);

        if ($r !== false) {
            return true;
        }

        return false;
    }

    /**
     * @param $ttl
     * @param $reads
     * @param $metadata
     * @return array
     */
    private function mountOptions($ttl, $reads , $metadata ){
        if (!empty($metadata)) {
            return $metadata;
        }

        $ttl = $this->getTimeCache($ttl);

        return $this->buildMetaData($this->getDateToOptions($ttl), $reads);

    }

    /**
     * @param int $timeInSeconds
     * @return int
     */
    private function getTimeCache($timeInSeconds=0){
        if(!empty($timeInSeconds)){
            return $timeInSeconds;
        }

        return Constants::DEFAULT_CACHE_TTL_SECONDS;
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
}