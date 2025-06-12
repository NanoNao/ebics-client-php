<?php

namespace EbicsApi\Ebics\Tests;

use DateTime;
use EbicsApi\Ebics\Contexts\FDLContext;
use EbicsApi\Ebics\Contexts\FULContext;
use EbicsApi\Ebics\Contexts\RequestContext;
use EbicsApi\Ebics\Exceptions\InvalidUserOrUserStateException;
use EbicsApi\Ebics\Factories\DocumentFactory;
use EbicsApi\Ebics\Orders\FDL;
use EbicsApi\Ebics\Orders\FUL;
use EbicsApi\Ebics\Orders\HAA;
use EbicsApi\Ebics\Orders\HCS;
use EbicsApi\Ebics\Orders\HEV;
use EbicsApi\Ebics\Orders\HIA;
use EbicsApi\Ebics\Orders\HKD;
use EbicsApi\Ebics\Orders\HPB;
use EbicsApi\Ebics\Orders\HPD;
use EbicsApi\Ebics\Orders\HTD;
use EbicsApi\Ebics\Orders\INI;
use EbicsApi\Ebics\Orders\PTK;
use EbicsApi\Ebics\Orders\SPR;
use Silarhi\Cfonb\CfonbParser;

/**
 * Class EbicsClientTest.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 *
 * @group V25
 */
