<?php

namespace EbicsApi\Ebics\Handlers;

use DOMDocument;
use EbicsApi\Ebics\Contracts\Processor\Base64EncoderInterface;
use EbicsApi\Ebics\Contracts\Processor\ZipCompressorInterface;
use EbicsApi\Ebics\Contracts\ResponseHandlerInterface;
use EbicsApi\Ebics\Factories\EbicsExceptionFactory;
use EbicsApi\Ebics\Factories\SegmentFactory;
use EbicsApi\Ebics\Handlers\Traits\H00XTrait;
use EbicsApi\Ebics\Models\DownloadSegment;
use EbicsApi\Ebics\Models\Http\Request;
use EbicsApi\Ebics\Models\Http\Response;
use EbicsApi\Ebics\Models\InitializationSegment;
use EbicsApi\Ebics\Models\Keyring;
use EbicsApi\Ebics\Models\UploadSegment;
use EbicsApi\Ebics\Services\CryptService;
use EbicsApi\Ebics\Services\DOMHelper;

/**
 * Class ResponseHandler manage response DOM elements.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
abstract class ResponseHandler implements ResponseHandlerInterface
{
    use H00XTrait;

    public function __construct(
        protected readonly SegmentFactory $segmentFactory,
        protected readonly CryptService $cryptService,
        protected readonly ZipCompressorInterface $zipService,
        protected readonly Base64EncoderInterface $base64Service
    ) {
    }

    public function retrieveH00XReturnCode(DOMDocument $xml): string
    {
        return DOMHelper::safeItemValue($this->queryH00XXpath($xml, '//header/mutable/ReturnCode'));
    }

    public function retrieveH00XBodyReturnCode(DOMDocument $xml): string
    {
        return DOMHelper::safeItemValue($this->queryH00XXpath($xml, '//body/ReturnCode'));
    }

    public function retrieveH00XReportText(DOMDocument $xml): string
    {
        return DOMHelper::safeItemValue($this->queryH00XXpath($xml, '//header/mutable/ReportText'));
    }

    public function retrieveH00XTransactionId(DOMDocument $xml): ?string
    {
        return DOMHelper::safeItemValueOrNull($this->queryH00XXpath($xml, '//header/static/TransactionID'));
    }

    public function retrieveH00XTransactionPhase(DOMDocument $xml): ?string
    {
        return DOMHelper::safeItemValueOrNull($this->queryH00XXpath($xml, '//header/mutable/TransactionPhase'));
    }

    public function retrieveH00XNumSegments(DOMDocument $xml): ?string
    {
        return DOMHelper::safeItemValueOrNull($this->queryH00XXpath($xml, '//header/static/NumSegments'));
    }

    public function retrieveH00XRequestOrderId(DOMDocument $xml): string
    {
        return DOMHelper::safeItemValueOrNull($this->queryH00XXpath($xml, '//OrderID'));
    }

    public function retrieveH00XResponseOrderId(DOMDocument $xml): string
    {
        return DOMHelper::safeItemValueOrNull($this->queryH00XXpath($xml, '//header/mutable/OrderID'));
    }

    public function retrieveH00XSegmentNumber(DOMDocument $xml): ?string
    {
        return DOMHelper::safeItemValueOrNull($this->queryH00XXpath($xml, '//header/mutable/SegmentNumber'));
    }

    public function retrieveH00XTransactionKey(DOMDocument $xml): ?string
    {
        return DOMHelper::safeItemValueOrNull(
            $this->queryH00XXpath($xml, '//body/DataTransfer/DataEncryptionInfo/TransactionKey')
        );
    }

    public function retrieveH00XOrderData(DOMDocument $xml): ?string
    {
        return DOMHelper::safeItemValueOrNull($this->queryH00XXpath($xml, '//body/DataTransfer/OrderData'));
    }

    public function retrieveH00XBodyOrHeaderReturnCode(DOMDocument $xml): string
    {
        $headerReturnCode = $this->retrieveH00XReturnCode($xml);
        $bodyReturnCode = $this->retrieveH00XBodyReturnCode($xml);

        if ('000000' !== $headerReturnCode) {
            return $headerReturnCode;
        }

        return $bodyReturnCode;
    }

    public function retrieveH000ReturnCode(DOMDocument $xml): string
    {
        $xpath = $this->prepareH000XPath($xml);
        $returnCode = $xpath->query('//H000:SystemReturnCode/H000:ReturnCode');

        return DOMHelper::safeItemValue($returnCode);
    }

    public function retrieveH000ReportText(DOMDocument $xml): string
    {
        $xpath = $this->prepareH000XPath($xml);
        $reportText = $xpath->query('//H000:SystemReturnCode/H000:ReportText');

        return DOMHelper::safeItemValue($reportText);
    }

    public function extractInitializationSegment(Response $response, Keyring $keyring): InitializationSegment
    {
        $transactionKeyEncoded = $this->retrieveH00XTransactionKey($response);
        $transactionKey = $this->base64Service->decode($transactionKeyEncoded);
        $orderDataEncrypted = $this->base64Service->decode($this->retrieveH00XOrderData($response));

        $orderDataCompressed = $this->cryptService->decryptOrderDataCompressed(
            $keyring,
            $orderDataEncrypted,
            $transactionKey
        );

        $orderData = $this->zipService->uncompress($orderDataCompressed);

        $segment = $this->segmentFactory->createInitializationSegment();
        $segment->setResponse($response);
        $segment->setTransactionKey($transactionKey);
        $segment->setOrderData($orderData);

        return $segment;
    }

    public function extractDownloadSegment(Response $response): DownloadSegment
    {
        $transactionId = $this->retrieveH00XTransactionId($response);
        $transactionPhase = $this->retrieveH00XTransactionPhase($response);
        $transactionKeyEncoded = $this->retrieveH00XTransactionKey($response);
        $transactionKey = $this->base64Service->decode($transactionKeyEncoded);
        $numSegments = $this->retrieveH00XNumSegments($response);
        $segmentNumber = $this->retrieveH00XSegmentNumber($response);
        $orderDataEncrypted = $this->retrieveH00XOrderData($response);
        $segment = $this->segmentFactory->createDownloadSegment();
        $segment->setResponse($response);
        $segment->setTransactionId($transactionId);
        $segment->setTransactionPhase($transactionPhase);
        $segment->setTransactionKey($transactionKey);
        $segment->setNumSegments((int)$numSegments);
        $segment->setSegmentNumber((int)$segmentNumber);
        $segment->setOrderData($orderDataEncrypted);

        return $segment;
    }

    abstract public function extractUploadSegment(Request $request, Response $response): UploadSegment;

    public function checkResponseReturnCode(Request $request, Response $response): void
    {
        $rootName = $response->documentElement->localName;

        if ($rootName === 'ebicsHEVResponse') {
            $errorCode = $this->retrieveH000ReturnCode($response);

            if ('000000' === $errorCode) {
                return;
            }

            $reportText = $this->retrieveH000ReportText($response);
            EbicsExceptionFactory::buildExceptionFromCode($errorCode, $reportText, $request, $response);
        } else {
            $errorCode = $this->retrieveH00XBodyOrHeaderReturnCode($response);

            if ('000000' === $errorCode) {
                return;
            }

            // For Transaction Done.
            if ('011000' === $errorCode) {
                return;
            }

            // For Download Postprocess Skipped (postponed).
            if ('011001' === $errorCode) {
                return;
            }

            $reportText = $this->retrieveH00XReportText($response);
            EbicsExceptionFactory::buildExceptionFromCode($errorCode, $reportText, $request, $response);
        }
    }
}
