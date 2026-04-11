<?php

namespace EbicsApi\Ebics\Contracts;

/**
 * Order Data interface.
 *
 * Represents the data payload of an EBICS order request or response.
 * Extends {@see DataInterface} to provide both raw and formatted content
 * access for order-specific data.
 *
 * EBICS Protocol Context:
 * Order data is the core content exchanged in EBICS transactions. It may
 * contain:
 * - Payment order XML (pain.001, pain.008, etc.) for upload orders
 * - Account statement XML (camt.053, camt.052, etc.) for download orders
 * - Status reports (PTK, HAC) in plain text or XML format
 *
 * The data may be compressed (ZIP), encrypted, or encoded depending on
 * the order type and bank configuration. OrderDataInterface provides a
 * unified access point regardless of the underlying format.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface OrderDataInterface extends DataInterface
{
}
