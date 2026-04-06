<?php

namespace EbicsApi\Ebics\Orders;

use EbicsApi\Ebics\Builders\Request\RootBuilder;
use EbicsApi\Ebics\Models\Http\Request;
use EbicsApi\Ebics\Models\Order\StandardOrder;

/**
 * EBICS HEV (Handshake EBICS Version) Order - Get supported protocol versions.
 *
 * EBICS Protocol Context:
 * The HEV order is the simplest EBICS request. It retrieves the list of EBICS
 * protocol versions supported by the bank's EBICS server. This is typically
 * the first request made when connecting to a new bank.
 *
 * Protocol Details:
 * - Order Type: HEV
 * - Order Attribute: None (unsecured request)
 * - Order Data Format: None
 * - Transaction Type: Standard order (single request-response)
 * - Security: No signatures or encryption required
 *
 * Supported EBICS Versions:
 * - VERSION_24: EBICS 2.4 (older version, still widely used)
 * - VERSION_25: EBICS 2.5 (enhanced security features)
 * - VERSION_30: EBICS 3.0 (latest version with improved protocols)
 *
 * HEV Request Structure:
 * - Host ID only (no authentication or encryption)
 * - Minimal XML structure
 *
 * HEV Response Structure:
 * - List of supported protocol versions
 * - May include additional server capabilities
 *
 * Typical Usage:
 * 1. Create Bank object with host ID and URL
 * 2. Execute HEV order to verify connectivity
 * 3. Use returned versions to configure Keyring version
 * 4. Proceed with initialization (INI/HIA/HPB) using appropriate version
 *
 * Note:
 * HEV is the only order that does not require user authentication.
 * All other EBICS orders require proper signatures and key exchange.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class HEV extends StandardOrder
{
    public function createRequest(): Request
    {
        return $this->buildRequest();
    }

    private function buildRequest(): Request
    {
        return $this->requestFactory->createRequestBuilderInstance()
            ->addContainerHEV(function (RootBuilder $builder) {
                $builder->addHostId($this->context->getBank()->getHostId());
            })
            ->popInstance();
    }
}
