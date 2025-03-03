<?php declare(strict_types=1);

namespace Shopware\Storefront\Page\Checkout\Finish;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Storefront\Page\Page;

#[Package('framework')]
class CheckoutFinishPage extends Page
{
    protected OrderEntity $order;

    protected bool $changedPayment = false;

    protected bool $paymentFailed = false;

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    public function setOrder(OrderEntity $order): void
    {
        $this->order = $order;
    }

    public function isChangedPayment(): bool
    {
        return $this->changedPayment;
    }

    public function setChangedPayment(bool $changedPayment): void
    {
        $this->changedPayment = $changedPayment;
    }

    public function isPaymentFailed(): bool
    {
        return $this->paymentFailed;
    }

    public function setPaymentFailed(bool $paymentFailed): void
    {
        $this->paymentFailed = $paymentFailed;
    }
}
