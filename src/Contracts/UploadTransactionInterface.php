<?php

namespace EbicsApi\Ebics\Contracts;

use EbicsApi\Ebics\Models\TransferSegment;

/**
 * EBICS Upload Transaction representation.
 *
 * Represents a multi-phase upload transaction between the client and the bank.
 * Handles file segmentation and sequential upload of encrypted data segments.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface UploadTransactionInterface extends TransactionInterface
{
    /**
     * Get the number of segments in this upload transaction.
     *
     * @return int
     */
    public function getNumSegments(): int;

    /**
     * Get all transfer segments for this transaction.
     *
     * @return TransferSegment[]
     */
    public function getSegments(): array;
}
