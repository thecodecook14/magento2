<?php declare(strict_types=1);
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tax\Test\Unit\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Tax\Helper\Data;
use Magento\Tax\Observer\UpdateProductOptionsObserver;
use PHPUnit\Framework\TestCase;

class UpdateProductOptionsObserverTest extends TestCase
{
    /**
     * Tests the methods that rely on the ScopeConfigInterface object to provide their return values
     * @param array $expected
     * @param bool $displayBothPrices
     * @param bool $priceIncludesTax
     * @param bool $displayPriceExcludingTax
     * @dataProvider dataProviderUpdateProductOptions
     */
    public function testUpdateProductOptions(
        $expected,
        $displayBothPrices,
        $priceIncludesTax,
        $displayPriceExcludingTax
    ) {
        $frameworkObject= new DataObject();
        $frameworkObject->setAdditionalOptions([]);

        $product=$this->createMock(Product::class);

        $registry=$this->createMock(Registry::class);
        $registry->expects($this->any())
            ->method('registry')
            ->with('current_product')
            ->will($this->returnValue($product));

        $taxData=$this->createMock(Data::class);
        $taxData->expects($this->any())
            ->method('getCalculationAlgorithm')
            ->will($this->returnValue('TOTAL_BASE_CALCULATION'));

        $taxData->expects($this->any())
            ->method('displayBothPrices')
            ->will($this->returnValue($displayBothPrices));

        $taxData->expects($this->any())
            ->method('priceIncludesTax')
            ->will($this->returnValue($priceIncludesTax));

        $taxData->expects($this->any())
            ->method('displayPriceExcludingTax')
            ->will($this->returnValue($displayPriceExcludingTax));

        $eventObject=$this->createPartialMock(Event::class, ['getResponseObject']);
        $eventObject->expects($this->any())
            ->method('getResponseObject')
            ->will($this->returnValue($frameworkObject));

        $observerObject=$this->createMock(Observer::class);

        $observerObject->expects($this->any())
            ->method('getEvent')
            ->will($this->returnValue($eventObject));

        $objectManager = new ObjectManager($this);
        $taxObserverObject = $objectManager->getObject(
            UpdateProductOptionsObserver::class,
            [
                'taxData' => $taxData,
                'registry' => $registry,
            ]
        );

        $taxObserverObject->execute($observerObject);

        $this->assertEquals($expected, $frameworkObject->getAdditionalOptions());
    }

    /**
     * @return array
     */
    public function dataProviderUpdateProductOptions()
    {
        return [
            [
                'expected' => [
                    'calculationAlgorithm' => 'TOTAL_BASE_CALCULATION',
                    'optionTemplate' => '<%= data.label %><% if (data.finalPrice.value) ' .
                        '{ %> +<%= data.finalPrice.formatted %> (Excl. tax: <%= data.basePrice.formatted %>)<% } %>',
                ],
                'displayBothPrices' => true,
                'priceIncludesTax' => false,
                'displayPriceExcludingTax' => false,
            ],
            [
                'expected' => [
                    'calculationAlgorithm' => 'TOTAL_BASE_CALCULATION',
                    'optionTemplate' => '<%= data.label %><% if (data.basePrice.value) ' .
                        '{ %> +<%= data.basePrice.formatted %><% } %>',
                ],
                'displayBothPrices' => false,
                'priceIncludesTax' => true,
                'displayPriceExcludingTax' => true,
            ],
            [
                'expected' => [
                    'calculationAlgorithm' => 'TOTAL_BASE_CALCULATION',
                ],
                'displayBothPrices' => false,
                'priceIncludesTax' => false,
                'displayPriceExcludingTax' => false,
            ],
        ];
    }
}
