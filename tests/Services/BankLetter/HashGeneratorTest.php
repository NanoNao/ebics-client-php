<?php

namespace EbicsApi\Ebics\Tests\Services\BankLetter;

use DateTime;
use EbicsApi\Ebics\Factories\Crypt\RSAFactory;
use EbicsApi\Ebics\Factories\SignatureFactory;
use EbicsApi\Ebics\Models\Bank;
use EbicsApi\Ebics\Models\Crypt\Key;
use EbicsApi\Ebics\Models\Crypt\KeyPair;
use EbicsApi\Ebics\Models\Crypt\RSA;
use EbicsApi\Ebics\Models\X509\BankX509Generator;
use EbicsApi\Ebics\Services\CryptService;
use EbicsApi\Ebics\Services\DigestResolverV2;
use EbicsApi\Ebics\Services\DigestResolverV3;
use EbicsApi\Ebics\Services\Processor\AESEncryptor;
use EbicsApi\Ebics\Services\Processor\Base64Encoder;
use EbicsApi\Ebics\Services\RandomService;
use EbicsApi\Ebics\Services\TransactionKeyResolver;
use EbicsApi\Ebics\Tests\AbstractEbicsTestCase;

/**
 * Class HashGeneratorTest.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 *
 * @group hash-generator
 */
class HashGeneratorTest extends AbstractEbicsTestCase
{
    /**
     * @group hash-generator-certificate-v2
     * @covers
     */
    public function testGenerateCertificateHashV2()
    {
        $aesEncryptor = new AESEncryptor(new TransactionKeyResolver());

        $digestResolver = new DigestResolverV2(
            new CryptService(new RSAFactory($aesEncryptor), $aesEncryptor, new RandomService(), new Base64Encoder())
        );

        $privateKey = new Key($this->getPrivateKey(), RSA::PRIVATE_FORMAT_PKCS1);
        $publicKey = new Key($this->getPublicKey(), RSA::PUBLIC_FORMAT_PKCS1);

        // Certificate generated for the 22/03/2020 (1 year validity)
        $x509Generator = new BankX509Generator();
        $x509Generator->setCertificateOptionsByBank(new Bank('H123456', 'https://test.bank.dom'));
        $x509Generator->getAX509Context()->setStartDate(new DateTime('2020-03-22'));
        $x509Generator->getAX509Context()->setEndDate(new DateTime('2021-03-22'));
        $x509Generator->getAX509Context()->setSerialNumber(
            '37376365613564393736653364353135633333333932376336366134393663336133663135323432'
        );
        $rsaFactory = new RSAFactory($aesEncryptor);
        $x509Generator->getAX509Context()->setSubjectPublicKey($rsaFactory->createPublic($publicKey));
        $x509Generator->getAX509Context()->setIssuerPublicKey($rsaFactory->createPublic($publicKey));
        $x509Generator->getAX509Context()->setIssuerPrivateKey($rsaFactory->createPrivate($privateKey, 'test123'));

        $certificateFactory = new SignatureFactory($rsaFactory);

        $signature = $certificateFactory->createSignatureAFromKeys(
            new KeyPair($publicKey, $privateKey, 'test123'),
            $x509Generator
        );

        $hash = $digestResolver->confirmDigest($signature);

        self::assertEquals('2ed0ab3715fbddb7e516290a6d757c556917715be7a552359c81a8b1b9b22162', $hash);
    }

    /**
     * @group hash-generator-certificate-v3
     * @covers
     */
    public function testGenerateCertificateHashV3()
    {
        $aesEncryptor = new AESEncryptor(new TransactionKeyResolver());

        $digestResolver = new DigestResolverV3(
            new CryptService(new RSAFactory($aesEncryptor), $aesEncryptor, new RandomService(), new Base64Encoder())
        );

        $privateKey = new Key($this->getPrivateKey(), RSA::PRIVATE_FORMAT_PKCS1);
        $publicKey = new Key($this->getPublicKey(), RSA::PUBLIC_FORMAT_PKCS1);

        // Certificate generated for the 22/03/2020 (1 year validity)
        $x509Generator = new BankX509Generator();
        $x509Generator->setCertificateOptionsByBank(new Bank('H123456', 'https://test.bank.dom'));
        $x509Generator->getAX509Context()->setStartDate(new DateTime('2020-03-22'));
        $x509Generator->getAX509Context()->setEndDate(new DateTime('2021-03-22'));
        $x509Generator->getAX509Context()->setSerialNumber(
            '37376365613564393736653364353135633333333932376336366134393663336133663135323432'
        );
        $rsaFactory = new RSAFactory($aesEncryptor);
        $x509Generator->getAX509Context()->setSubjectPublicKey($rsaFactory->createPublic($publicKey));
        $x509Generator->getAX509Context()->setIssuerPublicKey($rsaFactory->createPublic($publicKey));
        $x509Generator->getAX509Context()->setIssuerPrivateKey($rsaFactory->createPrivate($privateKey, 'test123'));

        $certificateFactory = new SignatureFactory($rsaFactory);

        $signature = $certificateFactory->createSignatureAFromKeys(
            new KeyPair($publicKey, $privateKey, 'test123'),
            $x509Generator
        );

        $hash = $digestResolver->confirmDigest($signature);

        self::assertEquals('2ed0ab3715fbddb7e516290a6d757c556917715be7a552359c81a8b1b9b22162', $hash);
    }

