<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkshopOrder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkshopOrderSortingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_date_headers_sort_both_directions_with_empty_dates_last(): void
    {
        $this->actingAs(User::create(['code' => 'sort-'.bin2hex(random_bytes(4)), 'name' => 'Sort test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        foreach ([['SORT-OLDER', '2026-10-01', '2026-10-05'], ['SORT-NEWER', '2026-11-01', '2026-11-05'],
            ['SORT-EMPTY', null, null]] as [$code, $ordered, $returned]) {
            WorkshopOrder::create(['product_code' => $code, 'supply_source' => WorkshopOrder::SUPPLY_OUTSIDE,
                'quantity' => 1, 'unit_price' => 0, 'status' => WorkshopOrder::DEFAULT_STATUS,
                'ordered_date' => $ordered, 'returned_date' => $returned]);
        }
        $this->get('/workshop-orders?supply_source=mua_ngoai&q=SORT-')->assertOk()
            ->assertSeeInOrder(['value="SORT-NEWER"', 'value="SORT-OLDER"', 'value="SORT-EMPTY"'], false)
            ->assertSee('aria-sort="descending"', false);
        foreach (['ordered_date', 'returned_date'] as $column) {
            $url = '/workshop-orders?supply_source=mua_ngoai&q=SORT-&sort='.$column;
            $descending = $this->get($url.'&direction=desc')->assertOk()
                ->assertSeeInOrder(['value="SORT-NEWER"', 'value="SORT-OLDER"', 'value="SORT-EMPTY"'], false)
                ->assertSee('aria-sort="descending"', false);
            preg_match('/<th aria-sort="descending">\s*<a href="([^"]+)"/', $descending->getContent(), $matches);
            $ascendingUrl = html_entity_decode($matches[1]);
            $this->assertStringContainsString('direction=asc', $ascendingUrl);
            $this->get($ascendingUrl)->assertOk()
                ->assertSeeInOrder(['value="SORT-OLDER"', 'value="SORT-NEWER"', 'value="SORT-EMPTY"'], false);
            $this->get($url.'&direction=asc')->assertOk()
                ->assertSeeInOrder(['value="SORT-OLDER"', 'value="SORT-NEWER"', 'value="SORT-EMPTY"'], false)
                ->assertSee('aria-sort="ascending"', false);
        }
        $this->get('/workshop-orders?sort=invalid&direction=invalid')->assertOk();
    }

    public function test_default_list_prioritizes_not_ordered_rows_by_newest_date(): void
    {
        $this->actingAs(User::create(['code' => 'sort-'.bin2hex(random_bytes(4)), 'name' => 'Sort test',
            'password' => 'test-password', 'role' => 'admin', 'delflag' => false]));
        foreach ([['DEFAULT-DONE', '2026-12-01', WorkshopOrder::STATUS_ORDERED],
            ['DEFAULT-OLD', '2026-10-01', WorkshopOrder::STATUS_NOT_YET],
            ['DEFAULT-NEW', '2026-11-01', WorkshopOrder::STATUS_NOT_YET],
            ['DEFAULT-ARRIVED', '2026-12-02', WorkshopOrder::STATUS_ARRIVED]] as [$code, $date, $status]) {
            WorkshopOrder::create(['product_code' => $code, 'supply_source' => WorkshopOrder::SUPPLY_OUTSIDE,
                'quantity' => 1, 'unit_price' => 0, 'status' => $status, 'ordered_date' => $date]);
        }
        $this->get('/workshop-orders?supply_source=mua_ngoai&q=DEFAULT-')->assertOk()
            ->assertSeeInOrder(['value="DEFAULT-NEW"', 'value="DEFAULT-OLD"',
                'value="DEFAULT-ARRIVED"', 'value="DEFAULT-DONE"'], false);
        $this->get('/workshop-orders?supply_source=mua_ngoai&q=DEFAULT-&sort=ordered_date&direction=desc')->assertOk()
            ->assertSeeInOrder(['value="DEFAULT-ARRIVED"', 'value="DEFAULT-DONE"',
                'value="DEFAULT-NEW"', 'value="DEFAULT-OLD"'], false);
    }
}
