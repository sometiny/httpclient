<?php


namespace Jazor\Http;

use Jazor\UnexpectedException;

class Response extends Headers
{
    private int $statusCode;
    private string $httpVersion = 'HTTP/1.1';
    private ?string $body = null;
    private ?string $location = null;
    private ?string $allResponseHeaders = null;
    private ?string $transferEncoding = null;
    private ?string $vary = null;
    private ?string $contentEncoding = null;
    private int $contentLength = -1;

    /**
     * Response constructor.
     * @param string|array $headers
     * @throws UnexpectedException
     */
    public function __construct($headers)
    {
        if(is_array($headers)){
            $this->allResponseHeaders = implode("\r\n", $headers) . "\r\n";
        }else {
            $this->allResponseHeaders = $headers;
            $headers = explode("\r\n", $headers);
        }

        $index = $this->parseStatusLine($headers);
        if($index !== 0){
            $this->allResponseHeaders = implode("\r\n", array_slice($headers, $index)) . "\r\n";
        }

        for ($i = $index + 1; $i < count($headers); $i++) {
            $this->addHeaderLine($headers[$i]);
        }
        $this->prepareHeaders();
    }

    protected function prepareHeaders()
    {
        parent::prepareHeaders();
        $header = $this->getSingletHeader('Location');
        if ($header !== null) $this->location = $header;

        $header = $this->getSingletHeader('Transfer-Encoding');
        if ($header !== null) $this->transferEncoding = $header;

        $header = $this->getSingletHeader('Vary');
        if ($header !== null) $this->vary = $header;

        $header = $this->getSingletHeader('Content-Encoding');
        if ($header !== null) $this->contentEncoding = $header;

        $header = $this->getSingletHeader('Content-Length');

        if ($header !== null) $this->contentLength = intval($header);
    }

    /**
     * When this stream wrapper follows a redirect, the wrapper_data returned by stream_get_meta_data()
     * might not necessarily contain the HTTP status line that actually applies to the content data at index 0.
     * @param string $line
     * @return int
     * @throws UnexpectedException
     */
    private function parseStatusLine(array $headers){

        for($i = count($headers) - 1 ; $i >=0 ;$i--){
            $line = $headers[$i];
            if(preg_match('/^(HTTP\/(?:[0-9\.]+?)) ([0-9]+)/', $line, $match)){
                $this->httpVersion = $match[1];
                $this->statusCode = intval($match[2]);
                return $i;
            }
        }
        throw new UnexpectedException('except http header');
    }

    /**
     * @return string|null
     */
    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * @param int $options
     * @return array|null
     * @throws \Exception
     */
    public function getJson(int $options = 0): ?array
    {
        $result = json_decode($this->body, true, 512, $options);

        if($result === false){
            throw new \Exception('json decode failed');
        }

        return $result;
    }

    /**
     * @param string|null $body
     */
    public function setBody(?string $body): void
    {
        $this->body = $body;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return string|null
     */
    public function getAllResponseHeaders(): ?string
    {
        return $this->allResponseHeaders;
    }

    /**
     * @return string
     */
    public function getHttpVersion(): string
    {
        return $this->httpVersion;
    }

    /**
     * @return string|null
     */
    public function getLocation(): ?string
    {
        return $this->location;
    }

    /**
     * @return string|null
     */
    public function getTransferEncoding(): ?string
    {
        return $this->transferEncoding;
    }

    /**
     * @return string|null
     */
    public function getVary(): ?string
    {
        return $this->vary;
    }

    /**
     * @return string|null
     */
    public function getContentEncoding(): ?string
    {
        return $this->contentEncoding;
    }

    /**
     * @return int
     */
    public function getContentLength(): int
    {
        return $this->contentLength;
    }
}
