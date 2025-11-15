<?php

namespace EbicsApi\Ebics\Tests;

use DateTime;
use EbicsApi\Ebics\Contexts\BTDContext;
use EbicsApi\Ebics\Contexts\BTUContext;
use EbicsApi\Ebics\Contexts\RequestContext;
use EbicsApi\Ebics\Exceptions\InvalidUserOrUserStateException;
use EbicsApi\Ebics\Factories\DocumentFactory;
use EbicsApi\Ebics\Models\Crypt\RSA;
use EbicsApi\Ebics\Orders\BTD;
use EbicsApi\Ebics\Orders\BTU;
use EbicsApi\Ebics\Orders\H3K;
use EbicsApi\Ebics\Orders\HAA;
use EbicsApi\Ebics\Orders\HAC;
use EbicsApi\Ebics\Orders\HEV;
use EbicsApi\Ebics\Orders\HIA;
use EbicsApi\Ebics\Orders\HKD;
use EbicsApi\Ebics\Orders\HPB;
use EbicsApi\Ebics\Orders\HPD;
use EbicsApi\Ebics\Orders\INI;
use EbicsApi\Ebics\Orders\PTK;
use EbicsApi\Ebics\Orders\SPR;

/**
 * Class EbicsClientTest.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 *
 * @group ebics-client
 */
class EbicsClientV30Test extends AbstractEbicsTestCase
{
    /**
     * @dataProvider serversDataProvider
     *
     * @group check-keyring
     */
    public function testCheckKeyring(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV30($credentialsId);

        $this->assertTrue($client->checkKeyring());

        $keyring = $client->getKeyring();
        $keyring->setPassword('incorrect_password');

        $this->assertFalse($client->checkKeyring());
    }

    /**
     * @dataProvider serversDataProvider
     *
     * @group change-keyring-password
     */
    public function testChangeKeyringPassword(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV30($credentialsId);

        $client->changeKeyringPassword('some_new_password');

        $hpb = $client->executeInitializationOrder(new HPB());

        $responseHandler = $client->getResponseHandler();
        $code = $responseHandler->retrieveH00XReturnCode(
            $hpb->getTransaction()->getInitializationSegment()->getResponse()
        );
        $reportText = $responseHandler->retrieveH00XReportText(
            $hpb->getTransaction()->getInitializationSegment()->getResponse()
        );
        $this->assertResponseOk($code, $reportText);
    }

