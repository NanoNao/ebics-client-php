<?php

namespace EbicsApi\Ebics\Services;

use DOMNameSpaceNode;
use DOMNode;
use DOMNodeList;
use RuntimeException;

/**
 * DOM manipulation helper utilities for safe XML node access.
 *
 * Provides static methods to safely access DOM nodes from DOMNodeList results
 * returned by XPath queries. These helpers prevent null pointer exceptions by
 * validating that queries return expected results before accessing node values.
 *
 * This is essential for parsing EBICS XML responses where certain nodes are
 * expected to exist but XPath queries may return empty results.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 *
 * @internal This class is for internal use and may change without notice.
 */
final class DOMHelper
{
    /**
     * Validate that a DOMNodeList is not empty and return it safely.
     *
     * Used when XPath queries might return false instead of a DOMNodeList.
     * Throws a RuntimeException if the query failed.
     *
     * @param DOMNodeList<DOMNameSpaceNode|DOMNode>|false $domNodeList The result from DOMXPath::query()
     *
     * @return DOMNodeList<DOMNameSpaceNode|DOMNode> The validated DOMNodeList
     * @throws RuntimeException If $domNodeList is false (query failed)
     */
    public static function safeItems($domNodeList): DOMNodeList
    {
        if (false === $domNodeList) {
            throw new RuntimeException('DOM Node List should not be empty.');
        }

        return $domNodeList;
    }

    /**
     * Safely retrieve the first item from a DOMNodeList.
     *
     * Validates that the DOMNodeList is not empty and retrieves the first node.
     * Throws a RuntimeException if the list is empty or the query failed.
     *
     * Use this when you expect exactly one result from an XPath query.
     *
     * @param DOMNodeList<DOMNameSpaceNode|DOMNode>|false $domNodeList The result from DOMXPath::query()
     *
     * @return DOMNameSpaceNode|DOMNode The first node from the list
     * @throws RuntimeException If $domNodeList is false or contains no items
     */
    public static function safeItem($domNodeList): DOMNameSpaceNode|DOMNode
    {
        if (false === $domNodeList) {
            throw new RuntimeException('DOM Node List should not be empty.');
        }
        $domNode = $domNodeList->item(0);
        if ($domNode === null) {
            throw new RuntimeException('DOM Node List should have an item.');
        }

        return $domNode;
    }

    /**
     * Safely retrieve the node value from the first item in a DOMNodeList.
     *
     * Combines safeItem() and accessing nodeValue in one call.
     * Throws a RuntimeException if the list is empty or the query failed.
     *
     * Use this when you need the text content of an expected XML node.
     *
     * @param DOMNodeList<DOMNameSpaceNode|DOMNode>|false $domNodeList The result from DOMXPath::query()
     *
     * @return string The text content of the first node
     * @throws RuntimeException If $domNodeList is false or contains no items
     */
    public static function safeItemValue($domNodeList): string
    {
        $domNode = self::safeItem($domNodeList);

        return $domNode->nodeValue;
    }

    /**
     * Safely retrieve the node value from the first item, or return null if not found.
     *
     * Unlike safeItemValue(), this method returns null instead of throwing an exception
     * when the DOMNodeList is empty. This is useful for optional XML nodes that may
     * or may not be present in the response.
     *
     * @param DOMNodeList<DOMNameSpaceNode|DOMNode>|false $domNodeList The result from DOMXPath::query()
     *
     * @return string|null The text content of the first node, or null if the node doesn't exist
     * @throws RuntimeException If $domNodeList is false (query failed)
     */
    public static function safeItemValueOrNull($domNodeList): ?string
    {
        if ($domNodeList === false) {
            throw new RuntimeException('DOM Node List should not be empty.');
        }

        $domNode = $domNodeList->item(0);

        if ($domNode === null) {
            return null;
        }

        return $domNode->nodeValue;
    }
}
