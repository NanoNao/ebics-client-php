<?php

namespace EbicsApi\Ebics\Contexts;

use EbicsApi\Ebics\Contracts\EbicsClientInterface;
use EbicsApi\Ebics\Contracts\OrderContextInterface;

/**
 * EBICS FDL (File Download) Context - Parameters for file download orders.
 *
 * EBICS Protocol Context:
 * The FDLContext holds order-specific parameters for the FDL (File Download) order.
 * It configures how files are requested from the bank and how the downloaded
 * data should be parsed.
 *
 * FDL Order Parameters:
 * - FileFormat: The format of the file to download (e.g., 'camt.053', 'pain.002')
 * - CountryCode: Country-specific format variant (usually from bank configuration)
 * - Parameters: Additional order-specific parameters (depends on bank/file type)
 * - ParserFormat: How to parse the downloaded data (text, XML, XML files, ZIP files)
 *
 * Parser Format Options:
 * - FILE_PARSER_FORMAT_TEXT: Raw text data (default)
 * - FILE_PARSER_FORMAT_XML: Parse as single XML document
 * - FILE_PARSER_FORMAT_XML_FILES: Extract multiple XML files from archive
 * - FILE_PARSER_FORMAT_ZIP_FILES: Extract files from ZIP archive
 *
 * Typical Usage:
 * $fdlContext = new FDLContext();
 * $fdlContext->setFileFormat('camt.053');  // Bank statement
 * $fdlContext->setCountryCode('DE');       // German format
 *
 * $order = new FDL($fdlContext, $startDate, $endDate);
 * $result = $client->executeDownloadOrder($order);
 *
 * Common File Formats:
 * - camt.052/053/054: Account reports (Cash Management)
 * - pain.002: Payment status report
 * - mt940/942: Account statement (older format)
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class FDLContext extends FFLContext
{
    private string $parserFormat = EbicsClientInterface::FILE_PARSER_FORMAT_TEXT;

    public function setParserFormat(string $parserFormat): self
    {
        $this->parserFormat = $parserFormat;

        return $this;
    }

    public function getParserFormat(): string
    {
        return $this->parserFormat;
    }

    public static function resolveInstance(?OrderContextInterface $orderContext = null): FDLContext
    {
        if ($orderContext instanceof FDLContext) {
            return $orderContext;
        }

        return new FDLContext();
    }
}
