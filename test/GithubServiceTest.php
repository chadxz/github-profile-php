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
use Mockery\MockInterface;
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
        $client = $this->getMockClient('post', 200, ['access_token' => 'foo']);
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
        $client = $this->getMockClient('post', 400);
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
        $client = $this->getMockClient('post', 200, ['access_token' => 'foo']);
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
     * @param string $method
     * @param int $status
     * @param array $body
     * @return Client|MockInterface
     */
    private function getMockClient(
        string $method,
        int $status,
        array $body = []
    ): MockInterface {
        $result = Mockery::mock(Client::class);
        $result
            ->shouldReceive($method)
            ->andReturn(new Response($status, [], json_encode($body)));

        return $result;
    }
}
