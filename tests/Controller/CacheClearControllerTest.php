<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CacheClearControllerTest extends WebTestCase
{
    public function testRequestWithoutApiKeyIsDenied(): void
    {
        $client = self::createClient();

        $client->request('GET', '/api/cms-admin-cache-clear');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testRequestWithWrongApiKeyIsDenied(): void
    {
        $client = self::createClient();

        $client->request('GET', '/api/cms-admin-cache-clear', server: ['HTTP_X_API_KEY' => 'wrong']);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
