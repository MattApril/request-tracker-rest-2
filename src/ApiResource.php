<?php

declare(strict_types=1);

namespace MattApril\RequestTracker;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;

/**
 * Class ApiResource
 *
 * @author Matthew April
 */
class ApiResource
{

    /**
     *
     */
    const API_BASE_URI = 'REST/2.0';

    /**
     * API token
     * @var string
     */
    private $token;

    /**
     * Http Client
     * @var ClientInterface
     */
    private $client;


    /**
     * ApiResource constructor.
     * @param ClientInterface $client
     * @param string $token
     */
    public function __construct( ClientInterface $client, string $token ){
        $this->setToken($token);
        $this->client = $client;
    }

    /**
     * @param string $token
     */
    public function setToken( string $token ){
        $this->token = $token;
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param string|null $body
     * @param array $headers
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function sendRequest( string $method, string $endpoint, string $body=null, $headers=[] ){
        $endpoint = trim($endpoint, '/'); // clean up any leading or trailing slashes
        $requestUrl = self::API_BASE_URI .'/'. $endpoint;

        $request = new Request( $method, $requestUrl, $headers, $body );
        $response = $this->client->send($request, [
            'headers' => [
                'Authorization' => 'token ' . $this->token
            ]
        ]);

        return $response;
    }

}