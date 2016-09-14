<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Method;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\AddressStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Form\Type\UPSShippingMethodOptionsType;
use Oro\Bundle\UPSBundle\Model\Package;
use Oro\Bundle\UPSBundle\Model\PriceResponse;
use Oro\Bundle\UPSBundle\Provider\UPSTransport as UPSTransportProvider;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethodType;
use Oro\Component\Testing\Unit\EntityTrait;

class UPSShippingMethodTypeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;
    /**
     * @var UPSTransport|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transport;

    /**
     * @var UPSTransportProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transportProvider;

    /**
     * @var ShippingService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingService;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var UPSShippingMethodType
     */
    protected $upsShippingMethodType;

    protected function setUp()
    {
        $this->transport = $this->getEntity(
            UPSTransport::class,
            [
                'baseUrl' => 'some url',
                'apiUser' => 'some user',
                'apiPassword' => 'some password',
                'apiKey' => 'some key',
                'shippingAccountNumber' => 'some number',
                'shippingAccountName' => 'some name',
                'pickupType' => '01',
                'unitOfWeight' => 'LPS',
                'country' => new Country('US'),
                'applicableShippingServices' => [new ShippingService()]
            ]
        );

        $this->transportProvider = $this->getMockBuilder(UPSTransportProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingService = $this->getMock(ShippingService::class);

        $this->registry = $this->getMock(ManagerRegistry::class);

        $this->upsShippingMethodType = new UPSShippingMethodType(
            $this->transport,
            $this->transportProvider,
            $this->shippingService,
            $this->registry
        );
    }

    public function testSetIdentifier()
    {
        $identifier = 'ups_1';
        $this->upsShippingMethodType->setIdentifier($identifier);

        $this->assertEquals($identifier, $this->upsShippingMethodType->getIdentifier());
    }

    public function testSetLabel()
    {
        $label = 'ups 1';
        $this->upsShippingMethodType->setLabel($label);

        $this->assertEquals($label, $this->upsShippingMethodType->getLabel());
    }

    public function testSetOptionsConfigurationFormType()
    {
        $optionsConfigurationFormType = UPSShippingMethodOptionsType::class;
        $this->upsShippingMethodType->setOptionsConfigurationFormType($optionsConfigurationFormType);

        $this->assertEquals(
            $optionsConfigurationFormType,
            $this->upsShippingMethodType->getOptionsConfigurationFormType()
        );
    }

    public function testGetSortOrder()
    {
        $this->assertEquals(0, $this->upsShippingMethodType->getSortOrder());
    }

    public function testCalculatePrice()
    {
        $context = $this->getEntity(
            ShippingContext::class,
            [
                'lineItems' => [],
                'billingAddress' => new AddressStub(),
                'shippingAddress' => new AddressStub(),
                'shippingOrigin' => new AddressStub(),
                'paymentMethod' => '',
                'currency' => 'USD',
                'subtotal' => new Price(),
            ]
        );

        $methodOptions = [];
        $this->shippingService->expects(self::any())->method('getCode')->willReturn('02');
        $typeOptions = ['02' => ['surcharge' => 20]];

        $priceResponse = $this->getMockBuilder(PriceResponse::class)->disableOriginalConstructor()->getMock();
        $priceResponse->expects(self::once())->method('getPriceByService')->willReturn(Price::create(50, 'USD'));

        $this->transportProvider->expects(self::once())->method('getPrices')->willReturn($priceResponse);

        /** @var UPSShippingMethodType|\PHPUnit_Framework_MockObject_MockObject $upsShippingMethodType */
        $upsShippingMethodType = $this->getMockBuilder(UPSShippingMethodType::class)
            ->setMethods(['createPackages'])
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                $this->transport,
                $this->transportProvider,
                $this->shippingService,
                $this->registry
            ])
            ->getMock()
        ;
        $upsShippingMethodType->expects(self::once())->method('createPackages')->willReturn(['1' => 'package']);

        $price = $upsShippingMethodType->calculatePrice($context, $methodOptions, $typeOptions);

        $this->assertEquals(Price::create(70, 'USD'), $price);
    }

    /**
     * @param string $name
     * @return \ReflectionMethod
    */
    protected static function getMethod($name)
    {
        $class = new \ReflectionClass(UPSShippingMethodType::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    public function testCreatePackages()
    {
        /** @var ProductShippingOptions $productShippingOptions */
        $productShippingOptions = $this->getEntity(
            ProductShippingOptions::class,
            [
                'id' => 42,
                'productUnit' => $this->getEntity(
                    ProductUnit::class,
                    ['code' => 'test1']
                ),
                'dimensions' => Dimensions::create(7, 8, 9, new LengthUnit('inch')),
                'weight' => Weight::create(2, $this->getEntity(
                    WeightUnit::class,
                    ['code' => 'kg']
                ))
            ]
        );

        $repository = $this->getMockBuilder(ObjectRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects(self::any())->method('findOneBy')->willReturn($productShippingOptions);

        $manager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $manager->expects(self::any())->method('getRepository')->willReturn($repository);

        $this->registry->expects(self::any())->method('getManagerForClass')->willReturn($manager);

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 22]);

        /** @var ShippingLineItem $lineItem */
        $lineItem = $this->getEntity(ShippingLineItem::class, ['product' => $product, 'quantity' => 1]);

        $createPackagesReflection = self::getMethod('createPackages');
        $packages = $createPackagesReflection->invokeArgs(
            $this->upsShippingMethodType,
            [[$lineItem], 'KG']
        );

        $this->assertCount(1, $packages);

        /** @var Package $package */
        $package = reset($packages);

        $this->assertInstanceOf(Package::class, $package);
        $this->assertEquals('00', $package->getPackagingTypeCode());
        $this->assertEquals(7, $package->getDimensionLength());
        $this->assertEquals(8, $package->getDimensionWidth());
        $this->assertEquals(9, $package->getDimensionHeight());
        $this->assertEquals('KG', $package->getWeightCode());
        $this->assertEquals(2, $package->getWeight());
    }

    public function testGetProductsParamsByUnit()
    {
        /** @var ProductShippingOptions $productShippingOptions */
        $productShippingOptions = $this->getEntity(
            ProductShippingOptions::class,
            [
                'id' => 42,
                'productUnit' => $this->getEntity(
                    ProductUnit::class,
                    ['code' => 'test1']
                ),
                'dimensions' => Dimensions::create(7, 8, 9, $this->getEntity(LengthUnit::class, ['code' => 'inch'])),
                'weight' => Weight::create(2, $this->getEntity(
                    WeightUnit::class,
                    ['code' => 'kg']
                ))
            ]
        );

        $repository = $this->getMockBuilder(ObjectRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects(self::any())->method('findOneBy')->willReturn($productShippingOptions);

        $manager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $manager->expects(self::any())->method('getRepository')->willReturn($repository);

        $this->registry->expects(self::any())->method('getManagerForClass')->willReturn($manager);

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 22]);

        /** @var ShippingLineItem $lineItem */
        $lineItem = $this->getEntity(ShippingLineItem::class, ['product' => $product, 'quantity' => 1]);

        $productsParamsByUnitReflection = self::getMethod('getProductsParamsByUnit');
        $productsByUnit = $productsParamsByUnitReflection->invokeArgs(
            $this->upsShippingMethodType,
            [[$lineItem]]
        );


        $this->assertCount(1, $productsByUnit);
        $this->assertArrayHasKey('IN', $productsByUnit);
        $this->assertArrayHasKey('KG', $productsByUnit['IN']);
        $this->assertCount(1, $productsByUnit['IN']['KG']);

        $productByUnit = reset($productsByUnit['IN']['KG']);

        $this->assertEquals('inch', $productByUnit['dimensionUnit']);
        $this->assertEquals(9, $productByUnit['dimensionHeight']);
        $this->assertEquals(8, $productByUnit['dimensionWidth']);
        $this->assertEquals(7, $productByUnit['dimensionLength']);
        $this->assertEquals('kg', $productByUnit['weightUnit']);
        $this->assertEquals(2, $productByUnit['weight']);
    }
}
