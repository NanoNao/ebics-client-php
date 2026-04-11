<?php

namespace EbicsApi\Ebics\Contracts;

use DateTimeInterface;
use DOMDocument;
use DOMElement;
use DOMNode;
use EbicsApi\Ebics\Exceptions\CertificateEbicsException;
use EbicsApi\Ebics\Models\XmlData;
use EbicsApi\Ebics\Models\XmlDocument;

/**
 * Order Data Handler interface.
 *
 * Manages the creation and extraction of OrderData DOM elements
 * for EBICS initialization orders (INI, HIA, H3K, HPB).
 *
 * EBICS Protocol Context:
 * OrderData is the payload of initialization orders that exchange
 * cryptographic keys and certificates between the client and the bank.
 * The handler is responsible for:
 *
 * - **INI (Initialize)**: Sending the user's authentication public key
 *   and X.509 certificate (Signature A)
 * - **HIA (HIA Request)**: Sending encryption (E) and transaction signing (X)
 *   public keys with their certificates
 * - **H3K (H3K Request)**: Sending all three key pairs (A, E, X) in a single
 *   request (EBICS 3.0)
 * - **HPB (HIA Response)**: Extracting the bank's public keys from the response
 *
 * The handler also manages X.509 certificate data within the public key
 * info structures, including issuer serial numbers and certificate content.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface OrderDataHandlerInterface
{
    /**
     * Build and insert the signature public key info into the order data.
     *
     * Creates the XML structure for the authentication signature's (A)
     * public key, including the RSA modulus/exponent and optional X.509
     * certificate data.
     *
     * @param DOMElement $xmlSignaturePubKeyInfo The target XML element to populate
     * @param XmlData $xml The XML document wrapper
     * @param SignatureInterface $certificateA The authentication signature (A) certificate
     * @param DateTimeInterface $dateTime The current date/time for timestamp
     * @param string|null $ns Optional namespace prefix
     *
     * @return void
     */
    public function handleSignaturePubKey(
        DOMElement $xmlSignaturePubKeyInfo,
        XmlData $xml,
        SignatureInterface $certificateA,
        DateTimeInterface $dateTime,
        ?string $ns = null
    ): void;

    /**
     * Build and insert the authentication public key info into the order data.
     *
     * Creates the XML structure for the transaction signing signature's (X)
     * public key, used for request authentication.
     *
     * @param DOMElement $xmlAuthenticationPubKeyInfo The target XML element to populate
     * @param XmlData $xml The XML document wrapper
     * @param SignatureInterface $certificateX The transaction signing signature (X) certificate
     * @param DateTimeInterface $dateTime The current date/time for timestamp
     * @param string|null $ns Optional namespace prefix
     *
     * @return void
     */
    public function handleAuthenticationPubKey(
        DOMElement $xmlAuthenticationPubKeyInfo,
        XmlData $xml,
        SignatureInterface $certificateX,
        DateTimeInterface $dateTime,
        ?string $ns = null
    ): void;

    /**
     * Build and insert the encryption public key info into the order data.
     *
     * Creates the XML structure for the encryption signature's (E)
     * public key, used for data encryption.
     *
     * @param DOMElement $xmlEncryptionPubKeyInfo The target XML element to populate
     * @param XmlData $xml The XML document wrapper
     * @param SignatureInterface $certificateE The encryption signature (E) certificate
     * @param DateTimeInterface $dateTime The current date/time for timestamp
     * @param string|null $ns Optional namespace prefix
     *
     * @return void
     */
    public function handleEncryptionPubKey(
        DOMElement $xmlEncryptionPubKeyInfo,
        XmlData $xml,
        SignatureInterface $certificateE,
        DateTimeInterface $dateTime,
        ?string $ns = null
    ): void;

    /**
     * Add X.509 certificate data to a public key info XML node.
     *
     * Embeds the certificate's issuer serial (name + serial number) and
     * the base64-encoded certificate content into the `<ds:X509Data>` element.
     *
     * @param DOMNode $xmlPublicKeyInfo The parent public key info element
     * @param DOMDocument $xml The DOM document for element creation
     * @param SignatureInterface $certificate The signature with X.509 certificate
     *
     * @return void
     *
     * @throws CertificateEbicsException If the certificate is empty
     */
    public function handleX509Data(DOMNode $xmlPublicKeyInfo, DOMDocument $xml, SignatureInterface $certificate): void;

    /**
     * Add the Partner ID element to the order data.
     *
     * @param DOMNode $xmlOrderData The parent order data element
     * @param DOMDocument $xml The DOM document for element creation
     *
     * @return void
     */
    public function handlePartnerId(DOMNode $xmlOrderData, DOMDocument $xml): void;

    /**
     * Add the User ID element to the order data.
     *
     * @param DOMNode $xmlOrderData The parent order data element
     * @param DOMDocument $xml The DOM document for element creation
     *
     * @return void
     */
    public function handleUserId(DOMNode $xmlOrderData, DOMDocument $xml): void;

    /**
     * Extract the authentication certificate from order data.
     *
     * Parses an XmlDocument to retrieve the user's authentication signature (A)
     * certificate sent during INI/HIA initialization.
     *
     * @param XmlDocument $document The order data document to parse
     *
     * @return SignatureInterface The extracted authentication signature certificate
     */
    public function retrieveAuthenticationSignature(XmlDocument $document): SignatureInterface;

    /**
     * Extract the encryption certificate from order data.
     *
     * Parses an XmlDocument to retrieve the user's encryption signature (E)
     * certificate sent during HIA/H3K initialization.
     *
     * @param XmlDocument $document The order data document to parse
     *
     * @return SignatureInterface The extracted encryption signature certificate
     */
    public function retrieveEncryptionSignature(XmlDocument $document): SignatureInterface;

    /**
     * Compute a SHA-256 hash of the given content.
     *
     * @param string $content The data to hash
     *
     * @return string The binary hash output
     */
    public function hash(string $content): string;
}
