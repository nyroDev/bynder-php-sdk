<?php

namespace Bynder\Api\Impl\OAuth2;

use Bynder\Api\Impl\AbstractRequestHandler;
use Bynder\Api\Impl\OAuth2\BynderOauthProvider;

class RequestHandler extends AbstractRequestHandler
{
    protected $configuration;

    private $oauthProvider;

    public function __construct($configuration)
    {
        $this->configuration = $configuration;

        $this->oauthProvider = new BynderOauthProvider([
            'clientId' => $configuration->getClientId(),
            'clientSecret' => $configuration->getClientSecret(),
            'redirectUri' => $configuration->getRedirectUri(),
            'bynderDomain' => $configuration->getBynderDomain(),
        ]);
    }

    public function getAuthorizationUrl(array $options = [])
    {
        return $this->oauthProvider->getAuthorizationUrl($options);
    }

    public function getAccessToken($code)
    {
        return $this->oauthProvider->getAccessToken(
            'authorization_code',
            ['code' => $code]
        );
    }

    public function getAccessTokenClientCredentials()
    {
        return $this->oauthProvider->getAccessToken(
            'client_credentials'
        );
    }

    protected function sendAuthenticatedRequest($requestMethod, $uri, $options = [])
    {
        $this->configuration->refreshToken($this->oauthProvider);

        $requestOptions = array_merge(
            $options,
            $this->configuration->getRequestOptions()
        );

        if (!isset($requestOptions['headers']) || !isset($requestOptions['headers']['User-Agent'])) {
            $requestOptions['headers']['User-Agent'] = 'bynder-php-sdk/' . $this->configuration->getSdkVersion();
        }

        return $this->oauthProvider->getHttpClient()->sendAsync(
            $this->oauthProvider->getAuthenticatedRequest(
                $requestMethod, $uri, $this->configuration->getToken()
            ),
            $requestOptions
        );
    }
}
