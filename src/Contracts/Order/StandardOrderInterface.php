<?php

namespace EbicsApi\Ebics\Contracts\Order;

use EbicsApi\Ebics\Models\Order\StandardOrderResult;

/**
 * EBICS Standard Order Interface - Contract for information retrieval orders.
 *
 * EBICS Protocol Context:
 * Standard orders are used for general information retrieval and administrative tasks.
 * These orders follow a simple request-response pattern without file segmentation
 * or multi-phase transactions.
 *
 * Standard Order Types:
 * - HEV: Get supported EBICS protocol versions
 * - HPD: Download server parameters (bank capabilities)
 * - HKD: Download customer information
 * - HTD: Download subscriber information
 * - HAA: Download available order types
 * - PTK: Download transaction status (plain text)
 * - HAC: Download transaction status (XML)
 *
 * Request Pattern:
 * 1. Create order with parameters
 * 2. Build XML request (with or without signatures)
 * 3. Send to bank via HTTP POST
 * 4. Receive and parse response
 * 5. Process result data
 *
 * Security:
 * Most standard orders require authentication (Signature X) but not electronic
 * signature (Signature A), except for orders returning sensitive data.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface StandardOrderInterface extends OrderInterface
{
    /**
     * Post-processing after order execution.
     *
     * EBICS Protocol Context:
     * This method is called by the EbicsClient after successfully executing
     * the standard order. It allows the order to process the response and
     * update any state (e.g., storing signatures in the keyring).
     *
     * For standard orders, this typically involves:
     * - Extracting response data for further use
     * - Updating keyring with received information (for INI/HIA)
     * - Processing any returned configuration data
     *
     * @param StandardOrderResult $orderResult The order execution result
     *
     * @return void
     */
    public function afterExecute(StandardOrderResult $orderResult): void;
}
