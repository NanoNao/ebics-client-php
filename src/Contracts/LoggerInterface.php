<?php

namespace EbicsApi\Ebics\Contracts;

/**
 * Logger interface (PSR-3 compatible).
 *
 * This interface is a direct copy of PSR-3 (Logger Interface), which defines
 * a standard contract for logging libraries in PHP. The library includes this
 * copy to maintain its zero-external-dependencies policy while still providing
 * full PSR-3 compatibility.
 *
 * Any PSR-3 compliant logger (Monolog, PsrLog, etc.) can be used with this
 * library by passing it via EbicsClientOptions::setLogger().
 *
 * @see https://www.php-fig.org/psr/psr-3/ PSR-3: Logger Interface
 *
 * The message MUST be a string or object implementing __toString().
 *
 * The message MAY contain placeholders in the form: {foo} where foo
 * will be replaced by the context data in key "foo".
 *
 * The context array can contain arbitrary data. The only assumption that
 * can be made by implementors is that if an Exception instance is given
 * to produce a stack trace, it MUST be in a key named "exception".
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author PHP-FIG (copied for zero-dependency usage)
 */
interface LoggerInterface
{
    /**
     * System is unusable.
     *
     * @param array<string, mixed> $context
     */
    public function emergency(string|\Stringable $message, array $context = []): void;

    /**
     * Action must be taken immediately.
     *
     * @param array<string, mixed> $context
     */
    public function alert(string|\Stringable $message, array $context = []): void;

    /**
     * Critical conditions.
     *
     * @param array<string, mixed> $context
     */
    public function critical(string|\Stringable $message, array $context = []): void;

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param array<string, mixed> $context
     */
    public function error(string|\Stringable $message, array $context = []): void;

    /**
     * Exceptional occurrences that are not errors.
     *
     * @param array<string, mixed> $context
     */
    public function warning(string|\Stringable $message, array $context = []): void;

    /**
     * Normal but significant events.
     *
     * @param array<string, mixed> $context
     */
    public function notice(string|\Stringable $message, array $context = []): void;

    /**
     * Interesting events.
     *
     * @param array<string, mixed> $context
     */
    public function info(string|\Stringable $message, array $context = []): void;

    /**
     * Detailed debug information.
     *
     * @param array<string, mixed> $context
     */
    public function debug(string|\Stringable $message, array $context = []): void;

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param array<string, mixed> $context
     */
    public function log($level, string|\Stringable $message, array $context = []): void;
}
