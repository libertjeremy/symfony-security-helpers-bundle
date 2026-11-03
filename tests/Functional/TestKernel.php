<?php

declare(strict_types=1);

namespace LibertJeremy\Symfony\SecurityHelpersBundle\Tests\Functional;

use LibertJeremy\Symfony\SecurityHelpersBundle\SecurityHelpersBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new SecurityHelpersBundle();
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/security-helpers-bundle/'.self::VERSION.'/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/security-helpers-bundle/'.self::VERSION.'/log';
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->register(TestController::class, TestController::class)->setPublic(true)->addTag('controller.service_arguments');
        $container->loadFromExtension('framework', ['secret' => 'test', 'test' => true, 'http_method_override' => false, 'handle_all_throwables' => true, 'php_errors' => ['log' => false]]);
        $container->loadFromExtension('security_helpers', ['security_headers' => [
            'enabled' => true,
            'content_security_policy' => [
                'directives' => ['default-src' => ["'self'"], 'script-src' => ["'self'"]],
                'nonce_directives' => ['script-src'],
            ],
            'permissions_policy' => ['camera' => ['self'], 'geolocation' => []],
        ]]);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(TestController::class, 'attribute');
    }
}
