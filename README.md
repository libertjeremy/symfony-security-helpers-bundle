# symfony-security-helpers-bundle

Symfony bundle (6.4, 7.x, 8.x) wiring [`symfony-security-helpers`](https://github.com/libertjeremy/symfony-security-helpers):
the `AbstractVoter` base class and a configurable HTTP security-headers listener.

Bundle class: `LibertJeremy\Symfony\SecurityHelpersBundle\SecurityHelpersBundle`
(extension alias `security_helpers`).

## Security headers

Off by default (enabling it on an existing application changes its responses). Once `enabled: true`, secure
defaults apply: HSTS (1 year, subdomains), a strict CSP (`default-src 'self'`, `frame-ancestors 'none'`, ...),
camera / microphone / geolocation denied, `Referrer-Policy: strict-origin-when-cross-origin`,
`X-Frame-Options: DENY`, COOP `same-origin`, CORP `same-site`, `X-Content-Type-Options: nosniff`, and no header on
`/_profiler` and `/_wdt`.

A header the application already set on the response is never overridden; HSTS is only sent on HTTPS requests.

```yaml
# config/packages/security_helpers.yaml
security_helpers:
    security_headers:
        enabled: true
        hsts:
            enabled: true
            max_age: 31536000
            include_subdomains: true
            preload: false
        content_security_policy:
            report_only: false
            directives: {}          # name => list of sources (or one space-separated string); replaces the defaults
            nonce_directives: []    # e.g. [script-src]: a per-request nonce is appended, see below
        permissions_policy: {}      # feature => allowlist; replaces the defaults
        referrer_policy: strict-origin-when-cross-origin   # null = not sent
        frame_options: DENY                                 # DENY | SAMEORIGIN | null
        cross_origin_opener_policy: same-origin             # null = not sent
        cross_origin_resource_policy: same-site             # null = not sent
        excluded_paths: ['/_profiler', '/_wdt']
```

A map you provide replaces the default map; an empty map (`directives: []`, `permissions_policy: []`) switches that
header off. With `nonce_directives`, read the nonce from the `_csp_nonce` request attribute
(`SecurityHeadersListener::NONCE_ATTRIBUTE`).

### Example: a PWA that scans barcodes with WebAssembly

```yaml
security_helpers:
    security_headers:
        enabled: true
        content_security_policy:
            directives:
                default-src: ["'self'"]
                script-src: ["'self'", "'wasm-unsafe-eval'"]
                style-src: ["'self'"]
                img-src: ["'self'", 'data:', 'blob:']
                connect-src: ["'self'", 'https://world.openfoodfacts.org']
                font-src: ["'self'"]
                manifest-src: ["'self'"]
                worker-src: ["'self'"]
                frame-ancestors: ["'none'"]
                base-uri: ["'self'"]
                form-action: ["'self'"]
        permissions_policy:
            camera: [self]
            microphone: [self]
            geolocation: []
```

This sends `Permissions-Policy: camera=(self), microphone=(self), geolocation=()` and the CSP
`default-src 'self'; script-src 'self' 'wasm-unsafe-eval'; style-src 'self'; img-src 'self' data: blob:; connect-src 'self' https://world.openfoodfacts.org; font-src 'self'; manifest-src 'self'; worker-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'`.

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse   # level 10
vendor/bin/php-cs-fixer fix --dry-run --diff
```
