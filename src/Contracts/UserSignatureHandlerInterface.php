<?php

namespace EbicsApi\Ebics\Contracts;

use EbicsApi\Ebics\Exceptions\EbicsException;
use EbicsApi\Ebics\Models\UserSignature;

/**
 * User Signature Handler interface.
 *
 * Handles the creation of user-level signatures for EBICS requests,
 * including the Partner ID and User ID binding to the signature value.
 *
 * EBICS Protocol Context:
 * The user signature (also called the static signature or request signature)
 * authenticates the entire EBICS request message. Unlike the AuthSignature
 * (which signs specific XML elements), the user signature covers the complete
 * request header and body digest.
 *
 * The signature process:
 * 1. Compute a digest of the request content
 * 2. Build the `<UserSignature>` XML structure with signature algorithm info
 * 3. Sign the digest with the user's X (authentication) private key
 * 4. Embed Partner ID and User ID for subscriber identification
 * 5. Validate the resulting XML against EBICS schema
 *
 * Different EBICS versions may use different XML structures for the
 * UserSignature element, hence the version-specific implementations.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface UserSignatureHandlerInterface
{
    /**
     * Build and attach the user signature to the request.
     *
     * Computes the signature value from the provided digest, builds the
     * complete UserSignature XML structure (including Partner ID and
     * User ID), and validates the result against the EBICS schema.
     *
     * @param UserSignature $xml The UserSignature XML model to populate
     * @param string $digest The pre-computed digest of the request content
     *
     * @return void
     *
     * @throws EbicsException If signature creation or validation fails
     */
    public function handle(UserSignature $xml, string $digest): void;
}
