<?php


namespace Jazor\Http\Upload;

use Jazor\Http\MIMETypes;

class FileField extends Field
{

    private ?string $file;
    private ?string $mimeType;

    public function __construct(string $name, ?string $file, ?string $mimeType = null)
    {
        $this->file = $file;
        if($file == null){
            $this->mimeType = $mimeType ?? 'application/octet-stream';
            parent::__construct($name, null);
            return;
        }
        if(!is_file($this->file)){
            throw new \Exception('file not exists: ' . $this->file);
        }
        $infos = pathinfo($file);
        $fileName = $infos['basename'];
        $extension = $infos['extension'] ?? null;
        if(empty($mimeType)){
            $mimeType = self::getMIMEType($extension, $file);
        }

        parent::__construct($name, $fileName);
        $this->mimeType = $mimeType;
    }

    private static function getMIMEType($extension, $file){
        if(is_file($file) && function_exists('mime_content_type')){
            return mime_content_type($file);
        }
        if(!$extension){
            return 'application/octet-stream';
        }
        return MIMETypes::getMIMEType($extension) ?? 'application/octet-stream';
    }

    public function prepare(string $boundary)
    {
        $header = sprintf(
            "--%s\r\nContent-Disposition: form-data; name=\"%s\"; filename=\"%s\"\r\nContent-Type: %s\r\n\r\n",
            $boundary,
            $this->getName(),
            $this->getValue() ?? '',
            $this->mimeType);

        $preparedLength = strlen($header) + 2;

        $this->preparedData = $header;

        if(!empty($this->file)) {
            $preparedLength += filesize($this->file);
        }

        return $preparedLength;
    }

    /**
     * @return string|null
     */
    public function getFile(): ?string
    {
        return $this->file;
    }
}
