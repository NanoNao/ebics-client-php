<?php

namespace EbicsApi\Ebics\Contracts;

use EbicsApi\Ebics\Models\InitializationSegment;

/**
 * EBICS Initialization Transaction representation.
 *
 * Represents an initialization transaction used during the initial setup phase
 * to exchange cryptographic keys and certificates with the bank.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface InitializationTransactionInterface extends TransactionInterface
{
    /**
     * Get the order data containing cryptographic material.
     *
     * @return string
     */
    public function getOrderData(): string;

    /**
     * Get the initialization segment with transaction details.
     *
     * @return InitializationSegment
     */
    public function getInitializationSegment(): InitializationSegment;
}
