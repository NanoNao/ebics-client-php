<?php

namespace EbicsApi\Ebics\Contracts\Order;

use EbicsApi\Ebics\Contracts\OrderDataInterface;
use EbicsApi\Ebics\Handlers\OrderDataHandler;
use EbicsApi\Ebics\Models\Order\UploadOrderResult;
use EbicsApi\Ebics\Models\UploadTransaction;

/**
 * EBICS Upload Order Interface - Contract for file submission orders.
 *
 * EBICS Protocol Context:
 * Upload orders are used to send files and data to the bank. The upload process
 * follows a multi-phase transaction model with automatic segmentation for large files.
 *
 * Upload Order Types:
 * - FUL: File Upload - Generic file submission (SEPA payments, etc.)
 * - BTU: BTF Upload - Upload in Bank Technical Format structure
 *
 * Upload Transaction Flow:
 * 1. Initialization Phase: Request upload, receive transaction key
 * 2. Transfer Phase: Upload order data in encrypted segments
 * 3. Receipt Phase: Bank confirms receipt with transaction status
 *
 * Security Requirements:
 * - Order data must be signed with user signature (A005/A006)
 * - Order data is encrypted with transaction key (AES)
 * - Data integrity verified with digest (SHA-256)
 * - XML schema validation before upload (for XML formats)
 *
 * Automatic Processing:
 * - Large files split into segments (CHUNK_SIZE)
 * - Each segment encrypted separately
 * - Segments uploaded sequentially
 * - Bank acknowledges each segment
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface UploadOrderInterface extends OrderInterface
{
    /**
     * Inject the order data handler for processing order-specific XML.
     *
     * @param OrderDataHandler $orderDataHandler The handler for order data processing
     */
    public function useOrderDataHandler(OrderDataHandler $orderDataHandler): void;

    /**
     * Set the upload transaction context with transaction key and metadata.
     *
     * EBICS Protocol Context:
     * Called during the upload process to inject the transaction context
     * containing the transaction key received from the bank during
     * the initialization phase.
     *
     * @param UploadTransaction $transaction The upload transaction with transaction key
     *
     * @return void
     */
    public function setTransaction(UploadTransaction $transaction): void;

    /**
     * Get the order data to be uploaded.
     *
     * EBICS Protocol Context:
     * Returns the OrderDataInterface containing the file content to upload.
     * The data will be:
     * 1. Validated against XML schema (if XML format)
     * 2. Split into segments (if larger than CHUNK_SIZE)
     * 3. Encrypted with transaction key
     * 4. Uploaded to the bank sequentially
     *
     * @return OrderDataInterface The order data with file content
     */
    public function getOrderData(): OrderDataInterface;

    /**
     * Post-processing after upload order execution.
     *
     * EBICS Protocol Context:
     * Called after successfully completing the upload transaction.
     * At this point, all segments have been:
     * - Uploaded to the bank
     * - Acknowledged by the bank
     * - Confirmed with final transaction status
     *
     * @param UploadOrderResult $orderResult Contains upload transaction status and receipt
     *
     * @return void
     */
    public function afterExecute(UploadOrderResult $orderResult): void;
}
