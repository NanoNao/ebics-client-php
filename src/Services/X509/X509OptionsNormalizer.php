<?php

namespace EbicsApi\Ebics\Services\X509;

/**
 * Normalizer for X.509 certificate extension options.
 *
 * Provides utility methods to normalize and denormalize X.509 certificate
 * extension options and Distinguished Names (DN). This ensures consistent
 * option formatting when creating X.509 certificates via phpseclib.
 *
 * The normalizer handles various input formats for extension options:
 * - Simple string values (converted to default structure)
 * - Arrays with optional 'critical' and 'replace' flags
 *
 * @see \EbicsApi\Ebics\Models\Crypt\X509::setExtension()
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Guillaume Sainthillier, Andrew Svirin
 *
 * @internal This class is for internal use and may change without notice.
 */
final class X509OptionsNormalizer
{
    /**
     * Normalize X.509 extension options to a consistent structure.
     *
     * Accepts flexible input formats and returns a standardized array with:
     * - 'value': The extension value (required)
     * - 'critical': Whether the extension is critical (default: false)
     * - 'replace': Whether to replace existing extensions (default: true)
     *
     * @param mixed|string|array $options Extension options in various formats
     *
     * @return array{value: mixed, critical: bool, replace: bool} Normalized options array
     *
     * @see \EbicsApi\Ebics\Models\Crypt\X509::setExtension()
     */
    public static function normalizeExtensions($options): array
    {
        $critical = false;
        $replace = true;

        if (!is_array($options)) {
            $value = $options;
        } else {
            if (!isset($options['value'])) {
                $value = $options;
            } else {
                $value = $options['value'];
                if (isset($options['critical'])) {
                    $critical = $options['critical'];
                }
                if (isset($options['replace'])) {
                    $replace = $options['replace'];
                }
            }
        }

        return [
            'value' => $value,
            'critical' => $critical,
            'replace' => $replace,
        ];
    }

    /**
     * Denormalize an X.509 Distinguished Name (DN) from ASN.1 structure.
     *
     * Converts the RDN sequence from an X.509 certificate's subject or issuer
     * into a simple key-value array for easier access to DN components
     * (e.g., CN, O, OU, C).
     *
     * @param array{rdnSequence: array<int, array<int, array{type: string, value: array<mixed>}>>} $options
     *                                       The RDN sequence from X.509 certificate
     *
     * @return array<string, mixed> Associative array mapping DN types to their values
     *                              (e.g., ['id-at-commonName' => 'example.com'])
     */
    public static function denormalizeDN(array $options): array
    {
        $result = [];
        foreach ($options['rdnSequence'] as $rdnSequence) {
            $result[$rdnSequence[0]['type']] = reset($rdnSequence[0]['value']);
        }

        return $result;
    }
}
