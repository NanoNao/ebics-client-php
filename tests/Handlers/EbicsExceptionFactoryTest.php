<?php

namespace EbicsApi\Ebics\Tests\Handlers;

use EbicsApi\Ebics\Exceptions\BankPubkeyUpdateRequiredException;
use EbicsApi\Ebics\Exceptions\EbicsResponseException;
use EbicsApi\Ebics\Exceptions\IncorrectResponseEbicsException;
use EbicsApi\Ebics\Exceptions\InternalErrorException;
use EbicsApi\Ebics\Factories\EbicsExceptionFactory;
use EbicsApi\Ebics\Tests\AbstractEbicsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Class EbicsExceptionFactoryTest.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Guillaume Sainthillier
 */
#[Group('ebics-exception-factory')]
class EbicsExceptionFactoryTest extends AbstractEbicsTestCase
{
    #[DataProvider('getExceptions')]
    public function testExceptions(
        string $errorCode,
        ?string $errorText,
        string $expectedExceptionClass,
        ?string $meaning
    ): void {
        try {
            EbicsExceptionFactory::buildExceptionFromCode($errorCode, $errorText);
        } catch (EbicsResponseException $exception) {
            self::assertInstanceOf($expectedExceptionClass, $exception);
            self::assertEquals($exception->getResponseCode(), $errorCode);
            self::assertEquals($exception->getMeaning(), $meaning);
        } catch (IncorrectResponseEbicsException $exception) {
            self::assertInstanceOf($expectedExceptionClass, $exception);
        }

    }

    /**
     * @return array<int, array{string, string|null, class-string, string|null}>
     */
    public static function getExceptions(): array
    {
        return [
            [
                '061099',
                null,
                InternalErrorException::class,
                'An internal error occurred when processing an EBICS request.'
            ],
            [
                '091008',
                null,
                BankPubkeyUpdateRequiredException::class,
                'The bank verifies the hash value sent by the user. If the hash value does not match the current public keys, the bank terminates the transaction initialization.'
            ],
            ['099999', null, IncorrectResponseEbicsException::class, null],
        ];
    }
}
