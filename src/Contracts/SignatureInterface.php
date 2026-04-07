<?php

namespace EbicsApi\Ebics\Contracts;

use EbicsApi\Ebics\Models\Crypt\Key;

/**
 * EBICS Signature interface.
 *
 * Represents a cryptographic signature used in EBICS protocol communication.
 * Each signature type serves a specific role:
 *
 * - **Type A** (Authentication): Authenticates the user to the bank.
 *   Versions: `A005` (RSA-PKCS#1 v1.5), `A006` (RSA-PSS).
 * - **Type X** (Signing): Signs transaction data. Version: `X002`.
 * - **Type E** (Encryption): Encrypts data transfer. Version: `E002`.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface SignatureInterface
{
    /**
     * Authentication signature version 5 (RSA-PKCS#1 v1.5 with SHA-256).
     */
    const A_VERSION5 = 'A005';

    /**
     * Authentication signature version 6 (RSA-PSS with SHA-256).
     */
    const A_VERSION6 = 'A006';

    /**
     * Encryption signature version 2.
     */
    const E_VERSION2 = 'E002';

    /**
     * Transaction signing signature version 2.
     */
    const X_VERSION2 = 'X002';

    /**
     * Authentication signature type identifier.
     */
    const TYPE_A = 'A';

    /**
     * Transaction signing signature type identifier.
     */
    const TYPE_X = 'X';

    /**
     * Encryption signature type identifier.
     */
    const TYPE_E = 'E';

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @return Key
     */
    public function getPublicKey(): Key;

    /**
     * @return Key|null
     */
    public function getPrivateKey(): ?Key;

    /**
     * @param string|null $certificateContent
     */
    public function setCertificateContent(?string $certificateContent): void;

    /**
     * @return string|null
     */
    public function getCertificateContent(): ?string;
}
