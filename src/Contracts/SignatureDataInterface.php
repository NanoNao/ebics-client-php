<?php

namespace EbicsApi\Ebics\Contracts;

/**
 * Signature Data interface.
 *
 * Represents the signature data payload included in EBICS order requests.
 * Extends {@see DataInterface} to provide both raw and formatted content
 * access for signature-specific data.
 *
 * EBICS Protocol Context:
 * Signature data contains the cryptographic signature (types A or X) that
 * authenticates and/or authorizes an EBICS order. It is transmitted as part
 * of the ebicsRequest XML body in the static initialization phase (INI, HIA, H3K)
 * or the transaction signing phase (request signature for orders requiring
 * signature A or X).
 *
 * The signature data is typically an RSA signature (PKCS#1 v1.5 or PSS)
 * over a digest of the order data, encoded as a base64 string.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface SignatureDataInterface extends DataInterface
{
}
