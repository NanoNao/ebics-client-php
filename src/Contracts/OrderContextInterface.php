<?php

namespace EbicsApi\Ebics\Contracts;

/**
 * EBICS Order Context interface.
 *
 * Marker interface for order context objects that hold parameters
 * and state required for building and executing EBICS orders.
 *
 * An order context encapsulates the configuration and runtime data needed
 * when constructing EBICS order requests. It serves as a container for
 * order-specific parameters such as date ranges, file formats, pagination
 * settings, and other metadata that influence how an order is built and
 * processed by the bank server.
 *
 * Implementations may carry context through the order lifecycle:
 * from request construction, through XML generation, to response parsing.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface OrderContextInterface
{
}
