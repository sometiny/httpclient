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
        $proxy = $this->options['proxy'] ?? null;
        $direct = $this->options['direct'] ?? null;
        $proxy_type = $this->options['proxy_type'] ?? 'none';



        $uri = $request->getUri();
        $method = $request->getMethod();
        $fp = curl_init();

        if($proxy_type === 'direct') {
            if (!empty($direct)) {
                curl_setopt($fp, CURLOPT_CONNECT_TO, (array)$direct);
            }
        }
        curl_setopt($fp, CURLOPT_URL, $uri->getUrl());
        curl_setopt($fp, CURLOPT_HEADER, true);
        curl_setopt($fp, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($fp, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($fp, CURLOPT_FOLLOWLOCATION, $this->followLocation);
        curl_setopt($fp, CURLOPT_ACCEPT_ENCODING, '');

        curl_setopt($fp, CURLOPT_SSL_VERIFYPEER, $this->sslVerifyPeer);
        curl_setopt($fp, CURLOPT_SSL_VERIFYHOST, $this->sslVerifyHost ? 2 : 0);

        curl_setopt($fp, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        if($proxy_type === 'proxy') {
            if (!empty($proxy)) {
                curl_setopt($fp, CURLOPT_PROXY, $proxy);
                if (strpos($proxy, 'https://') !== false
                    || strpos($proxy, 'http://') !== false) {
                    if (strpos($proxy, 'https://') !== false) {
                        curl_setopt($fp, CURLOPT_PROXY_SSL_VERIFYHOST, 0);
                        curl_setopt($fp, CURLOPT_PROXY_SSL_VERIFYPEER, 0);
                    }
                    curl_setopt($fp, CURLOPT_HTTPPROXYTUNNEL, 1);
                }
            }
        }

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
            $err = curl_error($fp);
            $err_no = curl_errno($fp);
            curl_close($fp);
            throw new \Exception($err, $err_no);
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
