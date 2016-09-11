<?php

namespace spec\jschreuder\Middle\View;

use jschreuder\Middle\View\RedirectView;
use PhpSpec\ObjectBehavior;

/** @mixin  RedirectView */
class RedirectViewSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->beConstructedWith('/path');

        $this->shouldHaveType(RedirectView::class);

        $this->getHeaders()->shouldReturn(['Location' => '/path']);
        $this->getStatusCode()->shouldReturn(302);
        $this->getParameters()->shouldReturn([]);

        $this->shouldThrow(\RuntimeException::class)->duringGetContentType();
        $this->shouldThrow(\RuntimeException::class)->duringGetTemplate();
        $this->shouldThrow(\RuntimeException::class)->duringSetParameter('another', 'not-allowed');
    }

    public function it_errors_on_non_redirect_status()
    {
        $this->beConstructedWith('/nope', 400);
        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }
}
