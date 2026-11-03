<?php

declare(strict_types=1);

namespace LibertJeremy\Symfony\SecurityHelpersBundle\SecurityHeaders;

use LibertJeremy\Symfony\SecurityHelpers\Header\ContentSecurityPolicy;
use LibertJeremy\Symfony\SecurityHelpers\Header\Hsts;
use LibertJeremy\Symfony\SecurityHelpers\Header\PermissionsPolicy;
use LibertJeremy\Symfony\SecurityHelpers\Header\SecurityHeadersPolicy;

/**
 * Builds the policy from the processed `security_headers` configuration (see SecurityHeadersConfiguration).
 *
 * @phpstan-type HstsConfig array{enabled: bool, max_age: int, include_subdomains: bool, preload: bool}
 * @phpstan-type CspConfig array{report_only: bool, directives: array<string, list<string>>, nonce_directives: list<string>}
 * @phpstan-type Config array{
 *     hsts: HstsConfig,
 *     content_security_policy: CspConfig,
 *     permissions_policy: array<string, list<string>>,
 *     referrer_policy: string|null,
 *     frame_options: string|null,
 *     cross_origin_opener_policy: string|null,
 *     cross_origin_resource_policy: string|null,
 *     excluded_paths: list<string>,
 * }
 */
final class PolicyFactory
{
    /**
     * @param Config $config
     */
    public static function fromConfig(array $config): SecurityHeadersPolicy
    {
        $hsts = $config['hsts'];
        $csp = $config['content_security_policy'];

        return new SecurityHeadersPolicy(
            hsts: $hsts['enabled'] ? new Hsts($hsts['max_age'], $hsts['include_subdomains'], $hsts['preload']) : null,
            contentSecurityPolicy: [] !== $csp['directives'] ? new ContentSecurityPolicy($csp['directives'], $csp['nonce_directives'], $csp['report_only']) : null,
            permissionsPolicy: [] !== $config['permissions_policy'] ? new PermissionsPolicy($config['permissions_policy']) : null,
            referrerPolicy: $config['referrer_policy'],
            frameOptions: $config['frame_options'],
            crossOriginOpenerPolicy: $config['cross_origin_opener_policy'],
            crossOriginResourcePolicy: $config['cross_origin_resource_policy'],
            excludedPaths: $config['excluded_paths'],
        );
    }
}
