<?php


namespace Jazor\Http\Transporters;

use Jazor\Http\Request;
use Jazor\Http\Response;
use Jazor\Http\Transporter;

class CurlTransporter extends Transporter
{

    /**
     * @param Request $request
     * @return Response
     * @throws \Jazor\NotImplementedException|\Exception
     */
    public function execute(Request $request): Response
    {

        $uri = $request->getUri();
        $method = $request->getMethod();
        $fp = curl_init();

        curl_setopt($fp, CURLOPT_URL, $uri->getUrl());
        curl_setopt($fp, CURLOPT_HEADER, true);
        curl_setopt($fp, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($fp, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($fp, CURLOPT_FOLLOWLOCATION, $this->followLocation);
        curl_setopt($fp, CURLOPT_ACCEPT_ENCODING, '');

        curl_setopt($fp, CURLOPT_SSL_VERIFYPEER, $this->sslVerifyPeer);
        curl_setopt($fp, CURLOPT_SSL_VERIFYHOST, $this->sslVerifyHost ? 2 : 0);

        curl_setopt($fp, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        $headers = $request->getAllHeadersArray();
        $body = $request->getBody($this);
        if ($body != null) {
            curl_setopt($fp, CURLOPT_POSTFIELDS, $body);
        }
        if (!empty($headers)) {
            curl_setopt($fp, CURLOPT_HTTPHEADER, $headers);
        }

        $body = curl_exec($fp);
        if ($body === false) {
            curl_close($fp);
            throw new \Exception(curl_error($fp), curl_errno($fp));
        }

        $headerSize = curl_getinfo($fp, CURLINFO_HEADER_SIZE);

        $header = substr($body, 0, $headerSize);
        $body = substr($body, $headerSize);

        curl_close($fp);
        $response = new Response($header);
        $response->setBody($body);

        return $response;
    }

    public function send(?string $content, Request $request)
    {
        throw new \Exception('not supported');
    }

    public function sendFile(?string $file, Request $request)
    {
        throw new \Exception('not supported');
    }
}
