<?php

namespace EbicsApi\Ebics\Contracts\Order;

use EbicsApi\Ebics\Models\Order\DownloadOrderResult;

/**
 * EBICS Download Order Interface - Contract for file retrieval orders.
 *
 * EBICS Protocol Context:
 * Download orders are used to retrieve files and data from the bank. The download
 * process follows a multi-phase transaction model with automatic segmentation
 * for large files.
 *
 * Download Transaction Flow:
 * 1. Initialization Phase: Request download with order type and date range
 * 2. Distribution Phase: Bank prepares the data (handled by bank)
 * 3. Retrieval Phase: Download all segments (automatic for large files)
 * 4. Decryption Phase: Decrypt and decompress the order data
 * 5. Receipt Phase: Send acknowledgment to the bank
 *
 * Data Processing:
 * - Downloaded data may be base64 encoded
 * - Data is encrypted with transaction key (AES)
 * - Data may be ZIP compressed
 * - Multiple parser formats available (text, XML, files)
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface DownloadOrderInterface extends OrderInterface
{
    /**
     * Get the parser format for processing downloaded data.
     *
     * EBICS Protocol Context:
     * The parser format determines how the downloaded data is processed
     * after decryption and decompression. Different file types require
     * different parsing strategies.
     *
     * Supported formats:
     * - FILE_PARSER_FORMAT_TEXT: Raw text (default, for MT940, etc.)
     * - FILE_PARSER_FORMAT_XML: Single XML document (for camt.053, etc.)
     * - FILE_PARSER_FORMAT_XML_FILES: Multiple XML files (for archives)
     * - FILE_PARSER_FORMAT_ZIP_FILES: ZIP archive contents
     *
     * @return string The parser format constant
     */
    public function getParserFormat(): string;

    /**
     * Post-processing after download order execution.
     *
     * EBICS Protocol Context:
     * Called after successfully completing the download transaction.
     * At this point, the data has been:
     * - Downloaded in segments (if large)
     * - Reassembled into complete file
     * - Decrypted with transaction key
     * - Decompressed from ZIP format
     * - Parsed according to the specified format
     *
     * @param DownloadOrderResult $orderResult Contains downloaded file data and documents
     *
     * @return void
     */
    public function afterExecute(DownloadOrderResult $orderResult): void;
}
