<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DailyStockCategoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_daily_stock_groups_and_filters_categories(): void
    {
        $this->actingAs(User::create(['code' => 'cat-'.bin2hex(random_bytes(4)), 'name' => 'Test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        $prefix = 'CAT-'.bin2hex(random_bytes(4));
        foreach (['Áo', 'Váy', null] as $index => $category) {
            Product::create(['code' => $prefix.$index, 'name' => 'Test', 'size' => 'M', 'fabric' => 'Lụa',
                'category' => $category, 'stock_quantity' => 10]);
        }
        $filters = ['q' => $prefix, 'from' => '2031-01-01', 'to' => '2031-01-02'];
        $this->get('/products/daily-stock?'.http_build_query($filters))->assertOk()
            ->assertViewHas('groupedProducts', fn ($groups) => $groups->count() === 3 && $groups->keys()->last() === 'Chưa phân loại')
            ->assertSee('stock-category-name', false);
        $this->get('/products/daily-stock?'.http_build_query($filters + ['category' => 'Áo']))->assertOk()
            ->assertViewHas('products', fn ($products) => $products->count() === 1 && $products->first()->category === 'Áo');
        $this->get('/products/daily-stock?'.http_build_query($filters + ['category' => 'Chưa phân loại']))->assertOk()
            ->assertViewHas('products', fn ($products) => $products->count() === 1 && $products->first()->category === null);
        $summaryCode = $prefix.'SUM';
        foreach (['L' => 3, 'M' => 2] as $size => $quantity) {
            Product::create(['code' => $summaryCode, 'name' => 'Test summary', 'size' => $size,
                'fabric' => 'Lụa', 'category' => 'Áo', 'stock_quantity' => $quantity]);
        }
        $this->get('/products/daily-stock?'.http_build_query(array_merge($filters, ['q' => $summaryCode])))
            ->assertOk()->assertSee('Tổng kết theo ngày')->assertSee('Ngày 01/01')->assertSee('Ngày 02/01')
            ->assertSee('L:')->assertSee('M:')->assertSee('<strong>5</strong>', false);
    }
}
