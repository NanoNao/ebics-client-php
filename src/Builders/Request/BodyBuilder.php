<?php

namespace EbicsApi\Ebics\Builders\Request;

use Closure;
use DOMDocument;
use DOMElement;
use EbicsApi\Ebics\Contracts\Processor\Base64EncoderInterface;
use EbicsApi\Ebics\Contracts\Processor\ZipCompressorInterface;
use EbicsApi\Ebics\Services\CryptService;

/**
 * Class BodyBuilder builder for request container.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
abstract class BodyBuilder extends XmlBuilder
{
    protected readonly ZipCompressorInterface $zipService;
    protected readonly CryptService $cryptService;
    protected readonly Base64EncoderInterface $base64Service;
    protected DOMElement $instance;

    public function __construct(
        ZipCompressorInterface $zipService,
        CryptService $cryptService,
        Base64EncoderInterface $base64Service,
        DOMDocument $dom
    ) {
        $this->zipService = $zipService;
        $this->cryptService = $cryptService;
        $this->base64Service = $base64Service;
        parent::__construct($dom);
    }

    public function createInstance(): BodyBuilder
    {
        $this->instance = $this->createEmptyElement('body');

        return $this;
    }

    abstract public function addDataTransfer(Closure $callback): BodyBuilder;

    public function addTransferReceipt(Closure $callback): BodyBuilder
    {
        $transferReceiptBuilder = new TransferReceiptBuilder($this->dom);
        $this->instance->appendChild($transferReceiptBuilder->createInstance()->getInstance());

        call_user_func($callback, $transferReceiptBuilder);

        return $this;
    }

    public function addPreValidation(string $signatureVersion, ?string $digest = null): BodyBuilder
    {
        $preValidation = $this->createEmptyElement('PreValidation', [
            'authenticate' => 'true'
        ]);

        $this->instance->appendChild($preValidation);

        if (null !== $digest) {
            $this->appendElementTo('DataDigest', $this->base64Service->encode($digest), $preValidation, [
                'SignatureVersion' => $signatureVersion,
            ]);
        }

        return $this;
    }

    public function getInstance(): DOMElement
    {
        return $this->instance;
    }
}
