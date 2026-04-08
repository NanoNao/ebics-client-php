<?php

namespace EbicsApi\Ebics\Services;

/**
 * XML parsing and manipulation utilities for EBICS protocol.
 *
 * Provides helper methods to parse XML content and extract individual
 * XML document fragments. This is particularly useful when processing
 * EBICS responses that may contain multiple XML documents embedded
 * within a single response payload (e.g., order data containing multiple
 * XML files from the bank).
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 *
 * @internal This class is for internal use and may change without notice.
 */
final class XmlService
{
    /**
     * Parse an XML string and extract individual XML document fragments.
     *
     * When EBICS responses contain multiple XML documents concatenated together,
     * this method splits them into separate strings by detecting the `<?xml`
     * declaration delimiter.
     *
     * @param string $xmlContent The raw XML string potentially containing multiple XML documents
     *
     * @return string[] Array of individual XML document strings, each trimmed
     */
    public function extractFilesFromString(string $xmlContent): array
    {
        $files = [];

        $delimiter = '<?xml';

        while (($pos = strpos(trim($xmlContent), $delimiter, 1)) !== false && $pos > 0) {
            $files[] = trim(substr($xmlContent, 0, $pos));

            $xmlContent = trim(substr($xmlContent, $pos));
        }

        $files[] = $xmlContent;


        return $files;
    }
}
