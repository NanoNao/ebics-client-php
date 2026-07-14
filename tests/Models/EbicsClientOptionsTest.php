<?php

namespace EbicsApi\Ebics\Tests\Models;

use EbicsApi\Ebics\Contracts\HttpClientInterface;
use EbicsApi\Ebics\Contracts\LoggerInterface;
use EbicsApi\Ebics\Contracts\Processor\AESEncryptorInterface;
use EbicsApi\Ebics\Contracts\Processor\Base64EncoderInterface;
use EbicsApi\Ebics\Contracts\Processor\ZipCompressorInterface;
use EbicsApi\Ebics\Models\EbicsClientOptions;
use PHPUnit\Framework\TestCase;

class EbicsClientOptionsTest extends TestCase
{
    private EbicsClientOptions $options;

    protected function setUp(): void
    {
        $this->options = new EbicsClientOptions();
    }

    public function testDefaultsAreAllNull(): void
    {
        self::assertNull($this->options->getHttpClient());
        self::assertNull($this->options->getLogger());
        self::assertNull($this->options->getRsaClassMap());
        self::assertNull($this->options->getSchemaDir());
        self::assertNull($this->options->getBase64Encoder());
        self::assertNull($this->options->getAesEncryptor());
        self::assertNull($this->options->getZipCompressor());
    }

    public function testCurlOptionsDefaultsToEmptyArray(): void
    {
        self::assertIsArray($this->options->getCurlOptions());
        self::assertEmpty($this->options->getCurlOptions());
    }

    public function testSetGetHttpClient(): void
    {
        $client = $this->createStub(HttpClientInterface::class);

        $result = $this->options->setHttpClient($client);

        self::assertSame($client, $this->options->getHttpClient());
        self::assertSame($this->options, $result);
    }

    public function testSetGetLogger(): void
    {
        $logger = $this->createStub(LoggerInterface::class);

        $result = $this->options->setLogger($logger);

        self::assertSame($logger, $this->options->getLogger());
        self::assertSame($this->options, $result);
    }

    public function testSetGetRsaClassMap(): void
    {
        $classMap = [\OpenSSLAsymmetricKey::class];

        $result = $this->options->setRsaClassMap($classMap);

        self::assertEquals($classMap, $this->options->getRsaClassMap());
        self::assertSame($this->options, $result);
    }

    public function testSetGetSchemaDir(): void
    {
        $result = $this->options->setSchemaDir('/path/to/schemas');

        self::assertEquals('/path/to/schemas', $this->options->getSchemaDir());
        self::assertSame($this->options, $result);
    }

    public function testSetGetCurlOptions(): void
    {
        $curlOptions = [
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => false,
        ];

        $result = $this->options->setCurlOptions($curlOptions);

        self::assertEquals($curlOptions, $this->options->getCurlOptions());
        self::assertSame($this->options, $result);
    }

    public function testSetGetBase64Encoder(): void
    {
        $encoder = $this->createStub(Base64EncoderInterface::class);

        $result = $this->options->setBase64Encoder($encoder);

        self::assertSame($encoder, $this->options->getBase64Encoder());
        self::assertSame($this->options, $result);
    }

    public function testSetGetAesEncryptor(): void
    {
        $encryptor = $this->createStub(AESEncryptorInterface::class);

        $result = $this->options->setAesEncryptor($encryptor);

        self::assertSame($encryptor, $this->options->getAesEncryptor());
        self::assertSame($this->options, $result);
    }

    public function testSetGetZipCompressor(): void
    {
        $compressor = $this->createStub(ZipCompressorInterface::class);

        $result = $this->options->setZipCompressor($compressor);

        self::assertSame($compressor, $this->options->getZipCompressor());
        self::assertSame($this->options, $result);
    }

    public function testSettersAcceptNull(): void
    {
        $this->options->setHttpClient($this->createStub(HttpClientInterface::class));
        $this->options->setLogger($this->createStub(LoggerInterface::class));
        $this->options->setRsaClassMap([\OpenSSLAsymmetricKey::class]);
        $this->options->setSchemaDir('/tmp');
        $this->options->setBase64Encoder($this->createStub(Base64EncoderInterface::class));
        $this->options->setAesEncryptor($this->createStub(AESEncryptorInterface::class));
        $this->options->setZipCompressor($this->createStub(ZipCompressorInterface::class));

        $this->options->setHttpClient(null);
        $this->options->setLogger(null);
        $this->options->setRsaClassMap(null);
        $this->options->setSchemaDir(null);
        $this->options->setBase64Encoder(null);
        $this->options->setAesEncryptor(null);
        $this->options->setZipCompressor(null);

        self::assertNull($this->options->getHttpClient());
        self::assertNull($this->options->getLogger());
        self::assertNull($this->options->getRsaClassMap());
        self::assertNull($this->options->getSchemaDir());
        self::assertNull($this->options->getBase64Encoder());
        self::assertNull($this->options->getAesEncryptor());
        self::assertNull($this->options->getZipCompressor());
    }

    public function testFluentInterfaceAllowsChaining(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $result = $this->options
            ->setHttpClient($httpClient)
            ->setLogger($logger)
            ->setSchemaDir('/schemas')
            ->setCurlOptions([CURLOPT_TIMEOUT => 30]);

        self::assertSame($this->options, $result);
        self::assertSame($httpClient, $this->options->getHttpClient());
        self::assertSame($logger, $this->options->getLogger());
        self::assertEquals('/schemas', $this->options->getSchemaDir());
    }

    public function testPublicPropertiesAreAccessible(): void
    {
        $client = $this->createStub(HttpClientInterface::class);
        $this->options->httpClient = $client;

        self::assertSame($client, $this->options->httpClient);
        self::assertSame($client, $this->options->getHttpClient());
    }
}
