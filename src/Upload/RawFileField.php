<?php


namespace Jazor\Http\Upload;

use Jazor\Http\MIMETypes;

class RawFileField extends Field
{

    private string $data;
    private ?string $mimeType;

    public function __construct(string $name, string $data, string $fileName, ?string $mimeType = null)
    {
        $this->data = $data;
        parent::__construct($name, $fileName);
        $this->mimeType = $mimeType;
    }

    public function prepare(string $boundary)
    {
        $header = sprintf(
            "--%s\r\nContent-Disposition: form-data; name=\"%s\"; filename=\"%s\"\r\nContent-Type: %s\r\n\r\n%s\r\n",
            $boundary,
            $this->getName(),
            $this->getValue() ?? '',
            $this->mimeType,
            $this->data);

        $preparedLength = strlen($header);

        $this->preparedData = $header;

        return $preparedLength;
    }

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }
}
