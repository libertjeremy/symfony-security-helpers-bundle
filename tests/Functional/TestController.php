<?php

declare(strict_types=1);

namespace LibertJeremy\Symfony\SecurityHelpersBundle\Tests\Functional;

use LibertJeremy\Symfony\SecurityHelpers\EventListener\SecurityHeadersListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TestController
{
    #[Route('/hello')]
    public function hello(Request $request): Response
    {
        $nonce = $request->attributes->get(SecurityHeadersListener::NONCE_ATTRIBUTE);

        return new Response(\is_string($nonce) ? $nonce : '');
    }

    #[Route('/custom-frame')]
    public function customFrame(): Response
    {
        return new Response('', 200, ['X-Frame-Options' => 'SAMEORIGIN']);
    }

    #[Route('/_profiler/x')]
    public function profiler(): Response
    {
        return new Response('profiler');
    }
}
