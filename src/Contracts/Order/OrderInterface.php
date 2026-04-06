<?php

namespace EbicsApi\Ebics\Contracts\Order;

use EbicsApi\Ebics\Contexts\RequestContext;
use EbicsApi\Ebics\Factories\RequestFactory;
use EbicsApi\Ebics\Handlers\OrderDataHandler;
use EbicsApi\Ebics\Handlers\UserSignatureHandler;
use EbicsApi\Ebics\Models\Http\Request;

/**
 * EBICS Order Interface - Base contract for all EBICS order types.
 *
 * EBICS Protocol Context:
 * This interface defines the contract for all EBICS order implementations.
 * Each order type (INI, HIA, HPB, FDL, FUL, etc.) implements this interface
 * to provide a standardized way to create and execute EBICS requests.
 *
 * Order Lifecycle:
 * 1. Construction: Order is created with order-specific parameters
 * 2. Context Preparation: prepareContext() sets up the request context
 * 3. Request Creation: createRequest() builds the XML request
 * 4. Execution: Client sends request to bank
 * 5. Post-Processing: afterExecute() handles the response (if applicable)
 *
 * Order Types:
 * - Initialization Orders (INI, HIA, HPB, H3K, HCS, SPR): Setup cryptographic trust
 * - Standard Orders (HEV, HPD, HKD, HTD, HAA, PTK, HAC): Information retrieval
 * - Download Orders (FDL, BTD): File retrieval from bank
 * - Upload Orders (FUL, BTU): File submission to bank
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface OrderInterface
{
    /**
     * Prepare the request context with order-specific parameters.
     *
     * EBICS Protocol Context:
     * This method initializes the RequestContext with parameters required
     * for the specific order type. It is called automatically by the EbicsClient
     * before creating the request.
     *
     * Typical preparation includes:
     * - Setting order type code (INI, HIA, FDL, etc.)
     * - Configuring date ranges for download orders
     * - Setting order-specific parameters (file format, country code)
     * - Validating required parameters
     *
     * @return void
     */
    public function prepareContext(): void;

    /**
     * Get the request context containing order parameters.
     *
     * EBICS Protocol Context:
     * The RequestContext contains all parameters needed to build the EBICS request:
     * - Bank and user identification
     * - Order type and parameters
     * - Transaction state (for multi-phase transactions)
     * - Cryptographic context (keyring, signatures)
     *
     * @return RequestContext The prepared request context
     */
    public function getContext(): RequestContext;

    /**
     * Create the EBICS XML request for this order.
     *
     * EBICS Protocol Context:
     * This method builds the complete XML request structure according to the
     * EBICS protocol specification. The request includes:
     *
     * Request Structure:
     * - Root element with EBICS namespace and version
     * - Header section with:
     *   - Static: Host ID, Partner ID, User ID, order details, security medium
     *   - Mutable: Transaction phase (for multi-phase transactions)
     *   - Bank public key digests (for secured requests)
     * - Body section with:
     *   - DataTransfer: Order data (for upload/initialization orders)
     *   - DataEncryptionInfo: Transaction key (for secured transfers)
     *   - SignatureData: User signature (for authorized orders)
     *
     * Security Levels:
     * - Unsecured: No signatures (HEV only)
     * - Secured: With authentication and encryption
     * - SecuredNoPubKeyDigests: Without bank key digests (HPB)
     *
     * @return Request The HTTP request containing the XML payload
     */
    public function createRequest(): Request;

    /**
     * Inject the request factory for building requests.
     *
     * EBICS Protocol Context:
     * The RequestFactory provides version-specific request building capabilities.
     * Different EBICS versions (2.4, 2.5, 3.0) have slightly different XML structures,
     * and the factory abstracts these differences.
     *
     * @param RequestFactory $requestFactory The factory for creating EBICS requests
     *
     * @return void
     */
    public function useRequestFactory(RequestFactory $requestFactory): void;

    /**
     * Inject the order data handler for processing order-specific XML.
     *
     * EBICS Protocol Context:
     * The OrderDataHandler handles creation and parsing of order-specific XML data,
     * such as SignaturePubKeyOrderData (INI), HIARequestOrderData (HIA),
     * and bank signature data (HPB).
     *
     * @param OrderDataHandler $orderDataHandler The handler for order data processing
     *
     * @return void
     */
    public function useOrderDataHandler(OrderDataHandler $orderDataHandler): void;

    /**
     * Inject the user signature handler for signing requests.
     *
     * EBICS Protocol Context:
     * The UserSignatureHandler creates the user signature (A005/A006) for orders
     * that require authorization. The signature proves the user's authority
     * to execute the order and is required for upload orders and certain downloads.
     *
     * @param UserSignatureHandler $userSignatureHandler The handler for user signatures
     *
     * @return void
     */
    public function useUserSignatureHandler(UserSignatureHandler $userSignatureHandler): void;
}
