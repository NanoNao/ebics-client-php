<?php

namespace EbicsApi\Ebics\Builders\Request;

use DateTimeInterface;
use DOMElement;
use EbicsApi\Ebics\Contexts\BTDContext;
use EbicsApi\Ebics\Contexts\BTUContext;
use EbicsApi\Ebics\Contexts\FDLContext;
use EbicsApi\Ebics\Contexts\FULContext;
use EbicsApi\Ebics\Contexts\HVDContext;
use EbicsApi\Ebics\Contexts\HVEContext;
use EbicsApi\Ebics\Contexts\HVTContext;

/**
 * Abstract Class OrderDetailsBuilder builder for request container.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
abstract class OrderDetailsBuilder extends XmlBuilder
{
    const ORDER_ATTRIBUTE_DZNNN = 'DZNNN';
    const ORDER_ATTRIBUTE_DZHNN = 'DZHNN';
    const ORDER_ATTRIBUTE_UZHNN = 'UZHNN';
    const ORDER_ATTRIBUTE_OZHNN = 'OZHNN';

    protected DOMElement $instance;

    public function createInstance(): OrderDetailsBuilder
    {
        $this->instance = $this->createEmptyElement('OrderDetails');

        return $this;
    }

    abstract public function addOrderType(string $orderType): OrderDetailsBuilder;

    abstract public function addAdminOrderType(string $orderType): OrderDetailsBuilder;

    public function addOrderId(string $orderId): OrderDetailsBuilder
    {
        $this->appendElementTo('OrderID', $orderId, $this->instance);

        return $this;
    }

    abstract public function addOrderAttribute(string $orderAttribute): OrderDetailsBuilder;

    public function addStandardOrderParams(
        ?DateTimeInterface $startDateTime = null,
        ?DateTimeInterface $endDateTime = null
    ): OrderDetailsBuilder {
        $xmlStandardOrderParams = $this->appendEmptyElementTo('StandardOrderParams', $this->instance);

        if (null !== $startDateTime && null !== $endDateTime) {
            $xmlDateRange = $this->createDateRange($startDateTime, $endDateTime);
            $xmlStandardOrderParams->appendChild($xmlDateRange);
        }

        return $this;
    }

    public function addFDLOrderParams(
        FDLContext $fdlContext,
        ?DateTimeInterface $startDateTime,
        ?DateTimeInterface $endDateTime
    ): OrderDetailsBuilder {
        $xmlFDLOrderParams = $this->appendEmptyElementTo('FDLOrderParams', $this->instance);

        if (null !== $startDateTime && null !== $endDateTime) {
            $xmlDateRange = $this->createDateRange($startDateTime, $endDateTime);
            $xmlFDLOrderParams->appendChild($xmlDateRange);
        }

        $this->addParameters($xmlFDLOrderParams, $fdlContext->getParameters());

        $this->appendElementTo('FileFormat', $fdlContext->getFileFormat(), $xmlFDLOrderParams, [
            'CountryCode' => $fdlContext->getCountryCode(),
        ]);

        return $this;
    }

    public function addFULOrderParams(FULContext $fulContext): OrderDetailsBuilder
    {
        $xmlFULOrderParams = $this->appendEmptyElementTo('FULOrderParams', $this->instance);

        $this->addParameters($xmlFULOrderParams, $fulContext->getParameters());

        $this->appendElementTo('FileFormat', $fulContext->getFileFormat(), $xmlFULOrderParams, [
            'CountryCode' => $fulContext->getCountryCode(),
        ]);

        return $this;
    }

    private function addParameters(DOMElement $orderParams, array $parameters): void
    {
        foreach ($parameters as $name => $value) {
            $xmlParameter = $this->appendEmptyElementTo('Parameter', $orderParams);

            $this->appendElementTo('Name', $name, $xmlParameter);
            $this->appendElementTo('Value', $value, $xmlParameter, ['Type' => 'string']);
        }
    }

    public function getInstance(): DOMElement
    {
        return $this->instance;
    }

    abstract public function addHVEOrderParams(HVEContext $hveContext): OrderDetailsBuilder;

    public function addHVUOrderParams(): OrderDetailsBuilder
    {
        $this->appendEmptyElementTo('HVUOrderParams', $this->instance);

        return $this;
    }

    public function addHVZOrderParams(): OrderDetailsBuilder
    {
        $this->appendEmptyElementTo('HVZOrderParams', $this->instance);

        return $this;
    }

    abstract public function addHVDOrderParams(HVDContext $hvdContext): OrderDetailsBuilder;

    abstract public function addHVTOrderParams(HVTContext $hvtContext): OrderDetailsBuilder;

    abstract public function addBTDOrderParams(
        BTDContext $btfContext,
        ?DateTimeInterface $startDateTime = null,
        ?DateTimeInterface $endDateTime = null
    ): OrderDetailsBuilder;

    abstract public function addBTUOrderParams(BTUContext $btuContext): OrderDetailsBuilder;

    protected function createDateRange(DateTimeInterface $startDateTime, DateTimeInterface $endDateTime): DOMElement
    {
        $xmlDateRange = $this->createEmptyElement('DateRange');

        $this->appendElementTo('Start', $startDateTime->format('Y-m-d'), $xmlDateRange);
        $this->appendElementTo('End', $endDateTime->format('Y-m-d'), $xmlDateRange);

        return $xmlDateRange;
    }
}
