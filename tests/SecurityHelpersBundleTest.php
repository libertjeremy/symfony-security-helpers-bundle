<?php

declare(strict_types=1);

namespace LibertJeremy\Symfony\SecurityHelpersBundle\Tests;

use LibertJeremy\Symfony\SecurityHelpers\EventListener\SecurityHeadersListener;
use LibertJeremy\Symfony\SecurityHelpers\Header\SecurityHeadersPolicy;
use LibertJeremy\Symfony\SecurityHelpersBundle\SecurityHelpersBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SecurityHelpersBundleTest extends TestCase
{
    public function testExtensionAliasIsPinned(): void
    {
        self::assertSame('security_helpers', (new SecurityHelpersBundle())->getContainerExtension()?->getAlias());
    }

    public function testSecurityHeadersAreOffByDefault(): void
    {
        $container = $this->load([]);

        self::assertFalse($container->hasDefinition(SecurityHeadersListener::class));
        self::assertFalse($container->hasDefinition(SecurityHeadersPolicy::class));
    }

    public function testExplicitlyDisabledRegistersNothing(): void
    {
        $container = $this->load(['security_headers' => ['enabled' => false]]);

        self::assertFalse($container->hasDefinition(SecurityHeadersListener::class));
    }

    public function testEnabledRegistersAnAutoconfiguredListenerFedByThePolicy(): void
    {
        $container = $this->load(['security_headers' => ['enabled' => true]]);

        self::assertTrue($container->hasDefinition(SecurityHeadersListener::class));
        self::assertTrue($container->getDefinition(SecurityHeadersListener::class)->isAutoconfigured());
        self::assertTrue($container->hasDefinition(SecurityHeadersPolicy::class));
    }

    public function testSecureDefaultsAreApplied(): void
    {
        $headers = $this->policy(['security_headers' => ['enabled' => true]])->headers(true);

        self::assertSame('max-age=31536000; includeSubDomains', $headers['Strict-Transport-Security']);
        self::assertSame("default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'", $headers['Content-Security-Policy']);
        self::assertSame('camera=(), microphone=(), geolocation=()', $headers['Permissions-Policy']);
        self::assertSame('strict-origin-when-cross-origin', $headers['Referrer-Policy']);
        self::assertSame('DENY', $headers['X-Frame-Options']);
        self::assertSame('same-origin', $headers['Cross-Origin-Opener-Policy']);
        self::assertSame('same-site', $headers['Cross-Origin-Resource-Policy']);
        self::assertSame('nosniff', $headers['X-Content-Type-Options']);
    }

    public function testDefaultsExcludeTheProfiler(): void
    {
        $policy = $this->policy(['security_headers' => ['enabled' => true]]);

        self::assertTrue($policy->isExcluded('/_profiler/abc'));
        self::assertTrue($policy->isExcluded('/_wdt/abc'));
        self::assertFalse($policy->isExcluded('/login'));
    }

    public function testQgConfigurationRendersTheExpectedHeaders(): void
    {
        $headers = $this->policy(['security_headers' => [
            'enabled' => true,
            'content_security_policy' => ['directives' => [
                'default-src' => ["'self'"],
                'script-src' => ["'self'", "'wasm-unsafe-eval'"],
                'style-src' => ["'self'"],
                'img-src' => ["'self'", 'data:', 'blob:'],
                'connect-src' => ["'self'", 'https://world.openfoodfacts.org'],
                'font-src' => ["'self'"],
                'manifest-src' => ["'self'"],
                'worker-src' => ["'self'"],
                'frame-ancestors' => ["'none'"],
                'base-uri' => ["'self'"],
                'form-action' => ["'self'"],
            ]],
            'permissions_policy' => ['camera' => ['self'], 'microphone' => ['self'], 'geolocation' => []],
        ]])->headers(true);

        self::assertSame(
            "default-src 'self'; script-src 'self' 'wasm-unsafe-eval'; style-src 'self'; img-src 'self' data: blob:; connect-src 'self' https://world.openfoodfacts.org; font-src 'self'; manifest-src 'self'; worker-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'",
            $headers['Content-Security-Policy'],
        );
        self::assertSame('camera=(self), microphone=(self), geolocation=()', $headers['Permissions-Policy']);
    }

    public function testDirectiveGivenAsStringIsSplitOnWhitespace(): void
    {
        $headers = $this->policy(['security_headers' => [
            'enabled' => true,
            'content_security_policy' => ['directives' => ['img-src' => "'self' data: blob:"]],
        ]])->headers(true);

        self::assertSame("img-src 'self' data: blob:", $headers['Content-Security-Policy']);
    }

    public function testHstsCanBeDisabled(): void
    {
        $headers = $this->policy(['security_headers' => ['enabled' => true, 'hsts' => ['enabled' => false]]])->headers(true);

        self::assertArrayNotHasKey('Strict-Transport-Security', $headers);
    }

    public function testHstsOptionsAreApplied(): void
    {
        $headers = $this->policy(['security_headers' => ['enabled' => true, 'hsts' => ['max_age' => 63072000, 'preload' => true]]])->headers(true);

        self::assertSame('max-age=63072000; includeSubDomains; preload', $headers['Strict-Transport-Security']);
    }

    public function testEmptyDirectivesAndFeaturesSwitchTheHeadersOff(): void
    {
        $headers = $this->policy(['security_headers' => [
            'enabled' => true,
            'content_security_policy' => ['directives' => []],
            'permissions_policy' => [],
        ]])->headers(true);

        self::assertArrayNotHasKey('Content-Security-Policy', $headers);
        self::assertArrayNotHasKey('Permissions-Policy', $headers);
    }

    public function testNullScalarsSwitchTheirHeaderOff(): void
    {
        $headers = $this->policy(['security_headers' => [
            'enabled' => true,
            'referrer_policy' => null,
            'frame_options' => null,
            'cross_origin_opener_policy' => null,
            'cross_origin_resource_policy' => null,
        ]])->headers(true);

        self::assertSame(['Strict-Transport-Security', 'Content-Security-Policy', 'Permissions-Policy', 'X-Content-Type-Options'], array_keys($headers));
    }

    public function testReportOnlyAndNonceDirectives(): void
    {
        $headers = $this->policy(['security_headers' => [
            'enabled' => true,
            'content_security_policy' => [
                'report_only' => true,
                'directives' => ['script-src' => ["'self'"]],
                'nonce_directives' => ['script-src'],
            ],
        ]])->headers(true, 'n1');

        self::assertSame("script-src 'self' 'nonce-n1'", $headers['Content-Security-Policy-Report-Only']);
        self::assertArrayNotHasKey('Content-Security-Policy', $headers);
    }

    public function testCustomExcludedPathsReplaceTheDefaults(): void
    {
        $policy = $this->policy(['security_headers' => ['enabled' => true, 'excluded_paths' => ['/health']]]);

        self::assertTrue($policy->isExcluded('/health'));
        self::assertFalse($policy->isExcluded('/_profiler'));
    }

    public function testInvalidFrameOptionsFailsTheContainerBuild(): void
    {
        $container = $this->load(['security_headers' => ['enabled' => true, 'frame_options' => 'ALLOWALL']]);

        $this->expectException(\InvalidArgumentException::class);

        $container->get(SecurityHeadersPolicy::class);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function policy(array $config): SecurityHeadersPolicy
    {
        $policy = $this->load($config, true)->get(SecurityHeadersPolicy::class);
        self::assertInstanceOf(SecurityHeadersPolicy::class, $policy);

        return $policy;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function load(array $config, bool $public = false): ContainerBuilder
    {
        $container = new ContainerBuilder();
        foreach (['kernel.environment' => 'test', 'kernel.debug' => false, 'kernel.project_dir' => __DIR__, 'kernel.cache_dir' => sys_get_temp_dir(), 'kernel.build_dir' => sys_get_temp_dir()] as $name => $value) {
            $container->setParameter($name, $value);
        }

        $extension = (new SecurityHelpersBundle())->getContainerExtension();
        self::assertNotNull($extension);
        $extension->load([$config], $container);

        if ($public && $container->hasDefinition(SecurityHeadersPolicy::class)) {
            $container->getDefinition(SecurityHeadersPolicy::class)->setPublic(true);
        }

        return $container;
    }
}
