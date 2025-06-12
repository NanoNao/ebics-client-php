<?php

namespace EbicsApi\Ebics\Models\Order;

use EbicsApi\Ebics\Models\XmlData;

/**
 * Order result with extracted data.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class StandardOrderResult extends OrderResult
{
    private XmlData $xmlData;

    public function setXmlData(XmlData $xmlData): void
    {
        $this->xmlData = $xmlData;
    }

    public function getXmlData(): ?XmlData
    {
        return $this->xmlData;
    }
}
