<?php

namespace Arakne\Spinneret\Security;

/**
 * Define the options for a cookie
 */
final readonly class CookieOptions
{
    public const string SAME_SITE_LAX = 'Lax';
    public const string SAME_SITE_STRICT = 'Strict';
    public const string SAME_SITE_NONE = 'None';

    public function __construct(
        /**
         * The name of the cookie
         */
        public string $name = 'auth',

        /**
         * If set, the cookie will be sent only to the given path prefix.
         * The path should start with a / character.
         */
        public ?string $path = '/',

        /**
         * Define the host name which will receive the cookie.
         *
         * If defined, all subdomains will receive the cookie as well.
         * If not, only the current exact domain (i.e. not subdomains) will receive the cookie.
         */
        public ?string $domain = null,

        /**
         * If set, the cookie will only be sent over secure connections (HTTPS),
         * or on localhost (for development purposes).
         */
        public bool $secure = false,

        /**
         * If set, the cookie will not be accessible via JavaScript.
         * This is useful to reduce the risk of XSS attacks.
         */
        public bool $httpOnly = true,

        /**
         * Define the sending behavior of the cookie when the user is redirected from another domain.
         *
         * - null: the browser will use the default behavior
         * - SAME_SITE_STRICT: the cookie will be sent only if the user comes from the same site
         * - SAME_SITE_LAX: the cookie will be sent if the user comes from the same site, or from a link on another site (GET request)
         * - SAME_SITE_NONE: the cookie will be sent in all cases, only if the cookie is secure
         *
         * @var self::SAME_SITE_LAX|self::SAME_SITE_STRICT|self::SAME_SITE_NONE|null
         */
        public ?string $sameSite = null,

        /**
         * Number of seconds before the cookie expires.
         * If null, the cookie will be deleted when the browser is closed.
         */
        public ?int $maxAge = null,
    ) {}

    /**
     * Create the cookie string with the given value
     * The result string can be used in the Set-Cookie header
     *
     * @param string $value The payload of the cookie
     * @return string
     */
    public function format(string $value): string
    {
        $str = $this->name . '=' . $value;

        if ($this->path !== null) {
            $str .= '; Path=' . $this->path;
        }

        if ($this->domain !== null) {
            $str .= '; Domain=' . $this->domain;
        }

        if ($this->secure) {
            $str .= '; Secure';
        }

        if ($this->httpOnly) {
            $str .= '; HttpOnly';
        }

        if ($this->sameSite !== null) {
            $str .= '; SameSite=' . $this->sameSite;
        }

        if ($this->maxAge !== null) {
            $str .= '; Max-Age=' . $this->maxAge;
        }

        return $str;
    }
}
