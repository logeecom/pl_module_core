<?php

namespace Packlink\BusinessLogic\OAuth\Services\Interfaces;

use Packlink\BusinessLogic\Http\DTO\OAuthUrlData;

interface OAuthServiceInterface
{
    /**
     * @param string $accessToken
     */
    public function connect($accessToken);

    public function getApiKey();

    public function getToken($accessToken);

    public function refreshToken($refreshToken);

    public function buildRedirectUrl(OAuthUrlData $data);
}