    /**
     * @dataProvider serversDataProvider
     *
     * @group INI-CUSTOM
     * @group INI-V30-CUSTOM
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testINIWithCustomCrt(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV30($credentialsId, $codes['INI']['fake']);

        $client->createUserSignatures([
            'a_version' => 'A005',
            'a_details' => [
                'privatekey' => file_get_contents($this->data . '/certificates/electronic_signature/private.key'),
                'privatekey_type' => RSA::PRIVATE_FORMAT_PKCS1,
                'publickey' => file_get_contents($this->data . '/certificates/electronic_signature/public.key'),
                'publickey_type' => RSA::PUBLIC_FORMAT_PKCS1,
                'certificate' => file_get_contents($this->data . '/certificates/electronic_signature/cert.crt'),
            ],
            'e_details' => [
                'privatekey' => file_get_contents($this->data . '/certificates/authorization_encryption/private.key'),
                'privatekey_type' => RSA::PRIVATE_FORMAT_PKCS1,
                'publickey' => file_get_contents($this->data . '/certificates/authorization_encryption/public.key'),
                'publickey_type' => RSA::PUBLIC_FORMAT_PKCS1,
                'certificate' => file_get_contents($this->data . '/certificates/authorization_encryption/cert.crt'),
            ],
            'x_details' => [
                'privatekey' => file_get_contents($this->data . '/certificates/authorization_encryption/private.key'),
                'privatekey_type' => RSA::PRIVATE_FORMAT_PKCS1,
                'publickey' => file_get_contents($this->data . '/certificates/authorization_encryption/public.key'),
                'publickey_type' => RSA::PUBLIC_FORMAT_PKCS1,
                'certificate' => file_get_contents($this->data . '/certificates/authorization_encryption/cert.crt'),
            ],
        ]);

        // Check that keyring is empty and or wait on success or wait on exception.
        $userExists = $client->getKeyring()->getUserSignatureA();
        if ($userExists) {
            $this->expectException(InvalidUserOrUserStateException::class);
            $this->expectExceptionCode(91002);
        }
        $ini = $client->executeStandardOrder(new INI())->getResponse();
        if (!$userExists) {
            $responseHandler = $client->getResponseHandler();
            $this->saveKeyring($credentialsId, $client->getKeyring());
            $code = $responseHandler->retrieveH00XReturnCode($ini);
            $reportText = $responseHandler->retrieveH00XReportText($ini);
            $this->assertResponseOk($code, $reportText);
        }
    }

    /**
     * @dataProvider serversDataProvider
     *
     * @group HEV
     * @group V3
     * @group HEV-V3
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testHEV(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV30($credentialsId, $codes['HEV']['fake']);

        $hev = $client->executeStandardOrder(new HEV())->getResponse();

        $responseHandler = $client->getResponseHandler();
        $code = $responseHandler->retrieveH000ReturnCode($hev);
        $reportText = $responseHandler->retrieveH000ReportText($hev);
        $this->assertResponseOk($code, $reportText);
    }

    /**
     * @dataProvider serversDataProvider
     *
     * @group INI
     * @group INI-V3
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testINI(int $credentialsId, array $codes): void
    {
        $withIssuer = false;

        if ($withIssuer) {
            $client = $this->setupClientV30($credentialsId);
            $issuer = $client->generateIssuerCertificate();
        }

        $client = $this->setupClientV30($credentialsId, $codes['INI']['fake']);

        if ($withIssuer) {
            $x509Generator = $client->getKeyring()->getCertificateGenerator();
            if ($x509Generator) {
                $this->setupIssuer($x509Generator, $issuer, $client->getKeyring()->getPassword());
            }
        }

        // Check that keyring is empty and or wait on success or wait on exception.
        $userExists = $client->getKeyring()->getUserSignatureA();
        if ($userExists) {
            $this->expectException(InvalidUserOrUserStateException::class);
            $this->expectExceptionCode(91002);
        }
        $ini = $client->executeStandardOrder(new INI())->getResponse();
        if (!$userExists) {
            $responseHandler = $client->getResponseHandler();
            $this->saveKeyring($credentialsId, $client->getKeyring());
            $code = $responseHandler->retrieveH00XReturnCode($ini);
            $reportText = $responseHandler->retrieveH00XReportText($ini);
            $this->assertResponseOk($code, $reportText);
        }
    }

    /**
     * @dataProvider serversDataProvider
     *
     * @group HIA
     * @group HIA-V3
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testHIA(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV30($credentialsId, $codes['HIA']['fake']);

        // Check that keyring is empty and or wait on success or wait on exception.
        $bankExists = $client->getKeyring()->getUserSignatureX();
        if ($bankExists) {
            $this->expectException(InvalidUserOrUserStateException::class);
            $this->expectExceptionCode(91002);
        }
        $hia = $client->executeStandardOrder(new HIA())->getResponse();
        if (!$bankExists) {
            $responseHandler = $client->getResponseHandler();
            $this->saveKeyring($credentialsId, $client->getKeyring());
            $code = $responseHandler->retrieveH00XReturnCode($hia);
            $reportText = $responseHandler->retrieveH00XReportText($hia);
            $this->assertResponseOk($code, $reportText);
        }
    }

    /**
     * @dataProvider serversDataProvider
     *
     * @group H3K
     * @group V3
     * @group H3K-V3
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testH3K(int $credentialsId, array $codes): void
    {
        if (false === isset($codes['H3K'])) {
            $this->markTestSkipped(sprintf('No H3K test for bank credential %d', $credentialsId));
        }

        $client = $this->setupClientV30($credentialsId, $codes['H3K']['fake']);

        // Check that keyring is empty and or wait on success or wait on exception.
        $bankExists = $client->getKeyring()->getUserSignatureX();
        if ($bankExists) {
            $this->expectException(InvalidUserOrUserStateException::class);
            $this->expectExceptionCode(91002);
        }
        $h3k = $client->executeStandardOrder(new H3K())->getResponse();
        if (!$bankExists) {
            $responseHandler = $client->getResponseHandler();
            $this->saveKeyring($credentialsId, $client->getKeyring());
            $code = $responseHandler->retrieveH00XReturnCode($h3k);
            $reportText = $responseHandler->retrieveH00XReportText($h3k);
            $this->assertResponseOk($code, $reportText);
        }
    }

    /**
     * Run first INI & HIA and Activate account in the bank panel.
     *
     * @dataProvider serversDataProvider
     *
     * @group HPB
     * @group V3
     * @group HPB-V3
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testHPB(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV30($credentialsId, $codes['HPB']['fake']);

        $this->assertExceptionCode($codes['HPB']['code']);

        $hpb = $client->executeInitializationOrder(new HPB());

        $responseHandler = $client->getResponseHandler();
        $code = $responseHandler->retrieveH00XReturnCode(
            $hpb->getTransaction()->getInitializationSegment()->getResponse()
        );
        $reportText = $responseHandler->retrieveH00XReportText(
            $hpb->getTransaction()->getInitializationSegment()->getResponse()
        );
        $this->assertResponseOk($code, $reportText);
        $this->saveKeyring($credentialsId, $client->getKeyring());
    }

    /**
     * @dataProvider serversDataProvider
     *
     * @group SPR
     * @group SPR-V30
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testSPR(int $credentialsId, array $codes): void
    {
        $this->markTestSkipped('Avoid keyring suspension.');

        $client = $this->setupClientV30($credentialsId, $codes['SPR']['fake']);

        $this->assertExceptionCode($codes['SPR']['code']);
        $spr = $client->executeUploadOrder(new SPR());

        $responseHandler = $client->getResponseHandler();

        $code = $responseHandler->retrieveH00XReturnCode($spr->getTransaction()->getInitialization()->getResponse());
        $reportText = $responseHandler->retrieveH00XReportText(
            $spr->getTransaction()->getInitialization()->getResponse()
        );

        $this->assertResponseOk($code, $reportText);
    }

    /**
     * @dataProvider serversDataProvider
     *
     * @group HKD
     * @group HKD-V3
     * @group V3
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testHKD(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV30($credentialsId, $codes['HKD']['fake']);

        $this->assertExceptionCode($codes['HKD']['code']);
        $hkd = $client->executeDownloadOrder(new HKD());

        $responseHandler = $client->getResponseHandler();
        $code = $responseHandler->retrieveH00XReturnCode($hkd->getTransaction()->getLastSegment()->getResponse());
        $reportText = $responseHandler->retrieveH00XReportText($hkd->getTransaction()->getLastSegment()->getResponse());
        $this->assertResponseOk($code, $reportText);

        $code = $responseHandler->retrieveH00XReturnCode($hkd->getTransaction()->getReceipt());
        $reportText = $responseHandler->retrieveH00XReportText($hkd->getTransaction()->getReceipt());

        $this->assertResponseDone($code, $reportText);
    }

    /**
     * @dataProvider serversDataProvider
     *
     * @group HPD
     * @group V3
     * @group HPD-V3
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testHPD(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV30($credentialsId, $codes['HPD']['fake']);

        $this->assertExceptionCode($codes['HPD']['code']);
        $hpd = $client->executeDownloadOrder(new HPD());

        $responseHandler = $client->getResponseHandler();
        $code = $responseHandler->retrieveH00XReturnCode($hpd->getTransaction()->getLastSegment()->getResponse());
        $reportText = $responseHandler->retrieveH00XReportText($hpd->getTransaction()->getLastSegment()->getResponse());
        $this->assertResponseOk($code, $reportText);

        $code = $responseHandler->retrieveH00XReturnCode($hpd->getTransaction()->getReceipt());
        $reportText = $responseHandler->retrieveH00XReportText($hpd->getTransaction()->getReceipt());

        $this->assertResponseDone($code, $reportText);
    }

    /**
     * @dataProvider serversDataProvider
     *
     * @group HAA
     * @group V3
     * @group HAA-V3
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testHAA(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV30($credentialsId, $codes['HAA']['fake']);

        $this->assertExceptionCode($codes['HAA']['code']);
        $haa = $client->executeDownloadOrder(new HAA());

        $responseHandler = $client->getResponseHandler();
        $code = $responseHandler->retrieveH00XReturnCode($haa->getTransaction()->getLastSegment()->getResponse());
        $reportText = $responseHandler->retrieveH00XReportText($haa->getTransaction()->getLastSegment()->getResponse());
        $this->assertResponseOk($code, $reportText);

        $code = $responseHandler->retrieveH00XReturnCode($haa->getTransaction()->getReceipt());
        $reportText = $responseHandler->retrieveH00XReportText($haa->getTransaction()->getReceipt());

        $this->assertResponseDone($code, $reportText);
    }

    /**
     * @dataProvider serversDataProvider
     *
     * @group PTK
     * @group V3
     * @group PTK-V3
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testPTK(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV30($credentialsId, $codes['PTK']['fake']);

        $this->assertExceptionCode($codes['PTK']['code']);
        $ptk = $client->executeDownloadOrder(new PTK());

        $responseHandler = $client->getResponseHandler();
        $code = $responseHandler->retrieveH00XReturnCode($ptk->getTransaction()->getLastSegment()->getResponse());
        $reportText = $responseHandler->retrieveH00XReportText($ptk->getTransaction()->getLastSegment()->getResponse());
        $this->assertResponseOk($code, $reportText);

        $code = $responseHandler->retrieveH00XReturnCode($ptk->getTransaction()->getReceipt());
        $reportText = $responseHandler->retrieveH00XReportText($ptk->getTransaction()->getReceipt());

        $this->assertResponseDone($code, $reportText);
    }

    /**
     * @dataProvider serversDataProvider
     *
     * @group HAC
     * @group V3
     * @group HAC-V3
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testHAC(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV30($credentialsId, $codes['HAC']['fake']);

        $this->assertExceptionCode($codes['HAC']['code']);
        $hac = $client->executeDownloadOrder(new HAC());

        $responseHandler = $client->getResponseHandler();
        $code = $responseHandler->retrieveH00XReturnCode($hac->getTransaction()->getLastSegment()->getResponse());
        $reportText = $responseHandler->retrieveH00XReportText($hac->getTransaction()->getLastSegment()->getResponse());
        $this->assertResponseOk($code, $reportText);

        $code = $responseHandler->retrieveH00XReturnCode($hac->getTransaction()->getReceipt());
        $reportText = $responseHandler->retrieveH00XReportText($hac->getTransaction()->getReceipt());

        $this->assertResponseDone($code, $reportText);
    }

    /**
     * @dataProvider serversDataProvider
     *
     * @group BTD
     * @group V3
     * @group BTD-V3
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testBTD(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV30($credentialsId, $codes['BTD']['fake']);

        $context = BTDContext::resolveInstance()
            ->setServiceName('PSR')
            ->setMsgName('pain.002')
            ->setMsgNameVersion('03')
            ->setScope('CH')
            ->setContainerType('ZIP');

        $this->assertExceptionCode($codes['BTD']['code']);
        $btd = $client->executeDownloadOrder(new BTD($context, new DateTime('2020-03-21'), new DateTime('2020-04-21')));
        $responseHandler = $client->getResponseHandler();
        $code = $responseHandler->retrieveH00XReturnCode($btd->getTransaction()->getLastSegment()->getResponse());
        $reportText = $responseHandler->retrieveH00XReportText($btd->getTransaction()->getLastSegment()->getResponse());
        $this->assertResponseOk($code, $reportText);

        $code = $responseHandler->retrieveH00XReturnCode($btd->getTransaction()->getReceipt());
        $reportText = $responseHandler->retrieveH00XReportText($btd->getTransaction()->getReceipt());

        $this->assertResponseDone($code, $reportText);
    }

    /**
     * @dataProvider serversDataProvider
     *
     * @group BTU
     * @group V3
     * @group BTU-V3
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testBTU(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV30($credentialsId, $codes['BTU']['fake']);

        $this->assertExceptionCode($codes['BTU']['code']);

        $orderData = $this->buildCustomerCreditTransfer('urn:iso:std:iso:20022:tech:xsd:pain.001.001.09');

        // XE2
        $btuContext = BTUContext::resolveInstance()
            ->setServiceName('MCT')
            ->setScope('CH')
            ->setMsgName('pain.001')
            ->setMsgNameVersion('09')
            ->setFileName('xe2.pain001.xml');

        $context = new RequestContext();
        $context->setDateTime(new DateTime());

        $btu = $client->executeUploadOrder(new BTU($btuContext, $orderData, $context));

        $responseHandler = $client->getResponseHandler();
        $code = $responseHandler->retrieveH00XReturnCode($btu->getTransaction()->getLastSegment()->getResponse());
        $reportText = $responseHandler->retrieveH00XReportText($btu->getTransaction()->getLastSegment()->getResponse());
        $this->assertResponseOk($code, $reportText);

        $code = $responseHandler->retrieveH00XReturnCode($btu->getTransaction()->getInitialization()->getResponse());
        $reportText = $responseHandler->retrieveH00XReportText(
            $btu->getTransaction()->getInitialization()->getResponse()
        );

        $this->assertResponseOk($code, $reportText);
    }

    /**
     * @dataProvider serversDataProvider
     *
     * @group BTU
     * @group V3
     * @group BTU-V3
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testBTUv2(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV30($credentialsId, $codes['BTU']['fake']);

        $this->assertExceptionCode($codes['BTU']['code']);

        $orderData = $this->buildCustomerCreditTransferV2('pain.001.001.09_GBIC_5');

        // XE2
        $btuContext = BTUContext::resolveInstance()
            ->setServiceName('MCT')
            ->setScope('CH')
            ->setMsgName('pain.001')
            ->setMsgNameVersion('09')
            ->setFileName('xe2.pain001.xml');

        $context = new RequestContext();
        $context->setDateTime(new DateTime());

        $btu = $client->executeUploadOrder(new BTU($btuContext, $orderData, $context));

        $responseHandler = $client->getResponseHandler();
        $code = $responseHandler->retrieveH00XReturnCode($btu->getTransaction()->getLastSegment()->getResponse());
        $reportText = $responseHandler->retrieveH00XReportText($btu->getTransaction()->getLastSegment()->getResponse());
        $this->assertResponseOk($code, $reportText);

        $code = $responseHandler->retrieveH00XReturnCode($btu->getTransaction()->getInitialization()->getResponse());
        $reportText = $responseHandler->retrieveH00XReportText(
            $btu->getTransaction()->getInitialization()->getResponse()
        );

        $this->assertResponseOk($code, $reportText);
    }

    /**
     * @dataProvider serversDataProvider
     *
     * @group CSV
     * @group V3
     * @group CSV-V3
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testCSV(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV30($credentialsId, $codes['CSV']['fake']);

        $this->assertExceptionCode($codes['CSV']['code']);

        $orderData = (new DocumentFactory())->createTxt("Username;Identifier\r\nbooker12;9012");

        // CSV
        $context = BTUContext::resolveInstance()
            ->setServiceName('OTH')
            ->setScope('BIL')
            ->setMsgName('csv')
            ->setServiceOption('CH002LMF')
            ->setFileName('file.csv');

        $btu = $client->executeUploadOrder(new BTU($context, $orderData));

        $responseHandler = $client->getResponseHandler();
        $code = $responseHandler->retrieveH00XReturnCode($btu->getTransaction()->getLastSegment()->getResponse());
        $reportText = $responseHandler->retrieveH00XReportText($btu->getTransaction()->getLastSegment()->getResponse());
        $this->assertResponseOk($code, $reportText);

        $code = $responseHandler->retrieveH00XReturnCode($btu->getTransaction()->getInitialization()->getResponse());
        $reportText = $responseHandler->retrieveH00XReportText(
            $btu->getTransaction()->getInitialization()->getResponse()
        );

        $this->assertResponseOk($code, $reportText);
    }

    /**
     * Provider for servers.
     */
    public function serversDataProvider()
    {
        return [
            [
                6, // Credentials Id.
                [
                    'HEV' => ['code' => null, 'fake' => false],
                    'INI' => ['code' => null, 'fake' => false],
                    'HIA' => ['code' => null, 'fake' => false],
                    'SPR' => ['code' => null, 'fake' => true],
                    'H3K' => ['code' => null, 'fake' => false],
                    'HPB' => ['code' => null, 'fake' => false],
                    'HKD' => ['code' => null, 'fake' => false],
                    'BTD' => ['code' => '090005', 'fake' => false],
                    'BTU' => ['code' => null, 'fake' => false],
                    'CSV' => ['code' => null, 'fake' => false],
                    'HPD' => ['code' => null, 'fake' => false],
                    'HAA' => ['code' => null, 'fake' => false],
                    'PTK' => ['code' => null, 'fake' => false],
                    'HAC' => ['code' => null, 'fake' => false],
                ],
            ],
            [
                7, // Credentials Id.
                [
                    'HEV' => ['code' => null, 'fake' => false],
                    'INI' => ['code' => null, 'fake' => false],
                    'HIA' => ['code' => null, 'fake' => false],
                    'SPR' => ['code' => null, 'fake' => true],
                    'HPB' => ['code' => null, 'fake' => false],
                    'HKD' => ['code' => null, 'fake' => false],
                    'BTD' => ['code' => '090005', 'fake' => false],
                    'BTU' => ['code' => null, 'fake' => false],
                    'CSV' => ['code' => '091005', 'fake' => false],
                    'HPD' => ['code' => null, 'fake' => false],
                    'HAA' => ['code' => null, 'fake' => false],
                    'PTK' => ['code' => null, 'fake' => false],
                    'HAC' => ['code' => null, 'fake' => false],
                ],
            ],
        ];
    }
}
