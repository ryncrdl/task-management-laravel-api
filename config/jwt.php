<?php

/*
|--------------------------------------------------------------------------
| JWT Authentication Configuration
|--------------------------------------------------------------------------
| Config for tymon/jwt-auth. Most values are pulled from .env.
| Run `php artisan jwt:secret` to generate the JWT_SECRET.
*/

return [

    /*
    |--------------------------------------------------------------------------
    | JWT Authentication Secret
    |--------------------------------------------------------------------------
    | Required: set JWT_SECRET in .env (generated via `php artisan jwt:secret`)
    | This MUST match the JWT_SECRET used in the Node.js service.
    */
    'secret' => env('JWT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | JWT Authentication Keys (for RS256 algorithm)
    |--------------------------------------------------------------------------
    | Only needed if using asymmetric algorithms. Leave null for HS256.
    */
    'keys' => [
        'public' => env('JWT_PUBLIC_KEY'),
        'private' => env('JWT_PRIVATE_KEY'),
        'passphrase' => env('JWT_PASSPHRASE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | JWT time to live (in minutes)
    |--------------------------------------------------------------------------
    | Specify the length of time (in minutes) that the token will be valid for.
    | Defaults to 1 hour (60 minutes).
    */
    'ttl' => env('JWT_TTL', 60),

    /*
    |--------------------------------------------------------------------------
    | Refresh time to live (in minutes)
    |--------------------------------------------------------------------------
    | Specify the length of time (in minutes) that the token can be refreshed
    | within. Defaults to 2 weeks (20160 minutes).
    */
    'refresh_ttl' => env('JWT_REFRESH_TTL', 20160),

    /*
    |--------------------------------------------------------------------------
    | JWT hashing algorithm
    |--------------------------------------------------------------------------
    | Specify the hashing algorithm that will be used to sign the token.
    | HS256 is the symmetric default — matched in the Node.js middleware.
    */
    'algo' => env('JWT_ALGO', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | Required Claims
    |--------------------------------------------------------------------------
    | Specify the required claims that must exist in any token.
    */
    'required_claims' => [
        'iss',
        'iat',
        'exp',
        'nbf',
        'sub',
        'jti',
    ],

    /*
    |--------------------------------------------------------------------------
    | Persistent Claims
    |--------------------------------------------------------------------------
    | Specify the claims that should persist when refreshing a token.
    */
    'persistent_claims' => [
        'role',
    ],

    /*
    |--------------------------------------------------------------------------
    | Lock Subject
    |--------------------------------------------------------------------------
    | Locks the subject to the authenticated user.
    */
    'lock_subject' => true,

    /*
    |--------------------------------------------------------------------------
    | Leeway (in seconds)
    |--------------------------------------------------------------------------
    | Acceptable leeway for clock skew between servers.
    */
    'leeway' => env('JWT_LEEWAY', 0),

    /*
    |--------------------------------------------------------------------------
    | Blacklist Enabled
    |--------------------------------------------------------------------------
    | Enables token invalidation on logout.
    */
    'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Blacklist Grace Period (in seconds)
    |--------------------------------------------------------------------------
    | Short grace period for race conditions.
    */
    'blacklist_grace_period' => env('JWT_BLACKLIST_GRACE_PERIOD', 0),

    /*
    |--------------------------------------------------------------------------
    | Decrypt Cookies
    |--------------------------------------------------------------------------
    */
    'decrypt_cookies' => false,

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'jwt' => Tymon\JWTAuth\Providers\JWT\Lcobucci::class,
        'auth' => Tymon\JWTAuth\Providers\Auth\Illuminate::class,
        'storage' => Tymon\JWTAuth\Providers\Storage\Illuminate::class,
    ],

];
