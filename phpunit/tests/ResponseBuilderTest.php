<?php

namespace Creios\Creiwork\Framework;

use Creios\Creiwork\Framework\Result\ApacheFileResult;
use Creios\Creiwork\Framework\Result\CsvResult;
use Creios\Creiwork\Framework\Result\FileResult;
use Creios\Creiwork\Framework\Result\HtmlRawResult;
use Creios\Creiwork\Framework\Result\JsonResult;
use Creios\Creiwork\Framework\Result\NginxFileResult;
use Creios\Creiwork\Framework\Result\PlainTextResult;
use Creios\Creiwork\Framework\Result\RedirectResult;
use Creios\Creiwork\Framework\Result\TemplateResult;
use Creios\Creiwork\Framework\Result\Util\Disposition;
use Creios\Creiwork\Framework\Result\XmlRawResult;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use League\Plates\Engine;
use Psr\Http\Message\ServerRequestInterface;
use Zumba\Util\JsonSerializer;

/**
 * Class ResponseBuilderTest
 * @package Creios\Creiwork\Framework
 */
class ResponseBuilderTest extends \PHPUnit_Framework_TestCase
{

    /** @var ResponseBuilder */
    private $responseBuilder;
    /** @var \PHPUnit_Framework_MockObject_MockObject|Engine */
    private $engine;
    /** @var \PHPUnit_Framework_MockObject_MockObject|JsonSerializer */
    private $serializer;
    /** @var \PHPUnit_Framework_MockObject_MockObject|ServerRequestInterface */
    private $serverRequest;
    /** @var resource */
    private $stream;

    public function setUp()
    {
        $this->engine = $this->createMock(Engine::class);
        $this->serializer = $this->createMock(JsonSerializer::class);
        $this->serverRequest = $this->createMock(ServerRequestInterface::class);
        $this->responseBuilder = new ResponseBuilder($this->serializer, $this->engine, $this->serverRequest);
        $this->stream = fopen('php://temp', 'r+');
        fwrite($this->stream, '');
        fseek($this->stream, 0);
    }

    public function testTemplateResult()
    {
        $expectedResponse = (new Response())->withHeader('Content-Type', 'text/html')->withHeader('Content-Length', 0)->withBody(new Stream($this->stream));
        $result = new TemplateResult('test', []);
        $actualResponse = $this->responseBuilder->process($result);
        $this->assertEquals($expectedResponse->getHeaders(), $actualResponse->getHeaders());
    }

    public function testTemplateResultWithStatusCode()
    {
        $expectedResponse = (new Response())->withStatus(200)->withHeader('Content-Type', 'text/html')->withHeader('Content-Length', 0)->withBody(new Stream($this->stream));
        $result = (new TemplateResult('test', []))->withStatusCode(200);
        $actualResponse = $this->responseBuilder->process($result);
        $this->assertEquals($expectedResponse->getStatusCode(), $actualResponse->getStatusCode());
        $this->assertEquals($expectedResponse->getHeaders(), $actualResponse->getHeaders());
    }

    public function testTemplateResultWithNullData()
    {

        $expectedResponse = (new Response())->withHeader('Content-Type', 'text/html')->withHeader('Content-Length', 0)->withBody(new Stream($this->stream));
        $result = new TemplateResult('test');
        $actualResponse = $this->responseBuilder->process($result);
        $this->assertEquals($expectedResponse->getHeaders(), $actualResponse->getHeaders());
    }

    public function testJsonResultDownload()
    {
        $expectedResponse = (new Response())->withHeader('Content-Type', 'application/json')->withHeader('Content-Length', 0)
            ->withHeader('Content-Disposition', 'attachment; filename=test.json')
            ->withBody(new Stream($this->stream));
        $disposition = (new Disposition(Disposition::ATTACHMENT))->withFilename('test.json');
        $result = (new JsonResult(['key' => 'value']))->withDisposition($disposition);
        $actualResponse = $this->responseBuilder->process($result);
        $this->assertEquals($expectedResponse->getHeaders(), $actualResponse->getHeaders());
    }

    public function testRedirectResult()
    {
        $expectedResponse = (new Response())->withHeader('Location', 'http://localhost/redirect');
        $result = new RedirectResult('http://localhost/redirect');
        $actualResponse = $this->responseBuilder->process($result);
        $this->assertEquals($expectedResponse->getHeaders(), $actualResponse->getHeaders());
    }

    public function testPlainResult()
    {
        $expectedResponse = (new Response())->withHeader('Content-Type', 'text/plain')->withHeader('Content-Length', 21);
        $result = 'Result is a plaintext';
        $actualResponse = $this->responseBuilder->process($result);
        $this->assertEquals($expectedResponse->getHeaders(), $actualResponse->getHeaders());
    }

