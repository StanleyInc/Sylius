<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\CoreBundle\EventListener;

use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sylius\Bundle\CoreBundle\Model\ShipmentInterface;
use Sylius\Bundle\CoreBundle\Model\OrderShippingStates;
use Sylius\Bundle\CoreBundle\SyliusOrderEvents;
use Sylius\Bundle\CoreBundle\OrderProcessing\StateResolverInterface;
use Sylius\Bundle\ShippingBundle\Processor\ShipmentProcessorInterface;

/**
 * Shipment listener.
 *
 * @author Saša Stamenković <umpirsky@gmail.com>
 */
class ShipmentListener
{
    /**
     * @var StateResolverInterface
     */
    protected $stateResolver;

    /**
     * Order shipping processor.
     *
     * @var ShipmentProcessorInterface
     */
    protected $shippingProcessor;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * Constructor.
     *
     * @param StateResolverInterface     $stateResolver
     * @param ShipmentProcessorInterface $shippingProcessor
     * @param EventDispatcherInterface   $dispatcher
     */
    public function __construct(StateResolverInterface $stateResolver, ShipmentProcessorInterface $shippingProcessor, EventDispatcherInterface $dispatcher)
    {
        $this->stateResolver = $stateResolver;
        $this->shippingProcessor = $shippingProcessor;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Set shipment status to shipped.
     *
     * @param GenericEvent $event
     */
    public function ship(GenericEvent $event)
    {
        $shipment = $this->getShipment($event);
        $order = $shipment->getOrder();

        $this->shippingProcessor->updateShipmentStates(
            array($shipment),
            $shipment::STATE_SHIPPED
        );

        $this->stateResolver->resolveShippingState($order);

        if (OrderShippingStates::SHIPPED === $order->getShippingState()) {
            $this->dispatcher->dispatch(SyliusOrderEvents::PRE_SHIP, new GenericEvent($order));
        }
    }

    private function getShipment(GenericEvent $event)
    {
        $shipment = $event->getSubject();

        if (!$shipment instanceof ShipmentInterface) {
            throw new \InvalidArgumentException(
                'Order shipping listener requires event subject to be instance of "Sylius\Bundle\CoreBundle\Model\ShipmentInterface"'
            );
        }

        return $shipment;
    }
}
