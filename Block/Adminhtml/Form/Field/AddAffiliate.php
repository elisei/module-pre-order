<?php
/**
 * O2TI Pre Order.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace O2TI\PreOrder\Block\Adminhtml\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use O2TI\PreOrder\Block\Adminhtml\Form\Field\Column\FieldColumn;

/**
 * Class Add Affiliate - Add Affiliate to field.
 */
class AddAffiliate extends AbstractFieldArray
{
    /**
     * @var FieldColumn
     */
    protected $fieldRenderer;

    /**
     * Prepare rendering the new field by adding all the needed columns.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _prepareToRender()
    {
        $this->addColumn('admin', [
            'label'    => __('Admin User'),
            'renderer' => $this->getFieldRenderer(),
            'class' => 'required-entry',
        ]);

        $this->addColumn('tracking', [
            'label' => __('Tracking Code'),
            'class' => 'required-entry',
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Prepare existing row data object.
     *
     * @param DataObject $row
     *
     * @throws LocalizedException
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $field = $row->getField();
        if ($field !== null) {
            $options['option_'.$this->getFieldRenderer()->calcOptionHash($field)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * Create Block FieldColumn.
     *
     * @throws LocalizedException
     *
     * @return FieldColumn
     */
    private function getFieldRenderer()
    {
        if (!$this->fieldRenderer) {
            $this->fieldRenderer = $this->getLayout()->createBlock(
                FieldColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->fieldRenderer;
    }
}
