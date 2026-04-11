<?php

namespace EbicsApi\Ebics\Contracts;

use DOMDocument;
use DOMNode;
use EbicsApi\Ebics\Exceptions\EbicsException;

/**
 * Authentication Signature Handler interface.
 *
 * Handles the creation and insertion of the `AuthSignature` XML element
 * into EBICS request documents. This signature authenticates the
 * request using the user's X (transaction signing) signature.
 *
 * EBICS Protocol Context:
 * The AuthSignature is an XML Digital Signature (XML-DSig) that proves
 * the authenticity of an EBICS request. It is inserted after the `<header>`
 * element and signs all XML elements marked with `authenticate="true"`.
 *
 * The signature process:
 * 1. Canonicalize the signed info section (XML-C14N)
 * 2. Compute SHA-256 digest of the canonicalized data
 * 3. Encrypt the digest with the user's X private key (RSA)
 * 4. Embed the signature value in the `<ds:SignatureValue>` element
 *
 * Different EBICS versions use different signature algorithms:
 * - EBICS 2.4/2.5: RSA-PKCS#1 v1.5 with SHA-256
 * - EBICS 3.0: May use RSA-PSS with SHA-256
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface AuthSignatureHandlerInterface
{
    /**
     * Add the AuthSignature element and its children to the request DOM.
     *
     * Signs all elements with attribute `authenticate="true"` and inserts
     * the completed AuthSignature block after the Header section.
     *
     * The method performs the following steps:
     * 1. Creates the `<AuthSignature>` element
     * 2. Builds `<ds:SignedInfo>` with canonicalization and signature methods
     * 3. Computes the digest value for all authenticated elements
     * 4. Encrypts the signed info hash with the user's X private key
     * 5. Embeds the signature value
     *
     * @param DOMDocument $dom The request DOM document to sign
     * @param DOMNode|null $xmlRequestHeader Optional header element to insert after.
     *                                       If null, the handler locates the Header automatically.
     *
     * @return void
     *
     * @throws EbicsException If signature creation fails
     */
    public function handle(DOMDocument $dom, ?DOMNode $xmlRequestHeader = null): void;
}
