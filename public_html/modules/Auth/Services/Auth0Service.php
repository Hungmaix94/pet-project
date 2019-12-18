<?php

namespace Modules\Auth\Services;

use Zttp\Zttp;
use Firebase\JWT\JWT;

class Auth0Service
{
    public function exchangeToken($code)
    {
        $response = Zttp::post(config('auth.domain').'/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => config('auth.client_id'),
            'client_secret' => config('auth.client_secret'),
            'redirect_uri' => config('auth.redirect_uri'),
            'scope' => 'openid profile email'
        ]);

        $data = $response->json();

        if (! $response->isOk()) {
            throw new \Exception(print_r($data, true));
        }

        return $data;
    }

    public function logout($logoutUrl)
    {
        $response = Zttp::post($logoutUrl);
        $data = $response->json();
        if (! $response->isOk()) {
            throw new \Exception(print_r($data, true));
        }
        return $data;
    }

    public function getUser($accessToken)
    {
        $response = Zttp::withHeaders(['Authorization' => 'Bearer '.$accessToken])
                ->get(config('auth.domain').'/userinfo');

        if ($response->isOk()) {
            return $response->json();
        }

        throw new \Exception('Invalid token');
    }

    public function parseToken($accessToken)
    {
        $tks = explode('.', $accessToken);

        if (count($tks) !== 3) {
            throw new \Exception('Wrong number of segments');
        }

        return JWT::jsonDecode(JWT::urlsafeB64Decode($tks[1]));
    }


    public function getToken(){
        $response = Zttp::post(config('auth.domain') . '/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => config('auth.client_id_api_auth'),
            'client_secret' => config('auth.client_secret_api_auth'),
            'audience' => config('auth.domain') . '/api/v2/',
        ]);

        $data = $response->json();

        if (! $response->isOk()) {
            throw new \Exception(print_r($data, true));
        }

        return $data;
    }
}