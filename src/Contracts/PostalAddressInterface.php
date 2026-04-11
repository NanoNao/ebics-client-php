<?php

namespace EbicsApi\Ebics\Contracts;

use DOMDocument;
use DOMElement;

/**
 * Postal Address interface.
 *
 * Represents a postal (physical) address in the EBICS protocol and
 * provides XML serialization for inclusion in EBICS requests.
 *
 * EBICS Protocol Context:
 * Postal addresses are used in certain EBICS orders (e.g., HKD — download
 * customer information) to identify the address of the account holder,
 * bank, or other parties. The address is serialized as a DOM element
 * conforming to the EBICS XML schema for address structures.
 *
 * Address fields typically include:
 * - Name
 * - Street and building number
 * - Postal code and city
 * - Country code (ISO 3166-1 alpha-2)
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Jonathan Luthi
 */
interface PostalAddressInterface
{
    /**
     * Create a DOM element representing this postal address.
     *
     * Builds and returns a DOMElement conforming to the EBICS XML schema
     * for address structures. The element is created using the provided
     * DOMDocument to ensure proper document ownership.
     *
     * @param DOMDocument $doc The DOMDocument to create the element within
     *
     * @return DOMElement The built DOM element representing the address
     */
    public function toDomElement(DOMDocument $doc): DOMElement;
}
