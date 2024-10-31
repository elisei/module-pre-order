<?php
/**
 * O2TI Pre Order.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace O2TI\PreOrder\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    public const XML_PATH_PREORDER_AFFILIATE_TRACKING = 'preorder/general/affiliate_tracking';

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param Context $context
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Context $context,
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
        parent::__construct($context);
    }

    /**
     * Get tracking code by admin username
     *
     * @param string $adminUsername
     * @param mixed $store
     * @return string|null
     */
    public function getTrackingByAdmin(string $adminUsername, $store = null): ?string
    {
        $trackingConfig = $this->getTrackingConfig($store);
        
        if (!$trackingConfig) {
            return null;
        }

        foreach ($trackingConfig as $config) {
            if (isset($config['admin']) && $config['admin'] === $adminUsername) {
                return $config['tracking'] ?? null;
            }
        }

        return null;
    }

    /**
     * Get all tracking configuration
     *
     * @param mixed $store
     * @return array
     */
    public function getTrackingConfig($store = null): array
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_PREORDER_AFFILIATE_TRACKING,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        if (!$value) {
            return [];
        }

        try {
            return $this->serializer->unserialize($value);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if affiliate tracking is enabled
     *
     * @param mixed $store
     * @return bool
     */
    public function isEnabled($store = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            'preorder/general/enabled',
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
