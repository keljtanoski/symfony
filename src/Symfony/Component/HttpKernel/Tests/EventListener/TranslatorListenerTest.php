<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\EventListener\TranslatorListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;

/**
 * @group legacy
 */
class TranslatorListenerTest extends TestCase
{
    private $listener;
    private $translator;
    private $requestStack;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(LocaleAwareInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->listener = new TranslatorListener($this->translator, $this->requestStack);
    }

    public function testLocaleIsSetInOnKernelRequest()
    {
        $this->translator
            ->expects($this->once())
            ->method('setLocale')
            ->with($this->equalTo('fr'));

        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $this->createRequest('fr'), HttpKernelInterface::MASTER_REQUEST);
        $this->listener->onKernelRequest($event);
    }

    public function testDefaultLocaleIsUsedOnExceptionsInOnKernelRequest()
    {
        $this->translator
            ->expects($this->exactly(2))
            ->method('setLocale')
            ->withConsecutive(
                ['fr'],
                ['en']
            )
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new \InvalidArgumentException())
            );

        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $this->createRequest('fr'), HttpKernelInterface::MASTER_REQUEST);
        $this->listener->onKernelRequest($event);
    }

    public function testLocaleIsSetInOnKernelFinishRequestWhenParentRequestExists()
    {
        $this->translator
            ->expects($this->once())
            ->method('setLocale')
            ->with($this->equalTo('fr'));

        $this->setMasterRequest($this->createRequest('fr'));
        $event = new FinishRequestEvent($this->createMock(HttpKernelInterface::class), $this->createRequest('de'), HttpKernelInterface::SUB_REQUEST);
        $this->listener->onKernelFinishRequest($event);
    }

    public function testLocaleIsNotSetInOnKernelFinishRequestWhenParentRequestDoesNotExist()
    {
        $this->translator
            ->expects($this->never())
            ->method('setLocale');

        $event = new FinishRequestEvent($this->createMock(HttpKernelInterface::class), $this->createRequest('de'), HttpKernelInterface::SUB_REQUEST);
        $this->listener->onKernelFinishRequest($event);
    }

    public function testDefaultLocaleIsUsedOnExceptionsInOnKernelFinishRequest()
    {
        $this->translator
            ->expects($this->exactly(2))
            ->method('setLocale')
            ->withConsecutive(
                ['fr'],
                ['en']
            )
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new \InvalidArgumentException())
            );

        $this->setMasterRequest($this->createRequest('fr'));
        $event = new FinishRequestEvent($this->createMock(HttpKernelInterface::class), $this->createRequest('de'), HttpKernelInterface::SUB_REQUEST);
        $this->listener->onKernelFinishRequest($event);
    }

    private function createRequest($locale)
    {
        $request = new Request();
        $request->setLocale($locale);

        return $request;
    }

    private function setMasterRequest($request)
    {
        $this->requestStack
            ->expects($this->any())
            ->method('getParentRequest')
            ->willReturn($request);
    }
}