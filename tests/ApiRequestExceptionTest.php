<?php

declare(strict_types=1);

namespace Detain\MyAdminPlesk\Tests;

use Detain\MyAdminPlesk\ApiRequestException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for the ApiRequestException class.
 *
 * Tests verify correct class hierarchy, instantiation, and
 * exception behavior.
 */
class ApiRequestExceptionTest extends TestCase
{
    /**
     * @var ReflectionClass<ApiRequestException>
     */
    private ReflectionClass $reflection;

    /**
     * Set up reflection for each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->reflection = new ReflectionClass(ApiRequestException::class);
    }

    /**
     * Verify the ApiRequestException class exists.
     *
     * @return void
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(ApiRequestException::class));
    }

    /**
     * Verify the class resides in the correct namespace.
     *
     * @return void
     */
    public function testClassNamespace(): void
    {
        $this->assertSame('Detain\MyAdminPlesk', $this->reflection->getNamespaceName());
    }

    /**
     * Verify ApiRequestException extends the base Exception class.
     *
     * @return void
     */
    public function testExtendsException(): void
    {
        $this->assertTrue($this->reflection->isSubclassOf(\Exception::class));
    }

    /**
     * Verify ApiRequestException implements Throwable.
     *
     * @return void
     */
    public function testImplementsThrowable(): void
    {
        $this->assertTrue($this->reflection->implementsInterface(\Throwable::class));
    }

    /**
     * Verify the exception can be instantiated with a message.
     *
     * @return void
     */
    public function testCanBeInstantiatedWithMessage(): void
    {
        $exception = new ApiRequestException('Test error message');
        $this->assertSame('Test error message', $exception->getMessage());
    }

    /**
     * Verify the exception can be instantiated with a message and code.
     *
     * @return void
     */
    public function testCanBeInstantiatedWithMessageAndCode(): void
    {
        $exception = new ApiRequestException('Connection failed', 7);
        $this->assertSame('Connection failed', $exception->getMessage());
        $this->assertSame(7, $exception->getCode());
    }

    /**
     * Verify the exception can be instantiated with no arguments.
     *
     * @return void
     */
    public function testCanBeInstantiatedWithNoArguments(): void
    {
        $exception = new ApiRequestException();
        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
    }

    /**
     * Verify the exception can be thrown and caught.
     *
     * @return void
     */
    public function testCanBeThrownAndCaught(): void
    {
        $this->expectException(ApiRequestException::class);
        $this->expectExceptionMessage('API request failed');
        throw new ApiRequestException('API request failed');
    }

    /**
     * Verify the exception can be caught as a base Exception.
     *
     * @return void
     */
    public function testCanBeCaughtAsBaseException(): void
    {
        $caught = false;
        try {
            throw new ApiRequestException('test');
        } catch (\Exception $e) {
            $caught = true;
            $this->assertInstanceOf(ApiRequestException::class, $e);
        }
        $this->assertTrue($caught);
    }

    /**
     * Verify the exception supports a previous exception in the chain.
     *
     * @return void
     */
    public function testSupportsPreviousException(): void
    {
        $previous = new \RuntimeException('Root cause');
        $exception = new ApiRequestException('Wrapper', 0, $previous);
        $this->assertSame($previous, $exception->getPrevious());
    }

    /**
     * Verify the class is not abstract.
     *
     * @return void
     */
    public function testClassIsNotAbstract(): void
    {
        $this->assertFalse($this->reflection->isAbstract());
    }

    /**
     * Verify the class is not final.
     *
     * @return void
     */
    public function testClassIsNotFinal(): void
    {
        $this->assertFalse($this->reflection->isFinal());
    }
}
