<?php

namespace EbicsApi\Ebics\Contracts\Order;

use EbicsApi\Ebics\Models\Order\InitializationOrderResult;

/**
 * EBICS Initialization Order Interface - Contract for cryptographic setup orders.
 *
 * EBICS Protocol Context:
 * Initialization orders are used during the initial setup phase with the bank
 * to exchange cryptographic keys and certificates. These orders establish the
 * trust relationship between the client and the bank.
 *
 * Initialization Order Types:
 * - INI: Send signature A (A005/A006) public key to the bank
 * - HIA: Send signatures E (E002) and X (X002) public keys to the bank
 * - H3K: Send all three signatures (A, E, X) in combined request (v3.0)
 * - HPB: Download bank's public signatures (E and X)
 * - HCS: Upload certificate renewal request
 * - SPR: Suspend/deactivate the current keyring
 *
 * Initialization Sequence:
 * Standard (v2.4/v2.5):
 * 1. INI - Register signature A with bank
 * 2. HIA - Register signatures E and X with bank
 * 3. HPB - Download bank's signatures E and X
 *
 * Combined (v3.0):
 * 1. H3K - Register all three signatures in single request
 * 2. HPB - Download bank's signatures
 *
 * Security Note:
 * Initialization orders are critical for establishing secure communication.
 * They should be executed only once during setup (except HCS for renewal).
 * The exchanged keys are then used for all subsequent transactions.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface InitializationOrderInterface extends OrderInterface
{
    /**
     * Post-processing after initialization order execution.
     *
     * EBICS Protocol Context:
     * Called after successfully completing the initialization order.
     * This is where cryptographic materials are stored in the keyring:
     *
     * For INI: No post-processing (only sends signature A)
     * For HIA: Stores signatures E and X in keyring
     * For HPB: Stores bank's signatures E and X in keyring
     * For H3K: Stores all user signatures in keyring
     * For HCS: Updates renewed certificates in keyring
     * For SPR: Marks keyring as suspended
     *
     * The keyring must be persisted after this call to preserve
     * the cryptographic materials for subsequent sessions.
     *
     * @param InitializationOrderResult $orderResult Contains transaction details and any returned data
     *
     * @return void
     */
    public function afterExecute(InitializationOrderResult $orderResult): void;
}
