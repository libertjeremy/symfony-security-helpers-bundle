<?php

declare(strict_types=1);

namespace LibertJeremy\Symfony\SecurityHelpersBundle\SecurityHeaders;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * The `security_headers` configuration node. Secure defaults apply as soon as `enabled` is true; a map
 * (directives, features) given by the application replaces the default map instead of merging with it.
 * An empty map (`directives: {}`, `permissions_policy: {}`) or a null scalar switches that header off.
 */
final class SecurityHeadersConfiguration
{
    public static function define(NodeBuilder $parent): ArrayNodeDefinition
    {
        $node = $parent->arrayNode('security_headers');
        $node->addDefaultsIfNotSet();

        $children = $node->children();
        $children->booleanNode('enabled')->defaultFalse()->end();

        $hsts = $children->arrayNode('hsts')->addDefaultsIfNotSet()->children();
        $hsts->booleanNode('enabled')->defaultTrue()->end();
        $hsts->integerNode('max_age')->min(0)->defaultValue(31536000)->end();
        $hsts->booleanNode('include_subdomains')->defaultTrue()->end();
        $hsts->booleanNode('preload')->defaultFalse()->end();

        $csp = $children->arrayNode('content_security_policy')->addDefaultsIfNotSet()->children();
        $csp->booleanNode('report_only')->defaultFalse()->end();
        self::sourceMap($csp->arrayNode('directives'))
            ->defaultValue([
                'default-src' => ["'self'"],
                'base-uri' => ["'self'"],
                'form-action' => ["'self'"],
                'frame-ancestors' => ["'none'"],
                'object-src' => ["'none'"],
            ]);
        $csp->arrayNode('nonce_directives')->scalarPrototype()->end()->end();

        self::sourceMap($children->arrayNode('permissions_policy'))
            ->defaultValue(['camera' => [], 'microphone' => [], 'geolocation' => []]);

        $children->scalarNode('referrer_policy')->defaultValue('strict-origin-when-cross-origin')->end();
        $children->scalarNode('frame_options')->defaultValue('DENY')->end();
        $children->scalarNode('cross_origin_opener_policy')->defaultValue('same-origin')->end();
        $children->scalarNode('cross_origin_resource_policy')->defaultValue('same-site')->end();
        $children->arrayNode('excluded_paths')->scalarPrototype()->end()->defaultValue(['/_profiler', '/_wdt'])->end();

        return $node;
    }

    /**
     * A `name => list of values` map; a single string is split on whitespace. Keys keep their dashes.
     */
    private static function sourceMap(ArrayNodeDefinition $node): ArrayNodeDefinition
    {
        $node->normalizeKeys(false)->useAttributeAsKey('name');

        $node->arrayPrototype()
            ->beforeNormalization()
                ->ifString()
                ->then(static fn (string $value): array => preg_split('/\s+/', trim($value), -1, \PREG_SPLIT_NO_EMPTY) ?: [])
            ->end()
            ->scalarPrototype()->end();

        return $node;
    }
}
