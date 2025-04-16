<?php

namespace Packlink\BusinessLogic\OAuth\Interfaces;

use Packlink\BusinessLogic\Http\DTO\OAuthUrlData;

interface OAuthServiceInterface
{
    /**
     * @param string $accessToken
     */
    public function connect($accessToken);

    public function getApiKey();

    public function getToken();

    public function refreshToken($refreshToken);

    public function buildRedirectUrl(OAuthUrlData $data);
}