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
 * Ebics 3.0 Class OrderDetailsBuilder builder for request container.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class OrderDetailsBuilderV3 extends OrderDetailsBuilder
{
    public function addOrderType(string $orderType): OrderDetailsBuilder
    {
        throw new LogicException('Unsupported yet');
    }

    public function addAdminOrderType(string $orderType): OrderDetailsBuilder
    {
        $this->appendElementTo('AdminOrderType', $orderType, $this->instance);

        return $this;
    }

    public function addOrderAttribute(
        string $orderAttribute
    ): OrderDetailsBuilder {
        throw new LogicException('Unsupported yet');
    }

    public function addBTDOrderParams(
        BTDContext $btfContext,
        ?DateTimeInterface $startDateTime = null,
        ?DateTimeInterface $endDateTime = null
    ): OrderDetailsBuilder {
        $xmlBTDOrderParams = $this->appendEmptyElementTo('BTDOrderParams', $this->instance);

        $xmlService = $this->appendEmptyElementTo('Service', $xmlBTDOrderParams);

        $this->appendElementTo('ServiceName', $btfContext->getServiceName(), $xmlService);

        if (null !== $btfContext->getScope()) {
            $this->appendElementTo('Scope', $btfContext->getScope(), $xmlService);
        }

        if (null !== $btfContext->getServiceOption()) {
            $this->appendElementTo('ServiceOption', $btfContext->getServiceOption(), $xmlService);
        }

        if (null !== $btfContext->getContainerFlag()) {
            $this->appendElementTo('ContainerFlag', $btfContext->getContainerFlag(), $xmlService);
        }

        if (null !== $btfContext->getContainerType()) {
            $this->appendEmptyElementTo('Container', $xmlService, [
                'containerType' => $btfContext->getContainerType(),
            ]);
        }

        $xmlMsgName = $this->appendElementTo('MsgName', $btfContext->getMsgName(), $xmlService);

        if (null !== $btfContext->getMsgNameVersion()) {
            $xmlMsgName->setAttribute('version', $btfContext->getMsgNameVersion());
        }

        if (null !== $btfContext->getMsgNameVariant()) {
            $xmlMsgName->setAttribute('variant', $btfContext->getMsgNameVariant());
        }

        if (null !== $btfContext->getMsgNameFormat()) {
            $xmlMsgName->setAttribute('format', $btfContext->getMsgNameFormat());
        }

        if (null !== $startDateTime && null !== $endDateTime) {
            $xmlDateRange = $this->createDateRange(
                $startDateTime,
                $endDateTime
            );
            $xmlBTDOrderParams->appendChild($xmlDateRange);
        }

        return $this;
    }

    public function addBTUOrderParams(BTUContext $btuContext): OrderDetailsBuilder
    {
        $xmlBTUOrderParams = $this->appendEmptyElementTo('BTUOrderParams', $this->instance, [
            'fileName' => $btuContext->getFileName(),
        ]);

        $xmlService = $this->appendEmptyElementTo('Service', $xmlBTUOrderParams);

        $this->appendElementTo('ServiceName', $btuContext->getServiceName(), $xmlService);

        if (null !== $btuContext->getScope()) {
            $this->appendElementTo('Scope', $btuContext->getScope(), $xmlService);
        }

        if (null !== $btuContext->getServiceOption()) {
            $this->appendElementTo('ServiceOption', $btuContext->getServiceOption(), $xmlService);
        }

        if (null !== $btuContext->getContainerFlag()) {
            $this->appendElementTo('ContainerFlag', $btuContext->getContainerFlag(), $xmlService);
        }

        $xmlMsgName = $this->appendElementTo('MsgName', $btuContext->getMsgName(), $xmlService);

        if (null !== $btuContext->getMsgNameVersion()) {
            $xmlMsgName->setAttribute('version', $btuContext->getMsgNameVersion());
        }

        if (null !== $btuContext->getMsgNameVariant()) {
            $xmlMsgName->setAttribute('variant', $btuContext->getMsgNameVariant());
        }

        if (null !== $btuContext->getMsgNameFormat()) {
            $xmlMsgName->setAttribute('format', $btuContext->getMsgNameFormat());
        }

        if (true === $btuContext->getSignatureFlag()) {
            $xmlSignatureFlag = $this->appendEmptyElementTo('SignatureFlag', $xmlBTUOrderParams);

            if (true === $btuContext->getSignatureFlagEds()) {
                $xmlSignatureFlag->setAttribute('requestEDS', 'true');
            }
        }

        return $this;
    }

    public function addHVEOrderParams(HVEContext $hveContext): OrderDetailsBuilder
    {
        $xmlHVEOrderParams = $this->appendEmptyElementTo('HVEOrderParams', $this->instance);

        $this->appendElementTo('PartnerID', $hveContext->getPartnerId(), $xmlHVEOrderParams);

        $xmlService = $this->appendEmptyElementTo('Service', $xmlHVEOrderParams);

        $this->appendElementTo('ServiceName', $hveContext->getServiceName(), $xmlService);

        if (null !== $hveContext->getScope()) {
            $this->appendElementTo('Scope', $hveContext->getScope(), $xmlService);
        }

        if (null !== $hveContext->getServiceOption()) {
            $this->appendElementTo('ServiceOption', $hveContext->getServiceOption(), $xmlService);
        }

        $this->appendElementTo('MsgName', $hveContext->getMsgName(), $xmlService);

        $this->appendElementTo('OrderID', $hveContext->getOrderId(), $xmlHVEOrderParams);

        return $this;
    }

    public function addHVDOrderParams(HVDContext $hvdContext): OrderDetailsBuilder
    {
        $xmlHVDOrderParams = $this->appendEmptyElementTo('HVDOrderParams', $this->instance);

        $this->appendElementTo('PartnerID', $hvdContext->getPartnerId(), $xmlHVDOrderParams);

        $xmlService = $this->appendEmptyElementTo('Service', $xmlHVDOrderParams);

        $this->appendElementTo('ServiceName', $hvdContext->getServiceName(), $xmlService);

        if (null !== $hvdContext->getScope()) {
            $this->appendElementTo('Scope', $hvdContext->getScope(), $xmlService);
        }

        if (null !== $hvdContext->getServiceOption()) {
            $this->appendElementTo('ServiceOption', $hvdContext->getServiceOption(), $xmlService);
        }

        $this->appendElementTo('MsgName', $hvdContext->getMsgName(), $xmlService);

        $this->appendElementTo('OrderID', $hvdContext->getOrderId(), $xmlHVDOrderParams);

        return $this;
    }

    public function addHVTOrderParams(HVTContext $hvtContext): OrderDetailsBuilder
    {
        $xmlHVTOrderParams = $this->appendEmptyElementTo('HVTOrderParams', $this->instance);

        $this->appendElementTo('PartnerID', $hvtContext->getPartnerId(), $xmlHVTOrderParams);

        $xmlService = $this->appendEmptyElementTo('Service', $xmlHVTOrderParams);

        $this->appendElementTo('ServiceName', $hvtContext->getServiceName(), $xmlService);

        if (null !== $hvtContext->getScope()) {
            $this->appendElementTo('Scope', $hvtContext->getScope(), $xmlService);
        }

        if (null !== $hvtContext->getServiceOption()) {
            $this->appendElementTo('ServiceOption', $hvtContext->getServiceOption(), $xmlService);
        }

        $this->appendElementTo('MsgName', $hvtContext->getMsgName(), $xmlService);

        $this->appendElementTo('OrderID', $hvtContext->getOrderId(), $xmlHVTOrderParams);

        $this->appendEmptyElementTo('OrderFlags', $xmlHVTOrderParams, [
            'completeOrderData' => $hvtContext->getCompleteOrderData() ? 'true' : 'false',
            'fetchLimit' => (string)$hvtContext->getFetchLimit(),
            'fetchOffset' => (string)$hvtContext->getFetchOffset(),
        ]);

        return $this;
    }
}
