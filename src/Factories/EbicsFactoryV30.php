<?php

namespace EbicsApi\Ebics\Factories;

use EbicsApi\Ebics\Builders\Request\RequestBuilder;
use EbicsApi\Ebics\Contracts\Processor\Base64EncoderInterface;
use EbicsApi\Ebics\Contracts\Processor\ZipCompressorInterface;
use EbicsApi\Ebics\Factories\Crypt\BigIntegerFactory;
use EbicsApi\Ebics\Handlers\AuthSignatureHandler;
use EbicsApi\Ebics\Handlers\AuthSignatureHandlerV30;
use EbicsApi\Ebics\Handlers\OrderDataHandler;
use EbicsApi\Ebics\Handlers\OrderDataHandlerV30;
use EbicsApi\Ebics\Handlers\ResponseHandler;
use EbicsApi\Ebics\Handlers\ResponseHandlerV30;
use EbicsApi\Ebics\Handlers\UserSignatureHandler;
use EbicsApi\Ebics\Handlers\UserSignatureHandlerV3;
use EbicsApi\Ebics\Models\Bank;
use EbicsApi\Ebics\Models\Keyring;
use EbicsApi\Ebics\Models\User;
use EbicsApi\Ebics\Services\CryptService;
use EbicsApi\Ebics\Services\DigestResolver;
use EbicsApi\Ebics\Services\DigestResolverV3;
use EbicsApi\Ebics\Services\SchemaValidator;

/**
 * Class Ebics30Factory.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class EbicsFactoryV30 extends EbicsFactory
{
    public function createRequestFactory(
        Bank $bank,
        User $user,
        Keyring $keyring,
        UserSignatureHandler $userSignatureHandler,
        OrderDataHandler $orderDataHandler,
        DigestResolver $digestResolver,
        RequestBuilder $requestBuilder,
        CryptService $cryptService,
        ZipCompressorInterface $zipService,
        Base64EncoderInterface $base64Service
    ): RequestFactory {
        return new RequestFactoryV30(
            $bank,
            $user,
            $keyring,
            $userSignatureHandler,
            $orderDataHandler,
            $digestResolver,
            $requestBuilder,
            $cryptService,
            $zipService,
            $base64Service
        );
    }

    public function createAuthSignatureHandler(
        Base64EncoderInterface $base64Service,
        Keyring $keyring,
        CryptService $cryptService
    ): AuthSignatureHandler {
        return new AuthSignatureHandlerV30($base64Service, $keyring, $cryptService);
    }

    public function createUserSignatureHandler(
        Base64EncoderInterface $base64Service,
        User $user,
        Keyring $keyring,
        CryptService $cryptService,
        SchemaValidator $schemaValidator
    ): UserSignatureHandler {
        return new UserSignatureHandlerV3($base64Service, $user, $keyring, $cryptService, $schemaValidator);
    }

    public function createOrderDataHandler(
        Base64EncoderInterface $base64Service,
        User $user,
        Keyring $keyring,
        CryptService $cryptService,
        SignatureFactory $signatureFactory,
        CertificateX509Factory $certificateX509Factory,
        BigIntegerFactory $bigIntegerFactory
    ): OrderDataHandler {
        return new OrderDataHandlerV30(
            $base64Service,
            $user,
            $cryptService,
            $signatureFactory,
            $certificateX509Factory,
            $bigIntegerFactory
        );
    }

    public function createResponseHandler(
        SegmentFactory $segmentFactory,
        CryptService $cryptService,
        ZipCompressorInterface $zipService,
        Base64EncoderInterface $base64Service
    ): ResponseHandler {
        return new ResponseHandlerV30(
            $segmentFactory,
            $cryptService,
            $zipService,
            $base64Service
        );
    }

    public function createDigestResolver(CryptService $cryptService): DigestResolver
    {
        return new DigestResolverV3($cryptService);
    }
}
