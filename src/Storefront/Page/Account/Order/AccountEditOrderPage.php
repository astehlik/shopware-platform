<?php declare(strict_types=1);

namespace Shopware\Storefront\Page\Account\Order;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Promotion\PromotionCollection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Storefront\Page\Page;

#[Package('checkout')]
class AccountEditOrderPage extends Page
{
    protected OrderEntity $order;

    protected PaymentMethodCollection $paymentMethods;

    protected PromotionCollection $activePromotions;

    protected ?string $deepLinkCode = null;

    protected bool $paymentChangeable = true;

    protected ?string $errorCode = null;

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    public function setOrder(OrderEntity $order): void
    {
        $this->order = $order;
    }

    public function getPaymentMethods(): PaymentMethodCollection
    {
        return $this->paymentMethods;
    }

    public function setPaymentMethods(PaymentMethodCollection $paymentMethods): void
    {
        $this->paymentMethods = $paymentMethods;
    }

    public function getDeepLinkCode(): ?string
    {
        return $this->deepLinkCode;
    }

    public function setDeepLinkCode(?string $deepLinkCode): void
    {
        $this->deepLinkCode = $deepLinkCode;
    }

    public function getActivePromotions(): PromotionCollection
    {
        return $this->activePromotions;
    }

    public function setActivePromotions(PromotionCollection $activePromotions): void
    {
        $this->activePromotions = $activePromotions;
    }

    public function isPaymentChangeable(): bool
    {
        return $this->paymentChangeable;
    }

    public function setPaymentChangeable(bool $paymentChangeable): void
    {
        $this->paymentChangeable = $paymentChangeable;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function setErrorCode(?string $errorCode): void
    {
        $this->errorCode = $errorCode;
    }
}
