<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigProvider
{
    private string $clientId;
    private string $clientSecret;
    private string $orderState;
    private string $orderCompletionState;
    private string $orderCancellationState;
    private string $orderPartialCancellationState;
    private string $paymentState;
    private string $deliveryState;
    private string $paypalPaymentType;
    private string $instantTransferPaymentType;
    private string $creditcardType;
    private string $dispatchTypePostal;
    private string $dispatchTypeLetter;
    private string $dispatchTypeDownload;
    private string $dispatchTypeForwarding;
    private string $dispatchTypeForwardingTwoMen;
    private string $dispatchTypeForwardingPickup;
    private string $dispatchTypeForwardingTwoMenPickup;
    private string $fallbackLanguage;
    private string $carrier;
    private string $customCarrierRule;
    private bool $isSandboxMode = true;
    private bool $isDebugMode = false;
    private string $defaultSalutation;
    private bool $saveUserComment = false;
    private bool $isSendMail = false;
    private string $defaultCustomerGroup;
    private bool $useAddressTwoAsCompany = false;
    private bool $useEmptyPhone = false;
    private bool $deactivateScheduledTask = false;
    private string $adjustOrderTime;
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public function init(string $salesChannelId): void
    {
        $config = $this->getSalesChannelConfig($salesChannelId);

        $this
            ->setClientId(isset($config['clientId']) ? trim($config['clientId']) : '')
            ->setClientSecret(isset($config['clientSecret']) ? trim($config['clientSecret']) : '')
            ->setOrderState($config['orderState'] ?? '')
            ->setOrderCompletionState($config['orderComplete'] ?? '')
            ->setOrderCancellationState($config['orderCancel'] ?? '')
            ->setOrderPartialCancellationState($config['orderPartialCancel'] ?? '')
            ->setPaymentState($config['paymentState'] ?? '')
            ->setDeliveryState($config['deliveryState'] ?? '')
            ->setPaypalPaymentType($config['paypal'] ?? '')
            ->setInstantTransferPaymentType($config['instant'] ?? '')
            ->setCreditcardType($config['creditcard'] ?? '')
            ->setDispatchTypePostal($config['dispatch'] ?? '')
            ->setDispatchTypeLetter($config['dispatchLetter'] ?? '')
            ->setDispatchTypeDownload($config['dispatchDownload'] ?? '')
            ->setDispatchTypeForwarding($config['dispatchForwarding'] ?? '')
            ->setDispatchTypeForwardingTwoMen($config['dispatchForwardingTwoMen'] ?? '')
            ->setDispatchTypeForwardingPickup($config['dispatchForwardingPickup'] ?? '')
            ->setDispatchTypeForwardingTwoMenPickup($config['dispatchForwardingTwoMenPickup'] ?? '')
            ->setFallbackLanguage($config['fallbackLanguage'] ?? '')
            ->setCarrier($config['carrier'] ?? '')
            ->setCustomCarrierRule($config['customCarrier'] ?? '')
            ->setIsSandboxMode((bool) $config['sandbox'] ?? false)
            ->setIsDebugMode($config['logging'] ?? false)
            ->setDefaultSalutation($config['defaultSalutation'] ?? '')
            ->setSaveUserComment(isset($config['commentSave']) ? (bool) $config['commentSave'] : false)
            ->setIsSendMail((bool) $config['sendMail'] ?? false)
            ->setDefaultCustomerGroup($config['defaultCustomerGroup'] ?? '')
            ->setUseAddressTwoAsCompany((bool) $config['useAddressTwoAsCompany'] ?? false)
            ->setUseEmptyPhone((bool) $config['useEmptyPhone'] ?? false)
            ->setDeactivateScheduledTask((bool) $config['deactivateScheduledTask'] ?? false)
            ->setAdjustOrderTime((string) $config['adjustOrderTime'])
        ;
    }

    private function getSalesChannelConfig(string $salesChannelId): array
    {
        return $this->systemConfigService->get('OttIdealoConnector.config', $salesChannelId);
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(string $clientSecret): self
    {
        $this->clientSecret = $clientSecret;

        return $this;
    }

    public function getOrderState(): string
    {
        return $this->orderState;
    }

    public function setOrderState(string $orderState): self
    {
        $this->orderState = $orderState;

        return $this;
    }

    public function getOrderCompletionState(): string
    {
        return $this->orderCompletionState;
    }

    public function setOrderCompletionState(string $orderCompletionState): self
    {
        $this->orderCompletionState = $orderCompletionState;

        return $this;
    }

    public function getOrderCancellationState(): string
    {
        return $this->orderCancellationState;
    }

    public function setOrderCancellationState(string $orderCancellationState): self
    {
        $this->orderCancellationState = $orderCancellationState;

        return $this;
    }

    public function getPaymentState(): string
    {
        return $this->paymentState;
    }

    public function setPaymentState(string $paymentState): self
    {
        $this->paymentState = $paymentState;

        return $this;
    }

    public function getPaypalPaymentType(): string
    {
        return $this->paypalPaymentType;
    }

    public function setPaypalPaymentType(string $paypalPaymentType): self
    {
        $this->paypalPaymentType = $paypalPaymentType;

        return $this;
    }

    public function getInstantTransferPaymentType(): string
    {
        return $this->instantTransferPaymentType;
    }

    public function setInstantTransferPaymentType(string $instantTransferPaymentType): self
    {
        $this->instantTransferPaymentType = $instantTransferPaymentType;

        return $this;
    }

    public function getCreditcardType(): string
    {
        return $this->creditcardType;
    }

    public function setCreditcardType(string $creditcardType): self
    {
        $this->creditcardType = $creditcardType;

        return $this;
    }

    public function getDispatchTypePostal(): string
    {
        return $this->dispatchTypePostal;
    }

    public function setDispatchTypePostal(string $dispatchTypePostal): self
    {
        $this->dispatchTypePostal = $dispatchTypePostal;

        return $this;
    }

    public function getCarrier(): string
    {
        return $this->carrier;
    }

    public function setCarrier(string $carrier): self
    {
        $this->carrier = $carrier;

        return $this;
    }

    public function getCustomCarrierRule(): string
    {
        return $this->customCarrierRule;
    }

    public function setCustomCarrierRule(string $customCarrierRule): self
    {
        $this->customCarrierRule = $customCarrierRule;

        return $this;
    }

    public function isSandboxMode(): bool
    {
        return $this->isSandboxMode;
    }

    public function setIsSandboxMode(bool $isSandboxMode): self
    {
        $this->isSandboxMode = $isSandboxMode;

        return $this;
    }

    public function isDebugMode(): bool
    {
        return $this->isDebugMode;
    }

    public function setIsDebugMode(bool $isDebugMode): self
    {
        $this->isDebugMode = $isDebugMode;

        return $this;
    }

    public function getDefaultSalutation(): string
    {
        return $this->defaultSalutation;
    }

    public function setDefaultSalutation(string $defaultSalutation): self
    {
        $this->defaultSalutation = $defaultSalutation;

        return $this;
    }

    public function isSaveUserComment(): bool
    {
        return $this->saveUserComment;
    }

    public function setSaveUserComment(bool $saveUserComment): self
    {
        $this->saveUserComment = $saveUserComment;

        return $this;
    }

    public function isSendMail(): bool
    {
        return $this->isSendMail;
    }

    public function setIsSendMail(bool $isSendMail): self
    {
        $this->isSendMail = $isSendMail;

        return $this;
    }

    public function getDefaultCustomerGroup(): string
    {
        return $this->defaultCustomerGroup;
    }

    public function setDefaultCustomerGroup(string $defaultCustomerGroup): self
    {
        $this->defaultCustomerGroup = $defaultCustomerGroup;

        return $this;
    }

    public function isUseAddressTwoAsCompany(): bool
    {
        return $this->useAddressTwoAsCompany;
    }

    public function setUseAddressTwoAsCompany(bool $useAddressTwoAsCompany): self
    {
        $this->useAddressTwoAsCompany = $useAddressTwoAsCompany;

        return $this;
    }

    public function isUseEmptyPhone(): bool
    {
        return $this->useEmptyPhone;
    }

    public function setUseEmptyPhone(bool $useEmptyPhone): self
    {
        $this->useEmptyPhone = $useEmptyPhone;

        return $this;
    }

    public function getDeliveryState(): string
    {
        return $this->deliveryState;
    }

    public function setDeliveryState(string $deliveryState): self
    {
        $this->deliveryState = $deliveryState;

        return $this;
    }

    public function getOrderPartialCancellationState(): string
    {
        return $this->orderPartialCancellationState;
    }

    public function setOrderPartialCancellationState(string $orderPartialCancellationState): self
    {
        $this->orderPartialCancellationState = $orderPartialCancellationState;

        return $this;
    }

    public function getFallbackLanguage(): ?string
    {
        return $this->fallbackLanguage;
    }

    public function setFallbackLanguage(?string $fallbackLanguage): self
    {
        $this->fallbackLanguage = $fallbackLanguage;

        return $this;
    }

    public function getDispatchTypeLetter(): string
    {
        return $this->dispatchTypeLetter;
    }

    public function setDispatchTypeLetter(string $dispatchTypeLetter): self
    {
        $this->dispatchTypeLetter = $dispatchTypeLetter;

        return $this;
    }

    public function getDispatchTypeDownload(): string
    {
        return $this->dispatchTypeDownload;
    }

    public function setDispatchTypeDownload(string $dispatchTypeDownload): self
    {
        $this->dispatchTypeDownload = $dispatchTypeDownload;

        return $this;
    }

    public function getDispatchTypeForwarding(): string
    {
        return $this->dispatchTypeForwarding;
    }

    public function setDispatchTypeForwarding(string $dispatchTypeForwarding): self
    {
        $this->dispatchTypeForwarding = $dispatchTypeForwarding;

        return $this;
    }

    public function getDispatchTypeForwardingTwoMen(): string
    {
        return $this->dispatchTypeForwardingTwoMen;
    }

    public function setDispatchTypeForwardingTwoMen(string $dispatchTypeForwardingTwoMen): self
    {
        $this->dispatchTypeForwardingTwoMen = $dispatchTypeForwardingTwoMen;

        return $this;
    }

    public function getDispatchTypeForwardingPickup(): string
    {
        return $this->dispatchTypeForwardingPickup;
    }

    public function setDispatchTypeForwardingPickup(string $dispatchTypeForwardingPickup): self
    {
        $this->dispatchTypeForwardingPickup = $dispatchTypeForwardingPickup;

        return $this;
    }

    public function getDispatchTypeForwardingTwoMenPickup(): string
    {
        return $this->dispatchTypeForwardingTwoMenPickup;
    }

    public function setDispatchTypeForwardingTwoMenPickup(string $dispatchTypeForwardingTwoMenPickup): self
    {
        $this->dispatchTypeForwardingTwoMenPickup = $dispatchTypeForwardingTwoMenPickup;

        return $this;
    }

    public function isDeactivateScheduledTask(): bool
    {
        return $this->deactivateScheduledTask;
    }

    public function setDeactivateScheduledTask(bool $deactivateScheduledTask): self
    {
        $this->deactivateScheduledTask = $deactivateScheduledTask;

        return $this;
    }

    public function getAdjustOrderTime(): string
    {
        return $this->adjustOrderTime;
    }

    public function setAdjustOrderTime(string $adjustOrderTime): self
    {
        $this->adjustOrderTime = $adjustOrderTime;

        return $this;
    }
}
