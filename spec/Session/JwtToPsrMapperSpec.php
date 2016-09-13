<?php

namespace spec\jschreuder\Middle\Session;

use jschreuder\Middle\Session\JwtToPsrMapper;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

/** @mixin  JwtToPsrMapper */
class JwtToPsrMapperSpec extends ObjectBehavior
{
    const PRIVATE_KEY = '-----BEGIN RSA PRIVATE KEY-----
MIIBOgIBAAJBAJZ37zflIWLaeFfzBcQLPVcwB9dTQKzJB+BkzAUS+w9a4R5XZIJr
/iOKU3znyDz91yoojDU0UcmOu3Ah7uX7Co0CAwEAAQJAZVJfyLDHWYypyvd/43J6
HNLgBNQv0eoRHr5hT+1nF//etGxkLb+Ih26AenxCyMiA9UiRv+pJvrLSiiK5cGka
IQIhAM7l7LVatmEWnWJxydfDpPa19HISlVHxic8aH0DNP6+5AiEAui2h3WG7V9oE
Po4mQZxw5lxIhuNNzlbEDldWXFT9E3UCIQCRL42E0cwrozf8Dgdq7nKDYbnQlrPL
1egzuYv26FDpmQIgULRhOy8XX+DBAEDscnqXMjSEt/wmiTBxcmoHpKSuw9UCIGTY
UJmjVQ6FwKlTMzvayj3oKaTwsJNGb82SiTxAJvkn
-----END RSA PRIVATE KEY-----';

    const PUBLIC_KEY = '-----BEGIN PUBLIC KEY-----
MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAJZ37zflIWLaeFfzBcQLPVcwB9dTQKzJ
B+BkzAUS+w9a4R5XZIJr/iOKU3znyDz91yoojDU0UcmOu3Ah7uX7Co0CAwEAAQ==
-----END PUBLIC KEY-----';

    public function let()
    {
        $this->beConstructedThrough('fromAsymmetricKeyDefaults', [
            self::PRIVATE_KEY, self::PUBLIC_KEY, 3600
        ]);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(JwtToPsrMapper::class);
    }
}
