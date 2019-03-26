<?php

namespace App\Tests;

use App\Services\GithubLoginUrlParams;
use App\Services\GithubService;
use App\Services\GithubServiceException;
use App\Services\GithubTokenRequestParams;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class GithubServiceTest extends TestCase {
    use MockeryPHPUnitIntegration;

    public function test_getLoginUrl_buildsUrlFromParams() {
        $actual = GithubService::getLoginUrl(
            new GithubLoginUrlParams('foo', 'bar', 'baz')
        );

        $expected =
            'https://github.com/login/oauth/authorize?client_id=foo&redirect_uri=bar&state=baz';

        $this->assertEquals($expected, $actual);
    }

    public function test_getLoginUrl_escapesUnsafeUrlChars() {
        $actual = GithubService::getLoginUrl(
            new GithubLoginUrlParams('foo\quix', 'bar&qux', 'baz+bagre')
        );

        $expected =
            'https://github.com/login/oauth/authorize?client_id=foo%5Cquix&redirect_uri=bar%26qux&state=baz%2Bbagre';

        $this->assertEquals($expected, $actual);
    }

    public function test_canConstruct() {
        $actual = new GithubService(new Client());

        $this->assertNotNull($actual);
    }

    /**
     * @throws GithubServiceException
     */
    public function test_getLoginToken_requestsATokenFromGithub() {
        $resp = new Response(200, [], json_encode(['access_token' => 'foo']));
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('post')->andReturn($resp);

        $github = new GithubService($client);

        $github->getLoginToken(
            new GithubTokenRequestParams(
                'fooClientId',
                'barClientSecret',
                'bazCode',
                'quixUrl',
                'quxState'
            )
        );

        $client->shouldHaveReceived('post', [
            'https://github.com/login/oauth/access_token',
            [
                'json' => [
                    'client_id' => 'fooClientId',
                    'client_secret' => 'barClientSecret',
                    'code' => 'bazCode',
                    'redirect_uri' => 'quixUrl',
                    'state' => 'quxState'
                ]
            ]
        ]);
    }

    /**
     * @throws GithubServiceException
     */
    public function test_getLoginToken_onUnexpectedResponse_throwsGithubServiceException() {
        $resp = new Response(400, [], json_encode([]));
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('post')->andReturn($resp);

        $github = new GithubService($client);

        $this->expectException(GithubServiceException::class);

        $github->getLoginToken(
            new GithubTokenRequestParams(
                'fooClientId',
                'barClientSecret',
                'bazCode',
                'quixUrl',
                'quxState'
            )
        );
    }

    /**
     * @throws GithubServiceException
     */
    public function test_getLoginToken_onSuccessfulResponse_returnsLoginToken() {
        $resp = new Response(200, [], json_encode(['access_token' => 'foo']));
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('post')->andReturn($resp);

        $github = new GithubService($client);

        $token = $github->getLoginToken(
            new GithubTokenRequestParams(
                'fooClientId',
                'barClientSecret',
                'bazCode',
                'quixUrl',
                'quxState'
            )
        );

        $this->assertEquals('foo', $token);
    }

    /**
     * @throws GithubServiceException
     */
    public function test_getProfile_onAuthenticatedUserUnexpectedResponse_throwsGithubServiceException() {
        $resp = new Response(400, [], json_encode([]));
        $client = Mockery::mock(Client::class);
        $client
            ->shouldReceive('get')
            ->withArgs([
                'https://api.github.com/user',
                [
                    'headers' => [
                        'Authorization' => "Bearer foo"
                    ]
                ]
            ])
            ->andReturn($resp);

        $github = new GithubService($client);

        $this->expectException(GithubServiceException::class);

        $github->getProfile('foo');
    }

    /**
     * @throws GithubServiceException
     */
    public function test_getProfile_onUserReposUnexpectedResponse_throwsGithubServiceException() {
        $client = Mockery::mock(Client::class);
        $client
            ->shouldReceive('get')
            ->withArgs([
                'https://api.github.com/user',
                [
                    'headers' => [
                        'Authorization' => "Bearer foo"
                    ]
                ]
            ])
            ->andReturn(
                new Response(
                    200,
                    [],
                    json_encode([
                        'login' => 'chadxz',
                        'html_url' => 'https://github.com/chadxz'
                    ])
                )
            );

        $client
            ->shouldReceive('get')
            ->withArgs([
                'https://api.github.com/user/repos?visibility=public&affiliation=owner',
                [
                    'headers' => [
                        'Authorization' => "Bearer foo"
                    ]
                ]
            ])
            ->andReturn(new Response(400, [], json_encode([])));

        $github = new GithubService($client);

        $this->expectException(GithubServiceException::class);

        $github->getProfile('foo');
    }

    /**
     * @throws GithubServiceException
     */
    public function test_getProfile_onSuccess_returnsProfile() {
        $client = Mockery::mock(Client::class);
        $client
            ->shouldReceive('get')
            ->withArgs([
                'https://api.github.com/user',
                [
                    'headers' => [
                        'Authorization' => "Bearer foo"
                    ]
                ]
            ])
            ->andReturn(
                new Response(
                    200,
                    [],
                    json_encode([
                        'login' => 'chadxz',
                        'html_url' => 'https://github.com/chadxz'
                    ])
                )
            );

        $client
            ->shouldReceive('get')
            ->withArgs([
                'https://api.github.com/user/repos?visibility=public&affiliation=owner',
                [
                    'headers' => [
                        'Authorization' => "Bearer foo"
                    ]
                ]
            ])
            ->andReturn(
                new Response(
                    200,
                    [],
                    json_encode([
                        [
                            'full_name' => 'github-profile-php',
                            'html_url' =>
                                'https://github.com/chadxz/github-profile-php',
                            'stargazers_count' => 1
                        ]
                    ])
                )
            );

        $github = new GithubService($client);

        $profile = $github->getProfile('foo');

        $this->assertEquals(
            [
                'name' => 'chadxz',
                'url' => 'https://github.com/chadxz',
                'repositories' => [
                    [
                        'name' => 'github-profile-php',
                        'url' => 'https://github.com/chadxz/github-profile-php',
                        'stargazers' => 1
                    ]
                ]
            ],
            $profile
        );
    }

    /**
     * @throws GithubServiceException
     */
    public function test_getProfile_onSuccess_sortsRepositoriesByStargazers() {
        $client = Mockery::mock(Client::class);
        $client
            ->shouldReceive('get')
            ->withArgs([
                'https://api.github.com/user',
                [
                    'headers' => [
                        'Authorization' => "Bearer foo"
                    ]
                ]
            ])
            ->andReturn(
                new Response(
                    200,
                    [],
                    json_encode([
                        'login' => 'chadxz',
                        'html_url' => 'https://github.com/chadxz'
                    ])
                )
            );

        $client
            ->shouldReceive('get')
            ->withArgs([
                'https://api.github.com/user/repos?visibility=public&affiliation=owner',
                [
                    'headers' => [
                        'Authorization' => "Bearer foo"
                    ]
                ]
            ])
            ->andReturn(
                new Response(
                    200,
                    [],
                    json_encode([
                        [
                            'full_name' => 'github-profile-php',
                            'html_url' =>
                                'https://github.com/chadxz/github-profile-php',
                            'stargazers_count' => 1
                        ],
                        [
                            'full_name' => 'awry',
                            'html_url' => 'https://github.com/chadxz/awry',
                            'stargazers_count' => 14
                        ]
                    ])
                )
            );

        $github = new GithubService($client);

        $profile = $github->getProfile('foo');

        $this->assertEquals(
            [
                'name' => 'chadxz',
                'url' => 'https://github.com/chadxz',
                'repositories' => [
                    [
                        'name' => 'awry',
                        'url' => 'https://github.com/chadxz/awry',
                        'stargazers' => 14
                    ],
                    [
                        'name' => 'github-profile-php',
                        'url' => 'https://github.com/chadxz/github-profile-php',
                        'stargazers' => 1
                    ]
                ]
            ],
            $profile
        );
    }
}
