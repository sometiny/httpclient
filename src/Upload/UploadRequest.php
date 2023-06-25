<?php


namespace Jazor\Http\Upload;


use Jazor\Http\Request;
use Jazor\Http\Transporter;
use Jazor\NameValueCollection;

/**
 * Class UploadRequest
 * @property NameValueCollection $fields
 * @package Jazor\Http\Upload
 */
class UploadRequest extends Request
{

    private NameValueCollection $fields;
    private string $boundary = '';

    /**
     * UploadRequest constructor.
     * @param $url
     * @param string $method
     * @throws \Exception
     */
    public function __construct($url, string $method = 'POST')
    {
        parent::__construct($url, $method);
        $this->fields = new NameValueCollection();
    }


    public function __get($name)
    {
        if($name === 'fields') return $this->fields;
        throw new \RuntimeException('can not find property: ' . $name);
    }


    /**
     * 获取随机字符串
     * @param $len
     * @return mixed|string
     */
    private static function getRandomString($len)
    {
        mt_srand();
        $seeds = "abcdefghijklmnopqrstuvwxyzABCDEFGHIGKLMNOPQRSTUVWXYZ0123456789";
        $returnStr = '';
        for ($i = 1; $i <= $len; $i++) {
            $returnStr .= $seeds[mt_rand(0, 61)];
        }

        return $returnStr;
    }


    /**
     * @param string $name
     * @param string|null $value
     * @return UploadRequest
     */
    public function addField(string $name, ?string $value){
        $this->fields->add($name, new Field($name, $value));
        return $this;
    }


    /**
     * @param array $fields
     * @return UploadRequest
     */
    public function addFields(array $fields){
        foreach ($fields as $key => $value){
            $this->fields->add($key, new Field($key, $value));
        }
        return $this;
    }

    /**
     * @param string $name
     * @param string|null $file
     * @param string|null $mimeType
     * @throws \Exception
     */
    public function addFileField(string $name, ?string $file, ?string $mimeType = null){
        $this->fields->add($name, new FileField($name, $file, $mimeType));
    }

    /**
     * @param string $name
     * @param string $data
     * @param $fileName
     * @param string|null $mimeType
     */
    public function addRawFileField(string $name, string $data, $fileName, ?string $mimeType = null){
        $this->fields->add($name, new RawFileField($name, $data, $fileName, $mimeType));
    }


    /**
     * @param string $boundary
     * @return mixed|null
     * @throws \Exception
     */
    private function prepareFields(string $boundary){

        /**
         * @var Field|FileField $field
         */

        return $this->fields->reduce(function ($field, $length) use ($boundary){
            $length += $field->prepare($boundary);
            return $length;
        }, 0);
    }


    /**
     * @param Transporter $transporter
     * @return string|null
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function getBody(Transporter $transporter): ?string
    {
        $boundary = self::getRandomString(22);
        $this->boundary = $boundary;

        $boundaryEnd = "--{$boundary}--";

        $contentLength = $this->prepareFields($boundary);

        $contentLength += strlen($boundaryEnd);

        $this->setHeader('Content-Type', "multipart/form-data; boundary={$boundary}");
        $this->setHeader('Content-Length', (string)$contentLength);

        $this->fields->walk(function ($field) use ($transporter){
            /**
             * @var Field|FileField $field
             */

            $transporter->send($field->getPreparedData(), $this);
            if(!($field instanceof FileField)){
                return;
            }

            $file = $field->getFile();
            if(empty($file)){
                $transporter->send("\r\n", $this);
                return;
            }
            $transporter->sendFile($file, $this);
            $transporter->send("\r\n", $this);
        });

        $transporter->send($boundaryEnd, $this);
        return null;
    }


    /**
     * @return string|null
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function getPreviewBody(): ?string
    {
        $body = $this->fields->reduce(function ($field, $body) {
            /**
             * @var Field|FileField $field
             */

            $body .= $field->getPreparedData();
            if($field instanceof FileField){
                return $body. "[-----FILE_BINARY_DATA-----]\r\n";
            }
            return $body;
        }, '');

        $body .= sprintf("--%s--", $this->boundary);
        return $body;
    }
}
