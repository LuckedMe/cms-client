<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class DefaultControllerTest extends WebTestCase
{
    private const string AUTH_CLIENT    = 'lucked_me_cms_client.auth.http_client';
    private const string CONTENT_CLIENT = 'lucked_me_cms_client.content.http_client';

    public function testHomepageRendersHomepageContent(): void
    {
        $client = self::createClient();
        $this->mockAuth(fn (): ResponseInterface => new JsonMockResponse($this->tokens()));
        $this->mockContent(static fn (string $method, string $url): ResponseInterface => match (true) {
            str_ends_with($url, '/pages/home') => new JsonMockResponse([
                'id' => '1',
                'slug' => 'home',
                'type' => 'page',
                'is_homepage' => true,
                'title' => 'Welcome home',
                'content' => '<p>Hello</p>',
            ]),
            default => new JsonMockResponse(['domain' => 'example.com', 'title' => 'My site', 'homepage' => 'home']),
        });

        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Welcome home');
    }

    public function testHomepageSlugRedirectsToRoot(): void
    {
        $client = self::createClient();
        $this->mockAuth(fn (): ResponseInterface => new JsonMockResponse($this->tokens()));
        $this->mockContent(new JsonMockResponse(['domain' => 'example.com', 'homepage' => 'home']));

        $client->request('GET', '/home');

        self::assertResponseRedirects('/', Response::HTTP_MOVED_PERMANENTLY);
    }

    public function testUnknownPageIsNotFound(): void
    {
        $client = self::createClient();
        $this->mockAuth(fn (): ResponseInterface => new JsonMockResponse($this->tokens()));
        $this->mockContent(static fn (string $method, string $url): ResponseInterface => str_contains($url, '/pages/')
            ? new JsonMockResponse(['message' => 'Not found'], ['http_code' => 404])
            : new JsonMockResponse(['domain' => 'example.com', 'homepage' => 'home']));

        $client->request('GET', '/');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testSiteIsNotFoundWhenApiRejectsLogin(): void
    {
        $client = self::createClient();
        $this->mockAuth(new JsonMockResponse(['message' => 'secret api detail'], ['http_code' => 401]));
        $contentCalls = 0;
        $this->mockContent(static function () use (&$contentCalls): ResponseInterface {
            ++$contentCalls;

            return new JsonMockResponse(['domain' => 'example.com', 'homepage' => 'home']);
        });

        $client->request('GET', '/');

        self::assertSame(0, $contentCalls, 'The content API must not be called without a token.');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testSiteIsNotFoundWhenApiIsUnreachable(): void
    {
        $client = self::createClient();
        $this->mockAuth(new MockResponse(info: ['error' => 'Connection refused']));
        $this->mockContent(new MockResponse(info: ['error' => 'Connection refused']));

        $client->request('GET', '/');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @return array<string, mixed>
     */
    private function tokens(): array
    {
        return ['token' => 'jwt', 'refresh_token' => 'refresh', 'refresh_token_expiration' => 1893456000];
    }

    private function mockAuth(callable|ResponseInterface $response): void
    {
        self::getContainer()->set(self::AUTH_CLIENT, new MockHttpClient($response));
    }

    private function mockContent(callable|ResponseInterface $response): void
    {
        self::getContainer()->set(self::CONTENT_CLIENT, new MockHttpClient($response, 'https://cms-admin.local/api/sites/1/'));
    }
}
