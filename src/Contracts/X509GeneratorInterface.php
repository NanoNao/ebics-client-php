<?php

namespace EbicsApi\Ebics\Contracts;

use EbicsApi\Ebics\Contracts\Crypt\X509Interface;
use EbicsApi\Ebics\Models\X509\X509Context;

/**
 * X.509 Certificate Generator interface.
 *
 * Generates and manages X.509 certificates for the three EBICS signature types
 * (A, E, X) as well as the issuer (certificate authority) certificate. These
 * certificates are required for certified EBICS communication with banks that
 * enforce X.509-based authentication.
 *
 * EBICS Protocol Context:
 * X.509 certificates provide an additional layer of trust beyond raw RSA keys.
 * Each signature type requires its own certificate:
 * - **Signature A** (A005/A006): Certificate for authorization/order signing
 * - **Signature E** (E002): Certificate for encryption of order data
 * - **Signature X** (X002): Certificate for request authentication
 *
 * The issuer certificate acts as a self-signed certificate authority (CA)
 * that signs the user certificates. This is typically generated during the
 * INI (Initialize) phase of EBICS setup.
 *
 * Certificate generation follows the EBICS specification for certificate
 * structure, including distinguished name (DN) formatting, validity periods,
 * and extension attributes required by participating banks.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin, Guillaume Sainthillier
 */
interface X509GeneratorInterface
{
    /**
     * Generate an X.509 certificate for Signature A (Authentication/Authorization).
     *
     * Creates a certificate bound to the user's authentication public key.
     * This certificate is sent to the bank during the INI (Initialize) order
     * and is used to sign order requests, proving the sender's identity.
     *
     * @return X509Interface The generated X.509 certificate instance
     */
    public function generateAX509(): X509Interface;

    /**
     * Generate an X.509 certificate for Signature E (Encryption).
     *
     * Creates a certificate bound to the user's encryption public key.
     * This certificate is used to encrypt order data before transmission,
     * ensuring confidentiality of sensitive financial data.
     *
     * @return X509Interface The generated X.509 certificate instance
     */
    public function generateEX509(): X509Interface;

    /**
     * Generate an X.509 certificate for Signature X (Transaction Signing).
     *
     * Creates a certificate bound to the user's transaction signing public key.
     * This certificate is used for the two-step transaction model, where
     * requests are signed separately from the order data.
     *
     * @return X509Interface The generated X.509 certificate instance
     */
    public function generateXX509(): X509Interface;

    /**
     * Generate the issuer (certificate authority) X.509 certificate.
     *
     * Creates a self-signed issuer certificate using the configured public
     * and private keys. This certificate acts as a local certificate authority
     * (CA) that signs the user's A, E, and X certificates.
     *
     * The issuer certificate is generated once and reused across all user
     * certificate generations.
     *
     * @return X509Interface The generated issuer X.509 certificate instance
     */
    public function generateIssuerX509(): X509Interface;

    /**
     * Get the X.509 context (configuration) for Signature A.
     *
     * Returns the X509Context containing the distinguished name (DN), validity
     * period, and other certificate attributes specific to the authentication
     * certificate.
     *
     * @return X509Context The X.509 configuration context for Signature A
     */
    public function getAX509Context(): X509Context;

    /**
     * Get the X.509 context (configuration) for Signature E.
     *
     * Returns the X509Context containing the distinguished name (DN), validity
     * period, and other certificate attributes specific to the encryption
     * certificate.
     *
     * @return X509Context The X.509 configuration context for Signature E
     */
    public function getEX509Context(): X509Context;

    /**
     * Get the X.509 context (configuration) for Signature X.
     *
     * Returns the X509Context containing the distinguished name (DN), validity
     * period, and other certificate attributes specific to the transaction
     * signing certificate.
     *
     * @return X509Context The X.509 configuration context for Signature X
     */
    public function getXX509Context(): X509Context;

    /**
     * Get the X.509 context (configuration) for the issuer certificate.
     *
     * Returns the X509Context containing the distinguished name (DN), validity
     * period, and other certificate attributes for the self-signed issuer
     * (certificate authority) certificate.
     *
     * @return X509Context The X.509 configuration context for the issuer
     */
    public function getIssuerX509Context(): X509Context;
}
