<?php

declare(strict_types=1);

namespace App\Services;

class GithubLoginUrlParams {
    /** @var string Request CSRF token, to be checked prior to requesting token */
    public $request_state;
    /** @var string The url for Github to redirect the client to once authenticated */
    public $redirect_url;
    /** @var string The client_id of our Github app the client should auth against */
    public $client_id;

    public function __construct(
        string $client_id,
        string $redirect_url,
        string $request_state
    ) {
        $this->client_id = $client_id;
        $this->redirect_url = $redirect_url;
        $this->request_state = $request_state;
    }
}
