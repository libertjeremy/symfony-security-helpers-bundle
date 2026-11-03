<?php

declare(strict_types=1);

namespace LibertJeremy\Symfony\SecurityHelpersBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class KernelTest extends TestCase
{
    public function testResponsesCarryTheConfiguredHeadersOnHttps(): void
    {
        $kernel = new TestKernel('test', false);
        $kernel->boot();

        $response = $kernel->handle(Request::create('https://qg.test/hello'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('max-age=31536000; includeSubDomains', $response->headers->get('Strict-Transport-Security'));
        $nonce = $response->getContent();
        self::assertIsString($nonce);
        self::assertNotSame('', $nonce);
        self::assertSame("default-src 'self'; script-src 'self' 'nonce-".$nonce."'", $response->headers->get('Content-Security-Policy'));
        self::assertSame('camera=(self), geolocation=()', $response->headers->get('Permissions-Policy'));
        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $kernel->shutdown();
    }

    public function testHstsIsNotSentOverHttp(): void
    {
        $kernel = new TestKernel('test', false);
        $kernel->boot();

        $response = $kernel->handle(Request::create('http://qg.test/hello'));

        self::assertFalse($response->headers->has('Strict-Transport-Security'));
        self::assertTrue($response->headers->has('Content-Security-Policy'));
        $kernel->shutdown();
    }

    public function testHeaderSetByTheControllerIsKept(): void
    {
        $kernel = new TestKernel('test', false);
        $kernel->boot();

        $response = $kernel->handle(Request::create('https://qg.test/custom-frame'));

        self::assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $kernel->shutdown();
    }

    public function testExcludedPathGetsNoHeader(): void
    {
        $kernel = new TestKernel('test', false);
        $kernel->boot();

        $response = $kernel->handle(Request::create('https://qg.test/_profiler/x'));

        self::assertFalse($response->headers->has('Content-Security-Policy'));
        $kernel->shutdown();
    }
}
