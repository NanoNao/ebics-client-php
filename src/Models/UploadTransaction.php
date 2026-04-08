<?php

namespace EbicsApi\Ebics\Models;

use EbicsApi\Ebics\Contracts\UploadTransactionInterface;

/**
 * Upload Transaction represent collection of segments.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class UploadTransaction extends Transaction implements UploadTransactionInterface
{
    public const CHUNK_SIZE = 1048576; // 1MB

    private string $key;
    private int $numSegments;
    /** @var TransferSegment[] */
    private array $segments;
    /**
     * @var string[]
     */
    private array $orderData;
    private string $digest;
    private UploadSegment $initialization;

    /**
     * @param string $key
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setNumSegments(int $numSegments): void
    {
        $this->numSegments = $numSegments;
    }

    public function getNumSegments(): int
    {
        return $this->numSegments;
    }

    public function addSegment(TransferSegment $segment): void
    {
        $this->segments[] = $segment;
    }

    /** @return TransferSegment[] */
    public function getSegments(): array
    {
        return $this->segments;
    }

    public function getLastSegment(): ?TransferSegment
    {
        if (0 === count($this->segments)) {
            return null;
        }

        return end($this->segments);
    }

    /**
     * @param string[] $orderData
     * @return void
     */
    public function setOrderData(array $orderData): void
    {
        $this->orderData = $orderData;
    }

    /**
     * @return string[]
     */
    public function getOrderData(): array
    {
        return $this->orderData;
    }

    public function setDigest(string $digest): void
    {
        $this->digest = $digest;
    }

    public function getDigest(): string
    {
        return $this->digest;
    }

    public function setInitialization(UploadSegment $uploadSegment): void
    {
        $this->initialization = $uploadSegment;
    }

    public function getInitialization(): UploadSegment
    {
        return $this->initialization;
    }
}
