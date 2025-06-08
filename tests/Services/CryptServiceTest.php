<?php

namespace EbicsApi\Ebics\Tests\Services;

use EbicsApi\Ebics\Factories\Crypt\AESFactory;
use EbicsApi\Ebics\Factories\Crypt\RSAFactory;
use EbicsApi\Ebics\Services\CryptService;
use EbicsApi\Ebics\Services\KeyStorageLocator;
use EbicsApi\Ebics\Services\RandomService;
use EbicsApi\Ebics\Tests\AbstractEbicsTestCase;

/**
 * Class CryptServiceTest.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 *
 * @group crypt-services
 */
class CryptServiceTest extends AbstractEbicsTestCase
{
    /**
     * @group crypt-service-generate-keys
     */
    public function testGenerateKeys()
    {
        $credentialsId = 2;
        $client = $this->setupClientV25($credentialsId);
        $cryptService = new CryptService(new RSAFactory(), new AESFactory, new RandomService);

        $keyPair = $cryptService->generateKeyPair($client->getKeyring()->getPassword());

        self::assertObjectHasProperty('privateKey', $keyPair);
        self::assertObjectHasProperty('publicKey', $keyPair);
    }
}
