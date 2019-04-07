<?php

declare(strict_types=1);

namespace App\Services;

class GithubTokenRequestParams {
    /** @var string OAuth client id */
    public $client_id;
    /** @var string The OAuth client secret */
    public $client_secret;
    /** @var string The request state we sent when we first requested our login code */
    public $request_state;
    /** @var string The original url Github redirected the client to us with */
    public $redirect_url;
    /** @var string The code sent by Github when it redirected to us */
    public $login_code;

    public function __construct(
        string $client_id,
        string $client_secret,
        string $login_code,
        string $redirect_url,
        string $state
    ) {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->login_code = $login_code;
        $this->redirect_url = $redirect_url;
        $this->request_state = $state;
    }
}
