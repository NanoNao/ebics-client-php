<?php

namespace EbicsApi\Ebics\Tests\Factories\X509;

use DateTime;
use EbicsApi\Ebics\Factories\Crypt\RSAFactory;
use EbicsApi\Ebics\Factories\SignatureFactory;
use EbicsApi\Ebics\Models\Bank;
use EbicsApi\Ebics\Models\Crypt\Key;
use EbicsApi\Ebics\Models\Crypt\KeyPair;
use EbicsApi\Ebics\Models\Crypt\RSA;
use EbicsApi\Ebics\Models\X509\BankX509Generator;
use EbicsApi\Ebics\Tests\AbstractEbicsTestCase;

/**
 * Legacy X509 certificate generator @see X509GeneratorInterface.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Guillaume Sainthillier, Andrew Svirin
 *
 * @group x509-generator
 */
class X509GeneratorTest extends AbstractEbicsTestCase
{

    /**
     * @group generate-bank-certificate-content
     */
    public function testGenerateBankCertificateContent()
    {
        $privateKey = new Key($this->getPrivateKey(), RSA::PRIVATE_FORMAT_PKCS1);
        $publicKey = new Key($this->getPublicKey(), RSA::PUBLIC_FORMAT_PKCS1);

        // Certificate generated for the 22/03/2020 (1 year validity)
        $x509Generator = new BankX509Generator();
        $x509Generator->setCertificateOptionsByBank(new Bank('H123456', 'https://test.bank.dom'));
        $x509Context = $x509Generator->getAX509Context();
        $x509Context->setStartDate(new DateTime('2020-03-21'));
        $x509Context->setEndDate(new DateTime('2021-03-22'));
        $x509Context->setSerialNumber('539453510852155194065233908413342789156542395956670254476154968597583055940');
        $rsaFactory = new RSAFactory();
        $x509Context->setIssuerPublicKey($rsaFactory->createPublic($publicKey));
        $x509Context->setIssuerPrivateKey($rsaFactory->createPrivate($privateKey, 'test123'));

        $signatureFactory = new SignatureFactory(new RSAFactory());
        $signature = $signatureFactory->createSignatureAFromKeys(
            new KeyPair($publicKey, $privateKey, 'test123'),
            $x509Generator
        );

        self::assertEquals($signature->getPrivateKey()->getKey(), $privateKey->getKey());
        self::assertEquals($signature->getPublicKey()->getKey(), $publicKey->getKey());
        $this->assertCertificateEquals(
            $signature->getCertificateContent(),
            $this->getCertificateContent()
        );
    }

    /**
     * @param string $generatedContent
     * @param string $fileContent
     */
    private function assertCertificateEquals(string $generatedContent, string $fileContent)
    {
        $generatedInfos = openssl_x509_parse($generatedContent);
        $certificateInfos = openssl_x509_parse($fileContent);

        self::assertEquals($generatedInfos['subject'], $certificateInfos['subject']);
        self::assertEquals($generatedInfos['issuer'], $certificateInfos['issuer']);
        self::assertEquals(
            DateTime::createFromFormat(
                'U',
                $generatedInfos['validFrom_time_t']
            )->format('d/m/Y'),
            DateTime::createFromFormat('U', $certificateInfos['validFrom_time_t'])->format('d/m/Y')
        );
        self::assertEquals(
            DateTime::createFromFormat(
                'U',
                $generatedInfos['validTo_time_t']
            )->format('d/m/Y'),
            DateTime::createFromFormat(
                'U',
                $certificateInfos['validTo_time_t']
            )->format('d/m/Y')
        );
        self::assertEquals($generatedInfos['extensions'], $certificateInfos['extensions']);
    }

    /**
     * @return string
     */
    private function getCertificateContent()
    {
        return '-----BEGIN CERTIFICATE-----
MIIClDCCAf2gAwIBAgJLNTM5NDUzNTEwODUyMTU1MTk0MDY1MjMzOTA4NDEzMzQy
Nzg5MTU2NTQyMzk1OTU2NjcwMjU0NDc2MTU0OTY4NTk3NTgzMDU1OTQwMA0GCSqG
SIb3DQEBCwUAMDExCzAJBgNVBAYMAkVVMRMwEQYDVQQDDAoqLmJhbmsuZG9tMQ0w
CwYDVQQKDARCYW5rMB4XDTIwMDMyMTAwMDAwMFoXDTIxMDMyMjAwMDAwMFowMTEL
MAkGA1UEBgwCRVUxEzARBgNVBAMMCiouYmFuay5kb20xDTALBgNVBAoMBEJhbmsw
gZ8wDQYJKoZIhvcNAQEBBQADgY0AMIGJAoGBAIzB7E84N4lzCyS7IiMipDakOQjq
TgcRWel8Y51zjH2MXRwVifil3An0x3PoaqCgcuNYfYPWsofWMSw4VP2Sz5DIdG0o
b+r2XIKvO4GjpxhNdTwyCL1RIz5nvQng1VIUo5s4LP/d4mvPh8N73nkMQyqFz5WS
ZeXI452IrLs+LZtvAgMBAAGjcjBwMB0GA1UdDgQWBBREAqUl0PKgjXu8AEk9bu5f
sVn5NzAJBgNVHRMEAjAAMBMGA1UdJQQMMAoGCCsGAQUFBwMEMA4GA1UdDwEB/wQE
AwIGQDAfBgNVHSMEGDAWgBREAqUl0PKgjXu8AEk9bu5fsVn5NzANBgkqhkiG9w0B
AQsFAAOBgQBD0STyIBabCiF2Xpg59bdEWO+8vtVSqAle7ssb2os/aiMaGs9YV5HY
uFgfmZRGEOwtPoCgPK8iNO5lIQ3VGxpZLHk4/NwV3yDUzgRJzHkJY6+qnR/ALKiK
DM+AGa/BdG5Xgy6Prg4KYSsfP+iPeOKezI2tukNGYLxb8U879FmuVg==
-----END CERTIFICATE-----';
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
