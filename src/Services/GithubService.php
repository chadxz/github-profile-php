<?php
namespace App\Services;

class GithubService {
    /** @var \GuzzleHttp\Client */
    private $http;

    /**
     * @param \GuzzleHttp\Client $http
     */
    public function __construct(\GuzzleHttp\Client $http) {
        $this->http = $http;
    }

    /**
     * @param string $token
     * @return array
     */
    public function getProfile(string $token): array {
        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $this->http->get('https://api.github.com/user', [
            'headers' => [
                'Authorization' => "Bearer {$token}"
            ]
        ]);

        $values = json_decode($response->getBody(), true);

        return [
            'name' => $values['login']
        ];
    }
}
