<?php

namespace App\Services;

class GithubService {
    /** @var \GuzzleHttp\Client */
    private $http;

    public static function getLoginUrl(GithubLoginUrlParams $params): string {
        $query = http_build_query([
            "client_id" => $params->client_id,
            "redirect_uri" => $params->redirect_url,
            "state" => $params->request_state
        ]);

        return "https://github.com/login/oauth/authorize?$query";
    }

    /**
     * @param \GuzzleHttp\Client $http
     */
    public function __construct(\GuzzleHttp\Client $http) {
        $this->http = $http;
    }

    /**
     * @param GithubTokenRequestParams $params
     * @return string
     * @throws GithubServiceException
     */
    public function getLoginToken(GithubTokenRequestParams $params): string {
        $response = $this->http->post(
            'https://github.com/login/oauth/access_token',
            [
                'json' => [
                    "client_id" => $params->client_id,
                    "client_secret" => $params->client_secret,
                    "code" => $params->login_code,
                    "redirect_uri" => $params->redirect_url,
                    "state" => $params->request_state
                ]
            ]
        );

        $values = json_decode($response->getBody(), true);

        if ($response->getStatusCode() !== 200) {
            throw new GithubServiceException(
                "Unexpected response from Github: {$response->getBody()}"
            );
        }

        return $values['access_token'];
    }

    /**
     * @param string $token
     * @return array
     * @throws GithubServiceException
     */
    public function getProfile(string $token): array {
        $user = $this->getAuthenticatedUser($token);
        $repositories = $this->getUserRepositories($user['login'], $token);

        return [
            'name' => $user['login'],
            'url' => $user['html_url'],
            'repositories' => $repositories
        ];
    }

    /**
     * @param string $token
     * @return array
     * @throws GithubServiceException
     */
    private function getAuthenticatedUser(string $token): array {
        $res = $this->http->get('https://api.github.com/user', [
            'headers' => [
                'Authorization' => "Bearer {$token}"
            ]
        ]);

        if ($res->getStatusCode() !== 200) {
            throw new GithubServiceException(
                "Error retrieving authenticated user"
            );
        }

        return json_decode($res->getBody(), true);
    }

    /**
     * @param string $user
     * @param string $token
     * @return array
     * @throws GithubServiceException
     */
    private function getUserRepositories(string $user, string $token): array {
        $res = $this->http->get(
            'https://api.github.com/user/repos?visibility=public&affiliation=owner',
            [
                'headers' => [
                    'Authorization' => "Bearer {$token}"
                ]
            ]
        );

        if ($res->getStatusCode() !== 200) {
            throw new GithubServiceException(
                "Error retrieving user repositories"
            );
        }

        $repositories = json_decode($res->getBody(), true);

        $result = array_map(function ($r) {
            return [
                'name' => $r['full_name'],
                'url' => $r['html_url'],
                'stargazers' => $r['stargazers_count']
            ];
        }, $repositories);

        return $this::sortByStargazersDesc($result);
    }

    /**
     * @param array $repos
     * @return array
     */
    private static function sortByStargazersDesc(array $repos): array {
        $result = $repos;

        usort($result, function ($a, $b) {
            return $b['stargazers'] - $a['stargazers'];
        });

        return $result;
    }
}
