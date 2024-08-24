<?php

namespace Arakne\Spinneret\Security;

final readonly class CookieOptions
{
    public function __construct(
        public string $name = 'auth',
        public ?string $path = null,
        public ?string $domain = null,
        public bool $secure = false,
        public bool $httpOnly = true,
        public ?string $sameSite = null,
        public ?int $maxAge = null,
    ) {
    }

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
