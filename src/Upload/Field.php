<?php


namespace Jazor\Http\Upload;


class Field
{
    private string $name;
    private ?string $value;
    protected ?string $preparedData = null;

    /**
     * Field constructor.
     * @param string $name
     * @param string $value
     */
    public function __construct(string $name, ?string $value)
    {
        $this->name = $name;
        $this->value = $value;
    }


    /**
     * @param string $boundary
     * @return int
     */
    public function prepare(string $boundary)
    {
        $this->preparedData = sprintf("--%s\r\nContent-Disposition: form-data; name=\"%s\"\r\n\r\n%s\r\n", $boundary, $this->name, $this->value);
        return strlen($this->preparedData);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getPreparedData(): string
    {
        if($this->preparedData === null) {
            throw new \Exception('can not get preparedData before prepare');
        }
        return $this->preparedData;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }
}
