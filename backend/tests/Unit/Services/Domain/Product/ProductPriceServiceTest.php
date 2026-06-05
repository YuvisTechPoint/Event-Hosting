<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Domain\Product;

use HiEvents\DomainObjects\Enums\PromoCodeDiscountTypeEnum;
use HiEvents\DomainObjects\Enums\ProductPriceType;
use HiEvents\DomainObjects\PromoCodeDomainObject;
use HiEvents\DomainObjects\ProductDomainObject;
use HiEvents\Services\Domain\Product\DTO\OrderProductPriceDTO;
use HiEvents\Services\Domain\Product\ProductPriceService;
use Mockery;
use Tests\TestCase;

class ProductPriceServiceTest extends TestCase
{
    private ProductPriceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductPriceService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testPercentagePromoCodeReducesPrice(): void
    {
        $product = $this->createPaidProduct(100.00);
        $promoCode = $this->createPromoCode(
            discountType: PromoCodeDiscountTypeEnum::PERCENTAGE,
            discount: 20.0,
            productId: $product->getId(),
        );

        $result = $this->service->getPrice(
            $product,
            new OrderProductPriceDTO(quantity: 1, price_id: null),
            $promoCode,
        );

        $this->assertSame(80.0, $result->price);
        $this->assertSame(100.0, $result->price_before_discount);
    }

    public function testFixedPromoCodeReducesPrice(): void
    {
        $product = $this->createPaidProduct(50.00);
        $promoCode = $this->createPromoCode(
            discountType: PromoCodeDiscountTypeEnum::FIXED,
            discount: 15.0,
            productId: $product->getId(),
        );

        $result = $this->service->getPrice(
            $product,
            new OrderProductPriceDTO(quantity: 1, price_id: null),
            $promoCode,
        );

        $this->assertSame(35.0, $result->price);
        $this->assertSame(50.0, $result->price_before_discount);
    }

    public function testPromoCodeDoesNotApplyToIneligibleProduct(): void
    {
        $product = $this->createPaidProduct(100.00);
        $promoCode = $this->createPromoCode(
            discountType: PromoCodeDiscountTypeEnum::PERCENTAGE,
            discount: 50.0,
            productId: 999,
        );

        $result = $this->service->getPrice(
            $product,
            new OrderProductPriceDTO(quantity: 1, price_id: null),
            $promoCode,
        );

        $this->assertSame(100.0, $result->price);
        $this->assertNull($result->price_before_discount);
    }

    private function createPaidProduct(float $price): ProductDomainObject
    {
        $product = Mockery::mock(ProductDomainObject::class)->makePartial();
        $product->setId(1);
        $product->shouldReceive('getType')->andReturn(ProductPriceType::PAID->name);
        $product->shouldReceive('getPrice')->andReturn($price);

        return $product;
    }

    private function createPromoCode(
        PromoCodeDiscountTypeEnum $discountType,
        float $discount,
        int $productId,
    ): PromoCodeDomainObject {
        $promoCode = Mockery::mock(PromoCodeDomainObject::class)->makePartial();
        $promoCode->shouldReceive('getDiscountType')->andReturn($discountType->name);
        $promoCode->shouldReceive('getDiscount')->andReturn($discount);
        $promoCode->shouldReceive('isFixedDiscount')->andReturn($discountType === PromoCodeDiscountTypeEnum::FIXED);
        $promoCode->shouldReceive('isPercentageDiscount')->andReturn($discountType === PromoCodeDiscountTypeEnum::PERCENTAGE);
        $promoCode->shouldReceive('appliesToProduct')->andReturnUsing(
            fn (ProductDomainObject $product) => $product->getId() === $productId
        );

        return $promoCode;
    }
}
