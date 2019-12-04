<?php

namespace Analyzer;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class RestApiCaller {
    /**
     * Start a request
     *
     * @return string the body of the response in json
     */
    public function startRequest($url) {
        $client_guzzle = $this->createGuzzleClient();
        $request = $this->createRequest('GET', $url);
        $response = $this->sendRequest($client_guzzle, $request);
        $status_code = $response->getStatusCode();
        $content_type = $response->getHeaderLine('content-type');
        $json = $response->getBody()->getContents();
        return $json;
    }

    /**
     * Create a new guzzle client
     *
     * @return \GuzzleHttp\Client the guzzle client
     */
    private function createGuzzleClient() {
        return new Client([
            'http_errors' => true,
            'timeout' => 4,
            'verify' => false, //Ignora la verifica dei certificati SSL (obbligatorio per accesso a risorse https)
            //see: http://docs.guzzlephp.org/en/latest/request-options.html#verify-option
            //'proxy' => '34.89.152.71:3128',
        ]);
    }

    /**
     * Create a guzzle request
     *
     * @param string $method the method
     * @param string $url the url
     * @return Request the guzzle request
     */
    private function createRequest($method, $url) {
        return new Request($method, $url);
    }

    /**
     * Send a guzzle request
     *
     * @param \GuzzleHttp\Client $client_guzzle the guzzle client
     * @param Request $request the guzzle request
     * @return \GuzzleHttp\Psr7\Response the response of the request
     */
    private function sendRequest(\GuzzleHttp\Client $client_guzzle, $request) {
        return $client_guzzle->send($request, [
            'headers' => [
                'User-Agent' => ' Chrome/78.0.3904.97', //Need this parameter
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ],
            'timeout' => 4,
        ]);
    }
}
