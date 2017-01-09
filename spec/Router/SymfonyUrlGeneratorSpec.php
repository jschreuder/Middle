<?php

namespace spec\jschreuder\Middle\Router;

use jschreuder\Middle\Router\SymfonyUrlGenerator;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Routing\Generator\UrlGenerator;

class SymfonyUrlGeneratorSpec extends ObjectBehavior
{
    /** @var  UrlGenerator */
    private $generator;

    public function let(UrlGenerator $generator)
    {
        $this->generator = $generator;
        $this->beConstructedWith($generator);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SymfonyUrlGenerator::class);
    }

    public function it_can_create_a_path()
    {
        $name = 'some.path';
        $options = ['another' => 'time'];
        $result = '/some/path/another/time';

        $this->generator->generate($name, $options, UrlGenerator::ABSOLUTE_PATH)->willReturn($result);

        $this->generatePath($name, $options)->shouldReturn($result);
    }

    public function it_can_create_a_url()
    {
        $name = 'some.path';
        $options = ['another' => 'time'];
        $result = 'http://with.ho.st/some/path/another/time';

        $this->generator->generate($name, $options, UrlGenerator::ABSOLUTE_URL)->willReturn($result);

        $this->generateUrl($name, $options)->shouldReturn($result);
    }
}