    /**
     * @group hash-generator-public-key
     * @covers
     */
    public function testGeneratePublicKeyHash()
    {
        $aesEncryptor = new AESEncryptor(new TransactionKeyResolver());

        $digestResolver = new DigestResolverV2(
            new CryptService(new RSAFactory($aesEncryptor), $aesEncryptor, new RandomService(), new Base64Encoder())
        );

        $privateKey = new Key($this->getPrivateKey(), RSA::PRIVATE_FORMAT_PKCS1);
        $publicKey = new Key($this->getPublicKey(), RSA::PUBLIC_FORMAT_PKCS1);

        $rsaFactory = new RSAFactory($aesEncryptor);

        $certificateFactory = new SignatureFactory($rsaFactory);

        $signature = $certificateFactory->createSignatureAFromKeys(
            new KeyPair($publicKey, $privateKey, 'test123')
        );

        $hash = $digestResolver->confirmDigest($signature);

        self::assertEquals('e1955c3873327e1791aca42e350cea48196f7934648d48b60228eaf5d10ee0c4', $hash);
    }

    /**
     * @return string
     */
    private function getPrivateKey()
    {
        return '-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQCMwexPODeJcwskuyIjIqQ2pDkI6k4HEVnpfGOdc4x9jF0cFYn4
pdwJ9Mdz6GqgoHLjWH2D1rKH1jEsOFT9ks+QyHRtKG/q9lyCrzuBo6cYTXU8Mgi9
USM+Z70J4NVSFKObOCz/3eJrz4fDe955DEMqhc+VkmXlyOOdiKy7Pi2bbwIDAQAB
AoGAMeWMn4iOJ2tgx+SOdWYSUExm64Ijpt2/wcUWivorE1Zuq0X3Yu1o0x6ylaQO
KGK4V19HHzU8lGqZg9N0TW99pI6Sp7IcOCakIm4RnyahAWzbKJzZ0XSAs1FHE/Gl
yRvDg+V1+Nx7i52jCbSbHSCB/EmoOlTaV+TJjtq8yFsNagECQQDKAUW5w4y9/w+K
ppWlyhBvV8zS1GztHQ8yJEcsTiHcUkyA3SF5KPATWw3c/lWN4uYw4XDTopdqWJNu
W+fwWdMNAkEAsmGhYqQlEI9r49Tz1anQAFtCUzBHEJtBWOuRa0C5BLJH6tyU2IK9
C1odvBbzlgLb1CzdjHal0/LYViHkrBa5awJBAL1uqAZmXUunLtnlEhzg+ryPZ6Km
VmedgqyQ3LWtp49HFjsaI9PNEiX0k3GUiIKAL0HTh8zPgpLV8ZviUAVTFtkCQHXU
G6BmwLzxn9i839vw8Z5qqaL9rtN/Wmj8IfBwrkY15V90GTXzFiCbhCysFHawqLi8
chPIg70/Gju646vwzsUCQGucnbDIXjnQK8nkzAiv/2+AluuCaP/DpBducbUhVWZZ
cTPigqsjIjo409hi01WNXMgZO3c6V7iAaaXtAmRmzVM=
-----END RSA PRIVATE KEY-----
';
    }

    /**
     * @return string
     */
    private function getPublicKey()
    {
        return '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCMwexPODeJcwskuyIjIqQ2pDkI
6k4HEVnpfGOdc4x9jF0cFYn4pdwJ9Mdz6GqgoHLjWH2D1rKH1jEsOFT9ks+QyHRt
KG/q9lyCrzuBo6cYTXU8Mgi9USM+Z70J4NVSFKObOCz/3eJrz4fDe955DEMqhc+V
kmXlyOOdiKy7Pi2bbwIDAQAB
-----END PUBLIC KEY-----
';
    }
}
