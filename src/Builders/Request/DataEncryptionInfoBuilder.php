<?php

namespace EbicsApi\Ebics\Builders\Request;

use DOMDocument;
use DOMElement;
use EbicsApi\Ebics\Exceptions\SignatureEbicsException;
use EbicsApi\Ebics\Models\Keyring;
use EbicsApi\Ebics\Services\Base64Service;
use EbicsApi\Ebics\Services\CryptService;

/**
 * Class DataEncryptionInfoBuilder builder for request container.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class DataEncryptionInfoBuilder extends XmlBuilder
{
    private DOMElement $instance;
    private readonly CryptService $cryptService;
    private readonly Base64Service $base64Service;

    public function __construct(CryptService $cryptService, Base64Service $base64Service, DOMDocument $dom)
    {
        $this->cryptService = $cryptService;
        $this->base64Service = $base64Service;
        parent::__construct($dom);
    }

    /**
     * Create body for UnsecuredRequest.
     *
     * @return $this
     */
    public function createInstance(): DataEncryptionInfoBuilder
    {
        $this->instance = $this->createEmptyElement('DataEncryptionInfo', ['authenticate' => 'true']);

        return $this;
    }

    /**
     * Uses bank signature.
     *
     * @param Keyring $keyring
     * @param string $algorithm
     *
     * @return $this
     * @throws SignatureEbicsException
     */
    public function addEncryptionPubKeyDigest(Keyring $keyring, string $algorithm = 'sha256'): DataEncryptionInfoBuilder
    {
        if (!($signatureE = $keyring->getBankSignatureE())) {
            throw new SignatureEbicsException('Bank Certificate E is empty.');
        }

        if ($signatureE->getCertificateContent()) {
            $certificateEDigest = $this->cryptService->calculateCertificateFingerprint(
                $signatureE->getCertificateContent(),
                $algorithm
            );
        } else {
            $certificateEDigest = $this->cryptService->calculatePublicKeyDigest($signatureE, $algorithm);
        }
        $encryptionPubKeyDigestNodeValue = $this->base64Service->encode($certificateEDigest);

        $this->appendElementTo('EncryptionPubKeyDigest', $encryptionPubKeyDigestNodeValue, $this->instance, [
            'Version' => $keyring->getBankSignatureEVersion(),
            'Algorithm' => sprintf('http://www.w3.org/2001/04/xmlenc#%s', $algorithm),
        ]);

        return $this;
    }

    public function addTransactionKey(string $transactionKey, Keyring $keyring): DataEncryptionInfoBuilder
    {
        $bankSignatureE = $keyring->getBankSignatureE();
        if ($bankSignatureE === null) {
            throw new SignatureEbicsException('Bank Certificate E is empty.');
        }
        $transactionKeyEncrypted = $this->cryptService->encryptTransactionKey(
            $bankSignatureE->getPublicKey(),
            $transactionKey
        );
        $transactionKeyNodeValue = $this->base64Service->encode($transactionKeyEncrypted);

        $this->appendElementTo('TransactionKey', $transactionKeyNodeValue, $this->instance);

        return $this;
    }

    public function getInstance(): DOMElement
    {
        return $this->instance;
    }
}