class EbicsClientV25Test extends AbstractEbicsTestCase
{
    /**
     * @dataProvider serversDataProvider
     *
     * @group check-keyring
     */
    public function testCheckKeyring(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV25($credentialsId);

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
        $client = $this->setupClientV25($credentialsId);

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
     * @group HEV
     * @group HEV-V25
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testHEV(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV25($credentialsId, $codes['HEV']['fake']);
        $hev = $client->executeStandardOrder(new HEV())->getXmlData();

        $responseHandler = $client->getResponseHandler();
        $code = $responseHandler->retrieveH000ReturnCode($hev);
        $reportText = $responseHandler->retrieveH000ReportText($hev);
        $this->assertResponseOk($code, $reportText);
    }

    /**
     * @dataProvider serversDataProvider
     *
     * @group INI
     * @group INI-V25
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testINI(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV25($credentialsId, $codes['INI']['fake']);

        // Check that keyring is empty and or wait on success or wait on exception.
        $userExists = $client->getKeyring()->getUserSignatureA();
        if ($userExists) {
            $this->expectException(InvalidUserOrUserStateException::class);
            $this->expectExceptionCode(91002);
        }
        $ini = $client->executeStandardOrder(new INI())->getXmlData();
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
     * @group HIA-V25
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testHIA(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV25($credentialsId, $codes['HIA']['fake']);

        // Check that keyring is empty and or wait on success or wait on exception.
        $bankExists = $client->getKeyring()->getUserSignatureX();
        if ($bankExists) {
            $this->expectException(InvalidUserOrUserStateException::class);
            $this->expectExceptionCode(91002);
        }
        $hia = $client->executeStandardOrder(new HIA())->getXmlData();
        if (!$bankExists) {
            $responseHandler = $client->getResponseHandler();
            $this->saveKeyring($credentialsId, $client->getKeyring());
            $code = $responseHandler->retrieveH00XReturnCode($hia);
            $reportText = $responseHandler->retrieveH00XReportText($hia);
            $this->assertResponseOk($code, $reportText);
        }
    }

    /**
     * Run first HIA and Activate account in bank panel.
     *
     * @dataProvider serversDataProvider
     *
     * @group HPB
     * @group HPB-V25
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testHPB(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV25($credentialsId, $codes['HPB']['fake']);

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
     * @group HCS
     * @group HCS-V25
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testHCS(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV25($credentialsId, $codes['HCS']['fake']);

        $this->assertExceptionCode($codes['HCS']['code']);

        $newKeyring = clone $client->getKeyring();

        $hcs = $client->executeUploadOrder(new HCS($newKeyring));

        $responseHandler = $client->getResponseHandler();
        $code = $responseHandler->retrieveH00XReturnCode($hcs->getTransaction()->getLastSegment()->getResponse());
        $reportText = $responseHandler->retrieveH00XReportText($hcs->getTransaction()->getLastSegment()->getResponse());
        $this->assertResponseOk($code, $reportText);

        $code = $responseHandler->retrieveH00XReturnCode($hcs->getTransaction()->getInitialization()->getResponse());
        $reportText = $responseHandler->retrieveH00XReportText(
            $hcs->getTransaction()->getInitialization()->getResponse()
        );

        $this->assertResponseOk($code, $reportText);
    }

    /**
     * @dataProvider serversDataProvider
     *
     * @group SPR
     * @group SPR-V25
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testSPR(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV25($credentialsId, $codes['SPR']['fake']);

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
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testHKD(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV25($credentialsId, $codes['HKD']['fake']);

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
     * @group HTD
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testHTD(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV25($credentialsId, $codes['HTD']['fake']);

        $this->assertExceptionCode($codes['HTD']['code']);
        $htd = $client->executeDownloadOrder(new HTD());

        $responseHandler = $client->getResponseHandler();
        $code = $responseHandler->retrieveH00XReturnCode($htd->getTransaction()->getLastSegment()->getResponse());
        $reportText = $responseHandler->retrieveH00XReportText($htd->getTransaction()->getLastSegment()->getResponse());
        $this->assertResponseOk($code, $reportText);

        $code = $responseHandler->retrieveH00XReturnCode($htd->getTransaction()->getReceipt());
        $reportText = $responseHandler->retrieveH00XReportText($htd->getTransaction()->getReceipt());

        $this->assertResponseDone($code, $reportText);
    }

    /**
     * @dataProvider serversDataProvider
     *
     * @group PTK
     * @group PTK-V2
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testPTK(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV25($credentialsId, $codes['PTK']['fake']);

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
     * @group HPD
     * @group HPD-V25
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testHPD(int $credentialsId, array $codes): void
    {
        $client = $this->setupClientV25($credentialsId, $codes['HPD']['fake']);

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
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testHAA(int $credentialsId, array $codes)
    {
        $client = $this->setupClientV25($credentialsId, $codes['HAA']['fake']);

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
     * @group FDL
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testFDL(int $credentialsId, array $codes): void
    {
        foreach ($codes['FDL'] as $fileFormat => $code) {
            $client = $this->setupClientV25($credentialsId, $code['fake']);

            $this->assertExceptionCode($code['code']);

            $context = (new FDLContext())
                ->setFileFormat($fileFormat)
                ->setParameter('TEST', 'TRUE')
                ->setCountryCode('FR');

            $fdl = $client->executeDownloadOrder(
                new FDL(
                    $context,
                    new DateTime('2020-03-21'),
                    new DateTime('2020-04-21')
                )
            );

            $parser = new CfonbParser();
            switch ($fileFormat) {
                case 'camt.xxx.cfonb120.stm':
                    $statements = $parser->read120C($fdl->getData());
                    self::assertNotEmpty($statements);
                    break;
                case 'camt.xxx.cfonb240.act':
                    $statements = $parser->read240C($fdl->getData());
                    self::assertNotEmpty($statements);
                    break;
            }

            $responseHandler = $client->getResponseHandler();
            $code = $responseHandler->retrieveH00XReturnCode($fdl->getTransaction()->getLastSegment()->getResponse());
            $reportText = $responseHandler->retrieveH00XReportText(
                $fdl->getTransaction()->getLastSegment()->getResponse()
            );
            $this->assertResponseOk($code, $reportText);

            $code = $responseHandler->retrieveH00XReturnCode($fdl->getTransaction()->getReceipt());
            $reportText = $responseHandler->retrieveH00XReportText($fdl->getTransaction()->getReceipt());

            $this->assertResponseDone($code, $reportText);
        }
    }

    /**
     * @dataProvider serversDataProvider
     *
     * @group FUL
     * @group FUL-V25
     *
     * @param int $credentialsId
     * @param array $codes
     *
     * @covers
     */
    public function testFUL(int $credentialsId, array $codes)
    {
        $documentFactory = new DocumentFactory();
        foreach ($codes['FUL'] as $fileFormat => $code) {
            $client = $this->setupClientV25($credentialsId, $code['fake']);

            $this->assertExceptionCode($code['code']);

            $context = (new FULContext())
                ->setFileFormat($fileFormat)
                ->setParameter('TEST', 'TRUE')
                ->setCountryCode('FR');

            $ful = $client->executeUploadOrder(
                new FUL(
                    $context,
                    $documentFactory->createXml($code['document'])
                )
            );

            $responseHandler = $client->getResponseHandler();
            $code = $responseHandler->retrieveH00XReturnCode($ful->getTransaction()->getLastSegment()->getResponse());
            $reportText = $responseHandler->retrieveH00XReportText(
                $ful->getTransaction()->getLastSegment()->getResponse()
            );
            $this->assertResponseOk($code, $reportText);

            $code = $responseHandler->retrieveH00XReturnCode(
                $ful->getTransaction()->getInitialization()->getResponse()
            );
            $reportText = $responseHandler->retrieveH00XReportText(
                $ful->getTransaction()->getInitialization()->getResponse()
            );

            $this->assertResponseOk($code, $reportText);
        }
    }

    /**
     * Provider for servers.
     */
    public function serversDataProvider()
    {
        return [
            [
                1, // Credentials Id.
                [
                    'HEV' => ['code' => null, 'fake' => false],
                    'INI' => ['code' => null, 'fake' => false],
                    'HIA' => ['code' => null, 'fake' => false],
                    'H3K' => ['code' => null, 'fake' => false],
                    'HCS' => ['code' => null, 'fake' => false],
                    'HPB' => ['code' => null, 'fake' => false],
                    'SPR' => ['code' => null, 'fake' => true],
                    'HPD' => ['code' => null, 'fake' => false],
                    'HKD' => ['code' => null, 'fake' => false],
                    'HTD' => ['code' => null, 'fake' => false],
                    'HAA' => ['code' => null, 'fake' => false],
                    'PTK' => ['code' => null, 'fake' => false],
                    'FDL' => [
                        'camt.xxx.cfonb120.stm' => ['code' => '091112', 'fake' => false],
                    ],
                    'FUL' => [
                        'pain.001.001.03.sct' => [
                            'code' => '091112',
                            'fake' => false,
                            'document' => '<?xml version="1.0" encoding="UTF-8"?><Root></Root>',
                        ],
                    ],
                ],
            ],
            [
                2, // Credentials Id.
                [
                    'HEV' => ['code' => null, 'fake' => false],
                    'INI' => ['code' => null, 'fake' => false],
                    'HIA' => ['code' => null, 'fake' => false],
                    'H3K' => ['code' => null, 'fake' => false],
                    'HCS' => ['code' => '091301', 'fake' => false],
                    'HPB' => ['code' => null, 'fake' => false],
                    'SPR' => ['code' => null, 'fake' => true],
                    'HPD' => ['code' => null, 'fake' => false],
                    'HKD' => ['code' => null, 'fake' => false],
                    'HTD' => ['code' => null, 'fake' => false],
                    'HAA' => ['code' => '091006', 'fake' => false],
                    'PTK' => ['code' => '090005', 'fake' => false],
                    'FDL' => [
                        'camt.xxx.cfonb120.stm' => ['code' => '090005', 'fake' => false],
                        'camt.xxx.cfonb240.act' => ['code' => '090005', 'fake' => false],
                    ],
                    'FUL' => [
                        'pain.001.001.03.sct' => [
                            'code' => null,
                            'fake' => false,
                            'document' => '<?xml version="1.0" encoding="UTF-8"?><Root></Root>',
                        ],
                    ],
                ],
            ],
            [
                3, // Credentials Id.
                [
                    'HEV' => ['code' => null, 'fake' => false],
                    'INI' => ['code' => null, 'fake' => false],
                    'HIA' => ['code' => null, 'fake' => false],
                    'H3K' => ['code' => null, 'fake' => false],
                    'HCS' => ['code' => null, 'fake' => false],
                    'HPB' => ['code' => null, 'fake' => false],
                    'SPR' => ['code' => null, 'fake' => true],
                    'HPD' => ['code' => null, 'fake' => false],
                    'HKD' => ['code' => null, 'fake' => false],
                    'HTD' => ['code' => null, 'fake' => false],
                    'HAA' => ['code' => null, 'fake' => false],
                    'PTK' => ['code' => null, 'fake' => false],
                    'FDL' => [
                        'camt.xxx.cfonb120.stm' => ['code' => '091112', 'fake' => false],
                    ],
                    'FUL' => [
                        'pain.001.001.03.sct' => [
                            'code' => '091112',
                            'fake' => false,
                            'document' => '<?xml version="1.0" encoding="UTF-8"?><Root></Root>',
                        ],
                    ],
                ],
            ],
            [
                4, // Credentials Id.
                [
                    'HEV' => ['code' => null, 'fake' => false],
                    'INI' => ['code' => null, 'fake' => false],
                    'HIA' => ['code' => null, 'fake' => false],
                    'H3K' => ['code' => null, 'fake' => false],
                    'HCS' => ['code' => null, 'fake' => false],
                    'HPB' => ['code' => null, 'fake' => false],
                    'SPR' => ['code' => null, 'fake' => true],
                    'HPD' => ['code' => null, 'fake' => false],
                    'HKD' => ['code' => null, 'fake' => false],
                    'HTD' => ['code' => null, 'fake' => false],
                    'HAA' => ['code' => null, 'fake' => false],
                    'PTK' => ['code' => null, 'fake' => false],
                    'FDL' => [
                        'camt.xxx.cfonb120.stm' => ['code' => '091112', 'fake' => false],
                    ],
                    'FUL' => [
                        'pain.001.001.03.sct' => [
                            'code' => '091112',
                            'fake' => false,
                            'document' => '<?xml version="1.0" encoding="UTF-8"?><Root></Root>',
                        ],
                    ],
                ],
            ],
            [
                5, // Credentials Id.
                [
                    'HEV' => ['code' => null, 'fake' => false],
                    'INI' => ['code' => null, 'fake' => false],
                    'HIA' => ['code' => null, 'fake' => false],
                    'H3K' => ['code' => null, 'fake' => false],
                    'HCS' => ['code' => null, 'fake' => false],
                    'HPB' => ['code' => null, 'fake' => false],
                    'SPR' => ['code' => null, 'fake' => true],
                    'HPD' => ['code' => null, 'fake' => false],
                    'HKD' => ['code' => null, 'fake' => false],
                    'HTD' => ['code' => null, 'fake' => false],
                    'HAA' => ['code' => null, 'fake' => false],
                    'PTK' => ['code' => null, 'fake' => false],
                    'FDL' => [
                        'camt.xxx.cfonb120.stm' => ['code' => '091112', 'fake' => false],
                    ],
                    'FUL' => [
                        'pain.001.001.03.sct' => [
                            'code' => '091112',
                            'fake' => false,
                            'document' => '<?xml version="1.0" encoding="UTF-8"?><Root></Root>',
                        ],
                    ],
                ],
            ],
        ];
    }
}
