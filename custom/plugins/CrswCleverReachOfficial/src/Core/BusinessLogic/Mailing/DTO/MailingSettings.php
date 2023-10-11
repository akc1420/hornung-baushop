<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

class MailingSettings extends DataTransferObject
{
    /**
     * @var string
     */
    protected $editor;
    /**
     * @var bool
     */
    protected $openTracking;
    /**
     * @var bool
     */
    protected $clickTracking;
    /**
     * @var bool
     */
    protected $linkTrackingUrl;
    /**
     * @var string
     */
    protected $linkTrackingType;
    /**
     * @var string
     */
    protected $googleCampaignName;
    /**
     * @var string
     */
    protected $unsubscribeFormId;

    /**
     * @return string
     */
    public function getEditor()
    {
        return $this->editor;
    }

    /**
     * @param string $editor
     */
    public function setEditor($editor)
    {
        $this->editor = $editor;
    }

    /**
     * @return bool
     */
    public function isOpenTracking()
    {
        return $this->openTracking;
    }

    /**
     * @param bool $openTracking
     */
    public function setOpenTracking($openTracking)
    {
        $this->openTracking = $openTracking;
    }

    /**
     * @return bool
     */
    public function isClickTracking()
    {
        return $this->clickTracking;
    }

    /**
     * @param bool $clickTracking
     */
    public function setClickTracking($clickTracking)
    {
        $this->clickTracking = $clickTracking;
    }

    /**
     * @return bool
     */
    public function isLinkTrackingUrl()
    {
        return $this->linkTrackingUrl;
    }

    /**
     * @param bool $linkTrackingUrl
     */
    public function setLinkTrackingUrl($linkTrackingUrl)
    {
        $this->linkTrackingUrl = $linkTrackingUrl;
    }

    /**
     * @return string
     */
    public function getLinkTrackingType()
    {
        return $this->linkTrackingType;
    }

    /**
     * @param string $linkTrackingType
     */
    public function setLinkTrackingType($linkTrackingType)
    {
        $this->linkTrackingType = $linkTrackingType;
    }

    /**
     * @return string
     */
    public function getGoogleCampaignName()
    {
        return $this->googleCampaignName;
    }

    /**
     * @param string $googleCampaignName
     */
    public function setGoogleCampaignName($googleCampaignName)
    {
        $this->googleCampaignName = $googleCampaignName;
    }

    /**
     * @return string
     */
    public function getUnsubscribeFormId()
    {
        return $this->unsubscribeFormId;
    }

    /**
     * @param string $unsubscribeFormId
     */
    public function setUnsubscribeFormId($unsubscribeFormId)
    {
        $this->unsubscribeFormId = $unsubscribeFormId;
    }

    /**
     * @inheritDoc
     *
     * @return array
     */
    public function toArray()
    {
        $data = array(
            'editor' => $this->getEditor(),
            'open_tracking' => $this->isOpenTracking(),
            'click_tracking' => $this->isClickTracking(),
            'link_tracking_url' => $this->isLinkTrackingUrl(),
            'link_tracking_type' => $this->getLinkTrackingType(),
            'unsubscribe_form_id' => $this->getUnsubscribeFormId(),
        );

        if ($this->getLinkTrackingType() === 'google') {
            $data['google_campaign_name'] = $this->getGoogleCampaignName();
        }

        return $data;
    }

    /**
     * @param array $data
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\DTO\MailingSettings
     */
    public static function fromArray(array $data)
    {
        $entity = new static;
        $entity->setEditor(static::getDataValue($data, 'editor'));
        $entity->setOpenTracking(static::getDataValue($data, 'open_tracking', false));
        $entity->setClickTracking(static::getDataValue($data, 'click_tracking', false));
        $entity->setLinkTrackingUrl(static::getDataValue($data, 'link_tracking_url', false));
        $entity->setLinkTrackingType(static::getDataValue($data, 'link_tracking_type'));
        $entity->setGoogleCampaignName(static::getDataValue($data, 'google_campaign_name', null));
        $entity->setUnsubscribeFormId(static::getDataValue($data, 'unsubscribe_form_id'));

        return $entity;
    }
}
