<?php
/**
 * O2TI Pre Order.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

declare(strict_types=1);

namespace O2TI\PreOrder\Block\Adminhtml\Form\Field\Column;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Magento\User\Model\ResourceModel\User\CollectionFactory;

/**
 * Class FieldColumn - Create Field to Column with Admin Users List.
 */
class FieldColumn extends Select
{
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $userCollectFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param CollectionFactory $userCollectFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $userCollectFactory,
        array $data = []
    ) {
        $this->userCollectFactory = $userCollectFactory;
        parent::__construct($context, $data);
    }

    /**
     * Sets name for input element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setData('name', $value);
    }

    /**
     * Set "id" for <select> element
     *
     * @param string $value
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }

        return parent::_toHtml();
    }

    /**
     * Get admin users as options
     *
     * @return array
     */
    private function getSourceOptions(): array
    {
        $userCollection = $this->userCollectFactory->create();
        $options = [];

        foreach ($userCollection as $user) {
            $options[] = [
                'label' => $user->getUsername(),
                'value' => $user->getUsername()
            ];
        }

        return $options;
    }
}
