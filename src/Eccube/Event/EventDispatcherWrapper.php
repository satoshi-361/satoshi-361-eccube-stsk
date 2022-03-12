<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class EventDispatcherWrapper extends EventDispatcher
{
    /**
     * @var RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function dispatch($event)
    {
        $eventName = 1 < \func_num_args() ? func_get_arg(1) : null;

        if (\is_object($event)) {
            $eventName = $eventName ?? \get_class($event);
        } elseif (\is_string($event) && (null === $eventName || $eventName instanceof \Symfony\Contracts\EventDispatcher\Event || $eventName instanceof Event)) {
            $swap = $event;
            $event = $eventName ?? new Event();
            $eventName = $swap;
        }

        try {
            return parent::dispatch($event, $eventName);
        } catch(NotFoundHttpException $e) {
            $response = new RedirectResponse($this->router->generate('homepage'));
            
            $event->setResponse($response);
            return parent::dispatch($event, $eventName);
        }
    }

}
