<?php

namespace Velto\Core\Support;

use Velto\Core\Session\Session;
use Velto\Core\Env\Env;
use RuntimeException;

class SocialAuth
{
    protected static array $providers = [];
    protected string $provider;
    protected array $config;

    protected bool $useState = true;

    public function stateless(): self
    {
        $this->useState = false;
        return $this;
    }

    public function stateful(): self
    {
        $this->useState = true;
        return $this;
    }

    public static function driver(string $provider): self
    {
        self::init();
        $instance = new self();
        $instance->provider = $provider;
        $instance->config = self::$providers[$provider] ?? throw new RuntimeException("Provider '{$provider}' not configured.");
        return $instance;
    }

    public static function init(): void
    {
        self::$providers = [
            'google' => [
                'client_id'     => Env::get('GOOGLE_CLIENT_ID'),
                'client_secret' => Env::get('GOOGLE_CLIENT_SECRET'),
                'redirect_uri'  => Env::get('GOOGLE_REDIRECT_URI'),
                'authorize_url' => 'https://accounts.google.com/o/oauth2/auth',
                'token_url'     => 'https://oauth2.googleapis.com/token',
                'user_url'      => 'https://www.googleapis.com/oauth2/v2/userinfo',
                'scope'         => 'email profile',
            ],
            'github' => [
                'client_id'     => Env::get('GITHUB_CLIENT_ID'),
                'client_secret' => Env::get('GITHUB_CLIENT_SECRET'),
                'redirect_uri'  => Env::get('GITHUB_REDIRECT_URI'),
                'authorize_url' => 'https://github.com/login/oauth/authorize',
                'token_url'     => 'https://github.com/login/oauth/access_token',
                'user_url'      => 'https://api.github.com/user',
                'scope'         => 'read:user user:email',
            ],
            'discord' => [
                'client_id'     => Env::get('DISCORD_CLIENT_ID'),
                'client_secret' => Env::get('DISCORD_CLIENT_SECRET'),
                'redirect_uri'  => Env::get('DISCORD_REDIRECT_URI'),
                'authorize_url' => 'https://discord.com/api/oauth2/authorize',
                'token_url'     => 'https://discord.com/api/oauth2/token',
                'user_url'      => 'https://discord.com/api/users/@me',
                'scope'         => 'identify email',
            ],
            'facebook' => [
                'client_id'     => Env::get('FACEBOOK_CLIENT_ID'),
                'client_secret' => Env::get('FACEBOOK_CLIENT_SECRET'),
                'redirect_uri'  => Env::get('FACEBOOK_REDIRECT_URI'),
                'authorize_url' => 'https://www.facebook.com/v18.0/dialog/oauth',
                'token_url'     => 'https://graph.facebook.com/v18.0/oauth/access_token',
                'user_url'      => 'https://graph.facebook.com/me?fields=id,name,email',
                'scope'         => 'email public_profile',
            ],
        ];
    }

    public function redirect(): string
    {
        $state = bin2hex(random_bytes(16));

        if ($this->useState) {
            Session::set("oauth_state_{$this->provider}", $state);
        }

        $query = http_build_query([
            'client_id'     => $this->config['client_id'],
            'redirect_uri'  => $this->config['redirect_uri'],
            'response_type' => 'code',
            'scope'         => $this->config['scope'],
            'state'         => $state,
        ], '', '&', PHP_QUERY_RFC3986);

        return $this->config['authorize_url'] . '?' . $query;
    }


    public function user(): ?array
    {
        $code  = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? '';
    
        if (!$code) {
            throw new RuntimeException('Missing OAuth code.');
        }
    
        if ($this->useState) {
            $expected = Session::get("oauth_state_{$this->provider}", '');
            if (!hash_equals($expected, $state)) {
                throw new RuntimeException('Invalid OAuth state.');
            }
        }
    
        $token = $this->exchangeCodeForToken($code);
        $data  = $token ? $this->fetchUserProfile($token) : null;
    
        if (!$data) return null;
    
        return [
            'id'    => $data['id'] ?? null,
            'name'  => $data['name'] 
                    ?? $data['login'] 
                    ?? $data['username'] 
                    ?? 'Unknown',
            'email' => $data['email'] ?? null,
            'raw'   => $data,
        ];
    }
    
    private function exchangeCodeForToken(string $code): ?string
    {
        $params = [
            'client_id'     => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'redirect_uri'  => $this->config['redirect_uri'],
            'code'          => $code,
        ];

        // Facebook requires `grant_type`
        if ($this->provider !== 'github') {
            $params['grant_type'] = 'authorization_code';
        }

        $response = self::http('POST', $this->config['token_url'], $params, ['Accept: application/json']);

        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }

    private function fetchUserProfile(string $token): ?array
    {
        $headers = [
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
            'User-Agent: VeltoPHP/2.0',
        ];

        $json = self::http('GET', $this->config['user_url'], [], $headers);
        return json_decode($json, true);
    }

    private static function http(string $method, string $url, array $payload = [], array $headers = []): string
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => $payload ? http_build_query($payload) : '',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $body   = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error  = curl_error($curl);
        curl_close($curl);

        if ($body === false || $status >= 400) {
            throw new RuntimeException("HTTP {$status}: {$error}");
        }

        return $body;
    }
}
