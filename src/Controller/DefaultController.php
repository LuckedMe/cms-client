<?php

declare(strict_types=1);

namespace App\Controller;

use LuckedMe\CmsClientBundle\Service\CmsService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Twig\Error\Error as TwigError;

class DefaultController extends AbstractController
{
    /**
     * @throws TwigError
     * @throws InvalidArgumentException
     */
    #[Route(
        path: '',
        name: 'web.homepage',
        options: ['expose' => true],
        methods: ['GET'],
    )]
    #[Route(
        path: '/{slug}',
        name: 'web.cms',
        requirements: ['slug' => Requirement::ASCII_SLUG],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(?string $slug, Request $request, CmsService $cmsService): Response
    {
        return $cmsService->handleRequest($slug, $request);
    }
}
