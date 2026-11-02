<?php

declare(strict_types=1);

namespace LibertJeremy\Symfony\SecurityHelpersBundle;

use LibertJeremy\Symfony\ConfigHelpers\Bundle\AbstractBundle;
use LibertJeremy\Symfony\SecurityHelpers\EventListener\SecurityHeadersListener;
use LibertJeremy\Symfony\SecurityHelpers\Header\SecurityHeadersPolicy;
use LibertJeremy\Symfony\SecurityHelpersBundle\SecurityHeaders\PolicyFactory;
use LibertJeremy\Symfony\SecurityHelpersBundle\SecurityHeaders\SecurityHeadersConfiguration;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

final class SecurityHelpersBundle extends AbstractBundle
{
    protected string $extensionAlias = 'security_helpers';

    public function configure(DefinitionConfigurator $definition): void
    {
        SecurityHeadersConfiguration::define($definition->rootNode()->children());
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        parent::loadExtension($config, $container, $builder);

        $container->import(__DIR__.'/../config/security.yaml');

        $headers = $config['security_headers'] ?? null;

        if (!\is_array($headers) || true !== ($headers['enabled'] ?? false)) {
            return;
        }

        $services = $container->services();
        $services->set(SecurityHeadersPolicy::class)
            ->factory([PolicyFactory::class, 'fromConfig'])
            ->args([$headers]);
        $services->set(SecurityHeadersListener::class)
            ->args([service(SecurityHeadersPolicy::class)])
            ->autoconfigure();
    }
}
