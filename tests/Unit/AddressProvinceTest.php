<?php

namespace Tests\Unit;

use App\Services\AddressProvinceService;
use PHPUnit\Framework\TestCase;

class AddressProvinceTest extends TestCase
{
    public function test_address_suffix_is_used_instead_of_street_name(): void
    {
        $service = new AddressProvinceService();
        $this->assertSame('Hải Phòng', $service->detect('số 6/ngõ 1041 Tôn Đức Thắng, Sở Dầu, Hồng Bàng, Hải Phòng'));
        $this->assertSame('Hà Nội', $service->detect('12 đường Hòa Bình, Hà Nội, Việt Nam'));
        $this->assertSame('Thừa Thiên Huế', $service->detect('Phú Vang, Thừa Thiên Huế'));
        $this->assertSame('TP. Hồ Chí Minh', $service->detect('Quận 1, TP.HCM'));
        $this->assertSame('Đắk Lắk', $service->detect('Buon Ma Thuot, Dak Lak'));
        $this->assertSame('Chưa xác định', $service->detect('12 đường Hòa Bình, phường 3'));
        $this->assertSame('Chưa xác định', $service->detect(null));
    }
}
