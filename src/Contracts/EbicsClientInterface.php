<?php

namespace EbicsApi\Ebics\Contracts;

use EbicsApi\Ebics\Contracts\Order\DownloadOrderInterface;
use EbicsApi\Ebics\Contracts\Order\InitializationOrderInterface;
use EbicsApi\Ebics\Contracts\Order\StandardOrderInterface;
use EbicsApi\Ebics\Contracts\Order\UploadOrderInterface;
use EbicsApi\Ebics\Handlers\ResponseHandler;
use EbicsApi\Ebics\Models\Bank;
use EbicsApi\Ebics\Models\Keyring;
use EbicsApi\Ebics\Models\Order\DownloadOrderResult;
use EbicsApi\Ebics\Models\Order\InitializationOrderResult;
use EbicsApi\Ebics\Models\Order\StandardOrderResult;
use EbicsApi\Ebics\Models\Order\UploadOrderResult;
use EbicsApi\Ebics\Models\User;

/**
 * EBICS client representation.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface EbicsClientInterface
{
    public const FILE_PARSER_FORMAT_TEXT = 'text';
    public const FILE_PARSER_FORMAT_XML = 'xml';
    public const FILE_PARSER_FORMAT_XML_FILES = 'xml_files';
    public const FILE_PARSER_FORMAT_ZIP_FILES = 'zip_files';

    /**
     * Create user signatures A (Authorization), E (Encryption), and X (Authentication) on first launch.
     *
     * EBICS Protocol Context:
     * This method initializes the cryptographic key pairs required for EBICS communication.
     * It generates three types of signatures:
     * - Signature A (A005/A006): Used for authorization/order signing
     * - Signature E (E002): Used for encryption of order data
     * - Signature X (X002): Used for authentication of requests
     *
     * The method should be called once during initial setup before any EBICS transactions.
     * It creates the key pairs and stores them in the keyring for subsequent use.
     *
     * @param array<string, mixed>|null $options Setup to specify custom certificate, private, public keys and version
     *   for Electronic Signature (E), Authorization and Identification (A), Encryption details.
     *   Supported options:
     *   - 'x509_generator': Custom X509GeneratorInterface implementation
     *   - 'signature_a_version': Version for signature A (A005 or A006)
     *   - 'signature_e_version': Version for signature E (E002)
     *   - 'signature_x_version': Version for signature X (X002)
     *   - 'certificate': Custom X.509 certificate
     *   - 'private_key': Custom private key
     *   - 'public_key': Custom public key
     */
    public function createUserSignatures(?array $options = null): void;

    /**
     * Generate X.509 certificate for issuer (certificate authority).
     *
     * EBICS Protocol Context:
     * Creates a self-signed X.509 certificate that can be used as the issuer certificate
     * for signing user certificates in the EBICS protocol. This is part of the certificate
     * infrastructure required for secure EBICS communication with certified banks.
     *
     * The certificate is used in the INI/HIA/H3K initialization processes.
     *
     * @return array Certificate data array containing:
     *   - 'certificate': The X.509 certificate in PEM format
     *   - 'private_key': The corresponding private key
     *   - 'public_key': The corresponding public key
     */
    public function generateIssuerCertificate(): array;

    /**
     * Execute an EBICS initialization order.
     *
     * EBICS Protocol Context:
     * Initialization orders are used during the initial setup phase with the bank to exchange
     * cryptographic keys and certificates. These orders establish the trust relationship
     * between the client and the bank.
     *
     * The typical initialization sequence is: INI → HIA → HPB (or H3K → HPB for version 3.0).
     *
     * @param InitializationOrderInterface $order The initialization order to execute
     *
     * @return InitializationOrderResult Result containing order execution status and any returned data
     */
    public function executeInitializationOrder(InitializationOrderInterface $order): InitializationOrderResult;

    /**
     * Execute an EBICS standard order.
     *
     * EBICS Protocol Context:
     * Standard orders are used for general information retrieval and administrative tasks.
     * These orders do not involve file transfer and typically return metadata or status information.
     *
     * Supported standard order types:
     * - HEV: Get supported EBICS protocol versions from the bank
     * - HPD: Download server parameters (bank capabilities and configuration)
     * - HKD: Download customer information (account details, partner data)
     * - HTD: Download subscriber information (user permissions and settings)
     * - HAA: Download available order types that the customer is authorized to use
     * - PTK: Download transaction status report in plain text format
     * - HAC: Download transaction status report in XML format
     *
     * Standard orders follow the request-response pattern without file segmentation.
     *
     * @param StandardOrderInterface $order The standard order to execute
     *
     * @return StandardOrderResult Result containing order execution status and returned data
     */
    public function executeStandardOrder(StandardOrderInterface $order): StandardOrderResult;

    /**
     * Execute an EBICS download order.
     *
     * EBICS Protocol Context:
     * Download orders are used to retrieve files and data from the bank. The download process
     * follows a multi-phase transaction model:
     *
     * 1. Initialization Phase: Client requests download with order type and date range
     * 2. Distribution Phase: Bank prepares the requested data
     * 3. Retrieval Phase: Client retrieves the data in segments (if large)
     * 4. Receipt Phase: Client confirms successful receipt (optional for some order types)
     *
     * The order data may be compressed (ZIP), encrypted, and split into multiple segments
     * for large files. The client handles automatic decompression and reassembly.
     *
     * @param DownloadOrderInterface $order The download order to execute
     *
     * @return DownloadOrderResult Result containing downloaded order data, transaction details, and file contents
     */
    public function executeDownloadOrder(DownloadOrderInterface $order): DownloadOrderResult;

    /**
     * Execute an EBICS upload order.
     *
     * EBICS Protocol Context:
     * Upload orders are used to send files and data to the bank. The upload process
     * follows a multi-phase transaction model:
     *
     * 1. Initialization Phase: Client sends upload request with order metadata
     * 2. Transfer Phase: Client uploads the order data (may be segmented for large files)
     * 3. Receipt Phase: Bank confirms receipt with transaction status
     *
     * The order data is automatically compressed and may be encrypted before transmission.
     * Large files are automatically segmented according to EBICS protocol limits.
     *
     * @param UploadOrderInterface $order The upload order to execute
     *
     * @return UploadOrderResult Result containing upload transaction status and bank receipt
     */
    public function executeUploadOrder(UploadOrderInterface $order): UploadOrderResult;

    /**
     * Get the keyring containing cryptographic keys and certificates.
     *
     * EBICS Protocol Context:
     * The keyring stores all cryptographic materials required for EBICS communication:
     * - User signatures (A, E, X) - key pairs for authorization, encryption, and authentication
     * - Bank signatures - bank's public keys received via HPB order
     * - X.509 certificates - for certified EBICS communication
     * - Transaction keys - AES keys for encrypting order data
     *
     * The keyring should be persisted between sessions using a KeyringManager.
     *
     * @return Keyring The keyring object containing all signatures and certificates
     */
    public function getKeyring(): Keyring;

    /**
     * Get the bank configuration and connection details.
     *
     * EBICS Protocol Context:
     * The Bank object contains:
     * - Host ID: Unique identifier for the EBICS server
     * - URL: Endpoint URL for EBICS communication
     * - Country code: Bank's country code for protocol-specific behavior
     *
     * @return Bank The bank configuration object
     */
    public function getBank(): Bank;

    /**
     * Get the user (subscriber) configuration.
     *
     * EBICS Protocol Context:
     * The User object represents an EBICS subscriber (participant) and contains:
     * - Partner ID: Identifies the contracting partner with the bank
     * - User ID: Identifies the specific user/subscriber within the partner
     *
     * Together with the Bank, this forms the unique identification for EBICS sessions.
     *
     * @return User The user configuration object
     */
    public function getUser(): User;

    /**
     * Get the response handler for manual response processing.
     *
     * EBICS Protocol Context:
     * The ResponseHandler provides access to the raw XML response from the bank,
     * allowing custom processing of:
     * - Response codes and messages
     * - Transaction IDs
     * - Order data extraction
     * - Error handling and diagnostics
     *
     * This is primarily useful for advanced use cases requiring direct access
     * to the EBICS response structure.
     *
     * @return ResponseHandler The response handler object
     */
    public function getResponseHandler(): ResponseHandler;

    /**
     * Validate that the keyring contains valid and complete cryptographic material.
     *
     * EBICS Protocol Context:
     * Checks whether the keyring has all required signatures and certificates
     * for EBICS communication. This includes verification of:
     * - User signature A (authorization) - required for signed orders
     * - User signature E (encryption) - required for encrypted orders
     * - User signature X (authentication) - required for all orders
     * - Bank signatures - required for verifying bank responses
     *
     * Should be called after initial setup or when loading a persisted keyring
     * to ensure the client is ready for EBICS communication.
     *
     * @return bool True if keyring is valid, false if initialization is needed
     */
    public function checkKeyring(): bool;

    /**
     * Update the encryption password for the keyring storage.
     *
     * EBICS Protocol Context:
     * The keyring password is used to encrypt the private keys when persisting
     * them to storage (file, database, etc.). This method allows changing
     * the password without regenerating the cryptographic key pairs.
     *
     * Important: This does NOT change the EBICS signatures known to the bank.
     * It only changes the local storage encryption password.
     *
     * @param string $newPassword The new encryption password for keyring storage
     *
     * @return void
     */
    public function changeKeyringPassword(string $newPassword): void;
}
