<?php

namespace EbicsApi\Ebics\Builders\Request;

use DateTimeInterface;
use EbicsApi\Ebics\Contexts\BTDContext;
use EbicsApi\Ebics\Contexts\BTUContext;
use EbicsApi\Ebics\Contexts\HVDContext;
use EbicsApi\Ebics\Contexts\HVEContext;
use EbicsApi\Ebics\Contexts\HVTContext;
use LogicException;

/**
 * Ebics 2.5 Class OrderDetailsBuilder builder for request container.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class OrderDetailsBuilderV2 extends OrderDetailsBuilder
{
    public function addOrderType(string $orderType): OrderDetailsBuilder
    {
        $this->appendElementTo('OrderType', $orderType, $this->instance);

        return $this;
    }

    public function addAdminOrderType(string $orderType): OrderDetailsBuilder
    {
        throw new LogicException('Unsupported yet');
    }

    public function addOrderAttribute(string $orderAttribute): OrderDetailsBuilder
    {
        $this->appendElementTo('OrderAttribute', $orderAttribute, $this->instance);

        return $this;
    }

    public function addBTDOrderParams(
        BTDContext $btfContext,
        ?DateTimeInterface $startDateTime = null,
        ?DateTimeInterface $endDateTime = null
    ): OrderDetailsBuilder {
        throw new LogicException('Unsupported yet');
    }

    public function addBTUOrderParams(BTUContext $btuContext): OrderDetailsBuilder
    {
        throw new LogicException('Unsupported yet');
    }

    public function addHVEOrderParams(HVEContext $hveContext): OrderDetailsBuilder
    {
        $xmlHVEOrderParams = $this->appendEmptyElementTo('HVEOrderParams', $this->instance);

        $this->appendElementTo('PartnerID', $hveContext->getPartnerId(), $xmlHVEOrderParams);
        $this->appendElementTo('OrderType', $hveContext->getOrderType(), $xmlHVEOrderParams);
        $this->appendElementTo('OrderID', $hveContext->getOrderId(), $xmlHVEOrderParams);

        return $this;
    }

    public function addHVDOrderParams(HVDContext $hvdContext): OrderDetailsBuilder
    {
        $xmlHVDOrderParams = $this->appendEmptyElementTo('HVDOrderParams', $this->instance);

        $this->appendElementTo('PartnerID', $hvdContext->getPartnerId(), $xmlHVDOrderParams);
        $this->appendElementTo('OrderType', $hvdContext->getOrderType(), $xmlHVDOrderParams);
        $this->appendElementTo('OrderID', $hvdContext->getOrderId(), $xmlHVDOrderParams);

        return $this;
    }

    public function addHVTOrderParams(HVTContext $hvtContext): OrderDetailsBuilder
    {
        $xmlHVTOrderParams = $this->appendEmptyElementTo('HVTOrderParams', $this->instance);

        $this->appendElementTo('PartnerID', $hvtContext->getPartnerId(), $xmlHVTOrderParams);
        $this->appendElementTo('OrderType', $hvtContext->getOrderType(), $xmlHVTOrderParams);
        $this->appendElementTo('OrderID', $hvtContext->getOrderId(), $xmlHVTOrderParams);
        $this->appendEmptyElementTo('OrderFlags', $xmlHVTOrderParams, [
            'completeOrderData' => $hvtContext->getCompleteOrderData() ? 'true' : 'false',
            'fetchLimit' => (string)$hvtContext->getFetchLimit(),
            'fetchOffset' => (string)$hvtContext->getFetchOffset(),
        ]);

        return $this;
    }
}
