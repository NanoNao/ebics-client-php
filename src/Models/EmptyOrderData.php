<?php

namespace EbicsApi\Ebics\Models;

use EbicsApi\Ebics\Contracts\OrderDataInterface;

/**
 * Class EmptyOrderData.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class EmptyOrderData extends DOMDocument implements OrderDataInterface
{
    public const CONTENT = ' ';

    public function getContent(): string
    {
        return self::CONTENT;
    }

    public function getFormattedContent(): string
    {
        return self::CONTENT;
    }
}
