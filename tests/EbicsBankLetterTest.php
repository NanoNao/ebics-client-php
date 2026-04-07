<?php

namespace EbicsApi\Ebics\Tests;

use EbicsApi\Ebics\Contracts\EbicsClientInterface;
use EbicsApi\Ebics\EbicsBankLetter;

/**
 * Class EbicsBankLetterTest.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 *
 * @group ebics-bank-letter
 */
class EbicsBankLetterTest extends AbstractEbicsTestCase
{
    /**
     * Prepare bank letter in txt format.
     *
     * @dataProvider clientsDataProvider
     *
     * @group prepare-bank-letter-txt
     *
     * @param int $credentialsId
     * @param string $version
     */
    public function testPrepareBankLetterTxt(int $credentialsId, string $version)
    {
        $client = $this->setupClientFromProvider($credentialsId, $version);
        $ebicsBankLetter = new EbicsBankLetter();

        $bankLetter = $ebicsBankLetter->prepareBankLetter(
            $client->getBank(),
            $client->getUser(),
            $client->getKeyring()
        );

        $txt = $ebicsBankLetter->formatBankLetter($bankLetter, $ebicsBankLetter->createTxtBankLetterFormatter());

        self::assertIsString($txt);
    }

    /**
     * Prepare bank letter in html format.
     *
     * @dataProvider clientsDataProvider
     *
     * @group prepare-bank-letter-html
     *
     * @param int $credentialsId
     * @param string $version
     */
    public function testPrepareBankLetterHtml(int $credentialsId, string $version)
    {
        $client = $this->setupClientFromProvider($credentialsId, $version);
        $ebicsBankLetter = new EbicsBankLetter();

        $bankLetter = $ebicsBankLetter->prepareBankLetter(
            $client->getBank(),
            $client->getUser(),
            $client->getKeyring()
        );

        $html = $ebicsBankLetter->formatBankLetter($bankLetter, $ebicsBankLetter->createHtmlBankLetterFormatter());

        self::assertIsString($html);
    }

    /**
     * Prepare bank letter in pdf format.
     *
     * @dataProvider clientsDataProvider
     *
     * @group prepare-bank-letter-pdf
     *
     * @param int $credentialsId
     * @param string $version
     */
    public function testPrepareBankLetterPdf(int $credentialsId, string $version)
    {
        $client = $this->setupClientFromProvider($credentialsId, $version);
        $ebicsBankLetter = new EbicsBankLetter();

        $bankLetter = $ebicsBankLetter->prepareBankLetter(
            $client->getBank(),
            $client->getUser(),
            $client->getKeyring()
        );

        $pdf = $ebicsBankLetter->formatBankLetter($bankLetter, $ebicsBankLetter->createPdfBankLetterFormatter());

        self::assertIsString($pdf);
    }

    /**
     * Provider for clients.
     *
     * @return array<int, array{int, string}>
     */
    public static function clientsDataProvider(): array
    {
        return [
            [9, 'V24'],
            [2, 'V25'],
            [3, 'V25'],
            [6, 'V30'],
        ];
    }

    /**
     * Setup client from data provider.
     */
    private function setupClientFromProvider(int $credentialsId, string $version): EbicsClientInterface
    {
        return match ($version) {
            'V24' => $this->setupClientV24($credentialsId),
            'V25' => $this->setupClientV25($credentialsId),
            'V30' => $this->setupClientV30($credentialsId),
        };
    }
}
