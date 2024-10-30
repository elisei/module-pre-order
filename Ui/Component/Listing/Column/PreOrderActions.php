<?php
declare(strict_types=1);

namespace O2TI\PreOrder\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Store\Model\StoreManagerInterface;

class PreOrderActions extends Column
{
    /**
     * @var UrlInterface
     */
    protected $frontendUrlBuilder;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $frontendUrlBuilder
     * @param StoreManagerInterface $storeManager
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $frontendUrlBuilder,
        StoreManagerInterface $storeManager,
        array $components = [],
        array $data = []
    ) {
        $this->frontendUrlBuilder = $frontendUrlBuilder;
        $this->storeManager = $storeManager;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {

            foreach ($dataSource['data']['items'] as &$item) {
                $data = [
                    '_secure' => true,
                    '_nosid' => true,
                    'hash' => $item['hash']
                ];

                if (isset($item['tracking'])) {
                    $data['affiliate_code'] = $item['tracking'];
                }

                $linkPay = $this->frontendUrlBuilder->setScope(0)
                    ->getUrl('preorder/index/quote', $data);

                $item[$this->getData('name')] = [
                    'view' => [
                        'href' => $linkPay,
                        'label' => __('View in Frontend'),
                        'target' => '_blank',
                    ]
                ];
            }
        }

        return $dataSource;
    }
}
