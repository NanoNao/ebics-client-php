<?php

namespace EbicsApi\Ebics\Tests\Services;

use EbicsApi\Ebics\Factories\Crypt\RSAFactory;
use EbicsApi\Ebics\Factories\KeyringFactory;
use EbicsApi\Ebics\Factories\SignatureFactory;
use EbicsApi\Ebics\Models\Keyring;
use EbicsApi\Ebics\Services\ArrayKeyringManager;
use EbicsApi\Ebics\Services\CryptoStorage;
use EbicsApi\Ebics\Services\KeyStorageLocator;
use LogicException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ArrayKeyringManager.
 *
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @author Andrew Svirin
 */
class ArrayKeyringManagerTest extends TestCase
{
    private ArrayKeyringManager $manager;

    protected function setUp(): void
    {
        $signatureFactory = new SignatureFactory(new RSAFactory());
        $cryptoStorage = new CryptoStorage(new KeyStorageLocator());
        $keyringFactory = new KeyringFactory($signatureFactory, $cryptoStorage);

        $this->manager = new ArrayKeyringManager($keyringFactory);
    }

    /**
     * Test loadKeyring creates new keyring from empty array.
     */
    public function testLoadKeyringCreatesNewFromEmptyArray(): void
    {
        $resource = [];
        $passphrase = 'testpassword';

        $keyring = $this->manager->loadKeyring($resource, $passphrase, Keyring::VERSION_25);

        self::assertInstanceOf(Keyring::class, $keyring);
        self::assertEquals(Keyring::VERSION_25, $keyring->getVersion());
        self::assertEquals($passphrase, $keyring->getPassword());
    }

    /**
     * Test loadKeyring uses custom default version.
     */
    public function testLoadKeyringUsesCustomDefaultVersion(): void
    {
        $resource = [];
        $passphrase = 'testpassword';

        $keyring = $this->manager->loadKeyring($resource, $passphrase, Keyring::VERSION_30);

        self::assertEquals(Keyring::VERSION_30, $keyring->getVersion());
    }

    /**
     * Test loadKeyring throws exception for non-array resource.
     */
    public function testLoadKeyringThrowsExceptionForNonArray(): void
    {
        $resource = 'not_an_array';
        $passphrase = 'testpassword';

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Expects array.');

        $this->manager->loadKeyring($resource, $passphrase);
    }

    /**
     * Test saveKeyring throws exception for non-array resource.
     */
    public function testSaveKeyringThrowsExceptionForNonArray(): void
    {
        $keyring = new Keyring(Keyring::VERSION_25);
        $keyring->setPassword('testpassword');
        $resource = 'not_an_array';

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Expects array.');

        $this->manager->saveKeyring($keyring, $resource);
    }

    /**
     * Test saveKeyring updates array with keyring data.
     */
    public function testSaveKeyringUpdatesArrayWithData(): void
    {
        $resource = [];
        $passphrase = 'testpassword';

        // Load keyring first to set up properly
        $keyring = $this->manager->loadKeyring($resource, $passphrase, Keyring::VERSION_25);
        $keyring->setUserSignatureAVersion('A006');

        $this->manager->saveKeyring($keyring, $resource);

        self::assertIsArray($resource);
        self::assertArrayHasKey(Keyring::VERSION_PREFIX, $resource);
        self::assertEquals(Keyring::VERSION_25, $resource[Keyring::VERSION_PREFIX]);
    }

    /**
     * Test load and save keyring roundtrip with empty keyring.
     */
    public function testLoadAndSaveKeyringRoundtrip(): void
    {
        $resource = [];
        $passphrase = 'testpassword';

        // Create and save new keyring
        $originalKeyring = $this->manager->loadKeyring($resource, $passphrase, Keyring::VERSION_25);
        $originalKeyring->setUserSignatureAVersion('A006');
        $this->manager->saveKeyring($originalKeyring, $resource);

        // Load the saved keyring
        $loadedKeyring = $this->manager->loadKeyring($resource, $passphrase, Keyring::VERSION_25);

        self::assertEquals($originalKeyring->getVersion(), $loadedKeyring->getVersion());
        self::assertEquals($originalKeyring->getPassword(), $loadedKeyring->getPassword());
    }

    /**
     * Test loadKeyring from populated array.
     */
    public function testLoadKeyringFromPopulatedArray(): void
    {
        // First create a keyring and save it
        $resource = [];
        $passphrase = 'testpassword';
        $keyring = $this->manager->loadKeyring($resource, $passphrase, Keyring::VERSION_25);
        $keyring->setUserSignatureAVersion('A006');
        $this->manager->saveKeyring($keyring, $resource);

        // Now load from the populated array
        $loadedKeyring = $this->manager->loadKeyring($resource, $passphrase);

        self::assertInstanceOf(Keyring::class, $loadedKeyring);
        self::assertEquals(Keyring::VERSION_25, $loadedKeyring->getVersion());
    }

    /**
     * Test saveKeyring replaces array content.
     */
    public function testSaveKeyringReplacesArrayContent(): void
    {
        $resource = [];
        $passphrase = 'testpassword';

        $keyring = $this->manager->loadKeyring($resource, $passphrase, Keyring::VERSION_25);
        $keyring->setUserSignatureAVersion('A006');

        $this->manager->saveKeyring($keyring, $resource);

        // The array should now contain the keyring data structure
        self::assertArrayHasKey(Keyring::VERSION_PREFIX, $resource);
        self::assertArrayHasKey(Keyring::USER_PREFIX, $resource);
    }
}
