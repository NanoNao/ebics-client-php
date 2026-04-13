<?php

namespace EbicsApi\Ebics\Services;

use EbicsApi\Ebics\Contracts\LoggerInterface;

/**
 * A logger that stores log records in an array.
 *
 * Useful for testing, debugging, or collecting logs after execution.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class ArrayLogger implements LoggerInterface
{
    /**
     * @var list<array{level: mixed, message: string, context: array<string, mixed>}>
     */
    private array $records = [];

    /**
     * @inheritDoc
     */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * @inheritDoc
     * @param array<string, mixed> $context
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->records[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }

    /**
     * Get all recorded log entries.
     *
     * @return list<array{level: mixed, message: string, context: array<string, mixed>}>
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    /**
     * Get log entries filtered by level.
     *
     * @param string $level
     * @return list<array{level: mixed, message: string, context: array<string, mixed>}>
     */
    public function recordsByLevel(string $level): array
    {
        return array_values(
            array_filter($this->records, fn($r) => $r['level'] === $level)
        );
    }

    /**
     * Clear all recorded log entries.
     */
    public function clear(): void
    {
        $this->records = [];
    }
}
