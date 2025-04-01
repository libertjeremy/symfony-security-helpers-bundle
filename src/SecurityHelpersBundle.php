<?php

declare(strict_types=1);

namespace LibertJeremy\Symfony\SecurityHelpersBundle;

use LibertJeremy\Symfony\ConfigHelpers\Bundle\AbstractBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

final class SecurityHelpersBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        parent::loadExtension($config, $container, $builder);

        $container->import(__DIR__.'/../config/security.yaml');
    }
}