    public function testPlainTextResult()
    {
        $expectedResponse = (new Response())->withStatus(400)->withHeader('Content-Type', 'text/plain')->withHeader('Content-Length', 21);
        $result = (new PlainTextResult('Result is a plaintext'))->withStatusCode(400);
        $actualResponse = $this->responseBuilder->process($result);
        $this->assertEquals($expectedResponse->getStatusCode(), $actualResponse->getStatusCode());
        $this->assertEquals($expectedResponse->getHeaders(), $actualResponse->getHeaders());
    }

    public function testFileResult()
    {
        $expectedResponse = (new Response())->withHeader('Content-Type', 'text/plain')->withHeader('Content-Length', 40);
        $result = new FileResult(__DIR__ . '/../asset/textfile.txt');
        $actualResponse = $this->responseBuilder->process($result);
        $this->assertEquals($expectedResponse->getHeaders(), $actualResponse->getHeaders());
    }

    public function testHtmlResult()
    {
        $expectedResponse = (new Response())->withHeader('Content-Type', 'text/html')->withHeader('Content-Length', 123);
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
</head>
<body>

</body>
</html>
HTML;
        $result = new HtmlRawResult($html);
        $actualResponse = $this->responseBuilder->process($result);
        $this->assertEquals($expectedResponse->getHeaders(), $actualResponse->getHeaders());
    }

    public function testXmlResult()
    {
        $expectedResponse = (new Response())->withHeader('Content-Type', 'text/xml')->withHeader('Content-Length', 82);
        $xml = <<<XML
<user>
    <id>1</id>
    <firstname>john</firstname>
    <name>doe</name>
</user>
XML;
        $result = new XmlRawResult($xml);
        $actualResponse = $this->responseBuilder->process($result);
        $this->assertEquals($expectedResponse->getHeaders(), $actualResponse->getHeaders());
    }

    public function testCsvResult()
    {
        $csvResult = (new CsvResult([
            ['A', 'B', 'C'],
            [1, 2, 3],
        ]))->withDisposition(
            (new Disposition(Disposition::ATTACHMENT))
                ->withFilename('foobar.csv'));
        $response = $this->responseBuilder->process($csvResult);
        $this->assertEquals(['text/csv'], $response->getHeader('Content-Type'));
        $this->assertEquals(
            ['attachment; filename=foobar.csv'],
            $response->getHeader('Content-Disposition'));
        $csv = <<<CSV
A,B,C
1,2,3

CSV;
        $this->assertEquals($csv, $response->getBody()->getContents());
    }

    public function testApacheFileResult()
    {
        $expectedResponse = (new Response())
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('X-Sendfile', __DIR__ . '/../asset/textfile.txt')
            ->withHeader('Content-Disposition', 'attachment; filename="textfile.txt"');
        $result = new ApacheFileResult(__DIR__ . '/../asset/textfile.txt');
        $actualResponse = $this->responseBuilder->process($result);
        $this->assertEquals($expectedResponse->getHeaders(), $actualResponse->getHeaders());

        $expectedResponse = (new Response())
            ->withHeader('Content-Type', 'text/plain')
            ->withHeader('X-Sendfile', __DIR__ . '/../asset/textfile.txt')
            ->withHeader('Content-Disposition', 'attachment; filename="textfile.txt"');
        $result = (new ApacheFileResult(__DIR__ . '/../asset/textfile.txt'))->withMimeType('text/plain');
        $actualResponse = $this->responseBuilder->process($result);
        $this->assertEquals($expectedResponse->getHeaders(), $actualResponse->getHeaders());
    }

    public function testNginxFileResult()
    {
        $expectedResponse = (new Response())
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('X-Accel-Redirect', __DIR__ . '/../asset/textfile.txt')
            ->withHeader('Content-Disposition', 'attachment; filename="textfile.txt"');
        $result = new NginxFileResult(__DIR__ . '/../asset/textfile.txt');
        $actualResponse = $this->responseBuilder->process($result);
        $this->assertEquals($expectedResponse->getHeaders(), $actualResponse->getHeaders());

        $expectedResponse = (new Response())
            ->withHeader('Content-Type', 'text/plain')
            ->withHeader('X-Accel-Redirect', __DIR__ . '/../asset/textfile.txt')
            ->withHeader('Content-Disposition', 'attachment; filename="textfile.txt"');
        $result = (new NginxFileResult(__DIR__ . '/../asset/textfile.txt'))->withMimeType('text/plain');
        $actualResponse = $this->responseBuilder->process($result);
        $this->assertEquals($expectedResponse->getHeaders(), $actualResponse->getHeaders());
    }
}
