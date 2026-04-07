<?php

namespace EbicsApi\Ebics\Tests\Factories\X509;

use EbicsApi\Ebics\Services\X509\X509OptionsNormalizer;
use EbicsApi\Ebics\Tests\AbstractEbicsTestCase;

/**
 * Legacy X509 certificate generator @see X509GeneratorInterface.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Guillaume Sainthillier
 */
class X509ExtensionOptionsNormalizerTest extends AbstractEbicsTestCase
{
    /**
     * @dataProvider getOptions
     *
     * @param mixed $value
     * @param mixed $expected
     */
    public function testOptions($value, $expected): void
    {
        $actualValue = X509OptionsNormalizer::normalizeExtensions($value);

        self::assertEquals($expected, $actualValue);
    }

    /**
     * @return array<int, array{mixed, mixed}>
     */
    public static function getOptions(): array
    {
        return [
            ['foo', ['value' => 'foo', 'critical' => false, 'replace' => true]],
            [['value' => 'foo'], ['value' => 'foo', 'critical' => false, 'replace' => true]],
            [['foo'], ['value' => ['foo'], 'critical' => false, 'replace' => true]],
            [['value' => ['foo']], ['value' => ['foo'], 'critical' => false, 'replace' => true]],
            [['value' => ['foo'], 'critical' => false], ['value' => ['foo'], 'critical' => false, 'replace' => true]],
            [['value' => ['foo'], 'critical' => true], ['value' => ['foo'], 'critical' => true, 'replace' => true]],
            [
                ['value' => ['foo'], 'critical' => false, 'replace' => false],
                ['value' => ['foo'], 'critical' => false, 'replace' => false]
            ],
            [
                ['value' => ['foo'], 'critical' => true, 'replace' => true],
                ['value' => ['foo'], 'critical' => true, 'replace' => true]
            ],
        ];
    }
}
