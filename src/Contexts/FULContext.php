<?php

namespace EbicsApi\Ebics\Contexts;

use EbicsApi\Ebics\Contracts\OrderContextInterface;

/**
 * EBICS FUL (File Upload) Context - Parameters for file upload orders.
 *
 * EBICS Protocol Context:
 * The FULContext holds order-specific parameters for the FUL (File Upload) order.
 * It configures how files are formatted and submitted to the bank.
 *
 * FUL Order Parameters:
 * - FileFormat: The format of the file being uploaded (e.g., 'pain.001', 'pain.008')
 * - CountryCode: Country-specific format variant (usually from bank configuration)
 * - Parameters: Additional order-specific parameters (depends on bank/file type)
 *
 * Typical Usage:
 * $fulContext = new FULContext();
 * $fulContext->setFileFormat('pain.001');  // SEPA Credit Transfer
 * $fulContext->setCountryCode('DE');       // German format
 *
 * $orderData = new XmlData($sepaXml);
 * $order = new FUL($fulContext, $orderData);
 * $result = $client->executeUploadOrder($order);
 *
 * Common File Formats:
 * - pain.001: SEPA Credit Transfer (customer credit transfer)
 * - pain.008: SEPA Direct Debit (core and B2B)
 * - pain.001.001.03: Specific pain.001 version
 * - pain.008.001.02: Specific pain.008 version
 *
 * Upload Process:
 * 1. File is validated against XML schema (if XML format)
 * 2. File is split into segments (if large)
 * 3. Segments are encrypted with transaction key
 * 4. Segments are uploaded sequentially to the bank
 * 5. Bank acknowledges receipt
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class FULContext extends FFLContext
{
    public static function resolveInstance(?OrderContextInterface $orderContext = null): FULContext
    {
        if ($orderContext instanceof FULContext) {
            return $orderContext;
        }

        return new FULContext();
    }
}
