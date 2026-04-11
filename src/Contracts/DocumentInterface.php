<?php

namespace EbicsApi\Ebics\Contracts;

/**
 * Document interface.
 *
 * Represents a downloadable document retrieved from the bank server
 * during an EBICS transaction. Extends {@see DataInterface} to provide
 * both raw and formatted content access.
 *
 * EBICS Protocol Context:
 * Documents are the result of download orders (e.g., account statements,
 * transaction reports, payment confirmations). They may contain XML,
 * plain text, or binary data (e.g., ZIP archives). The DocumentInterface
 * provides a unified abstraction over various document formats returned
 * by the bank.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface DocumentInterface extends DataInterface
{
}
