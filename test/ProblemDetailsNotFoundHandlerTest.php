<?php

declare(strict_types=1);

namespace MezzioTest\ProblemDetails;

use Mezzio\ProblemDetails\ProblemDetailsNotFoundHandler;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ProblemDetailsNotFoundHandlerTest extends TestCase
{
    use ProblemDetailsAssertionsTrait;

    /** @var ProblemDetailsResponseFactory&MockObject */
    private $responseFactory;

    protected function setUp(): void
    {
        $this->responseFactory = $this->createMock(ProblemDetailsResponseFactory::class);
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function acceptHeaders(): array
    {
        return [
            'application/json' => ['application/json', 'application/problem+json'],
            'application/xml'  => ['application/xml', 'application/problem+xml'],
        ];
    }

    #[DataProvider('acceptHeaders')]
    public function testResponseFactoryPassedInConstructorGeneratesTheReturnedResponse(string $acceptHeader): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getHeaderLine')->with('Accept')->willReturn($acceptHeader);
        $request->method('getUri')->willReturn('https://example.com/foo');

        $response = $this->createMock(ResponseInterface::class);

        $this->responseFactory
            ->method('createResponse')
            ->with(
                $request,
                404,
                'Cannot POST https://example.com/foo!'
            )->willReturn($response);

        $notFoundHandler = new ProblemDetailsNotFoundHandler($this->responseFactory);

        $this->assertSame(
            $response,
            $notFoundHandler->process($request, $this->createMock(RequestHandlerInterface::class))
        );
    }

    public function testHandlerIsCalledIfAcceptHeaderIsUnacceptable(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getHeaderLine')->with('Accept')->willReturn('text/html');
        $request->method('getUri')->willReturn('https://example.com/foo');

        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->with($request)->willReturn($response);

        $notFoundHandler = new ProblemDetailsNotFoundHandler($this->responseFactory);

        $this->assertSame(
            $response,
            $notFoundHandler->process($request, $handler)
        );
    }
}
