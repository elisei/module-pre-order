<?php

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
    private $userCollectionFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param CollectionFactory $userCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $userCollectionFactory,
        array $data = []
    ) {
        $this->userCollectionFactory = $userCollectionFactory;
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
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML.
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }

        return parent::_toHtml();
    }

    /**
     * Get admin users as options.
     *
     * @return array
     */
    private function getSourceOptions(): array
    {
        $userCollection = $this->userCollectionFactory->create();
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
