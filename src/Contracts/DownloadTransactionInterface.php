<?php

namespace EbicsApi\Ebics\Contracts;

use EbicsApi\Ebics\Models\Http\Response;

/**
 * EBICS Download Transaction representation.
 *
 * Represents a multi-phase download transaction between the bank and the client.
 * Handles segmented retrieval, decryption, and receipt acknowledgment.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface DownloadTransactionInterface extends TransactionInterface
{
    /**
     * Get the transaction ID.
     *
     * @return string|null
     */
    public function getId(): ?string;

    /**
     * Get the receipt response from the bank (acknowledgment).
     *
     * @return Response
     */
    public function getReceipt(): Response;

    /**
     * Get the number of segments in this download transaction.
     *
     * @return int
     */
    public function getNumSegments(): int;

    /**
     * Get the decrypted and reassembled order data.
     *
     * @return string
     */
    public function getOrderData(): string;
}
