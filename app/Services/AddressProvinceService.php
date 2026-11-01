<?php

namespace App\Services;

use Illuminate\Support\Str;

class AddressProvinceService
{
    // Include place names still used in existing customer addresses.
    public const PROVINCES = [
        'An Giang', 'Bà Rịa - Vũng Tàu', 'Bắc Giang', 'Bắc Kạn', 'Bạc Liêu', 'Bắc Ninh',
        'Bến Tre', 'Bình Định', 'Bình Dương', 'Bình Phước', 'Bình Thuận', 'Cà Mau',
        'Cần Thơ', 'Cao Bằng', 'Đà Nẵng', 'Đắk Lắk', 'Đắk Nông', 'Điện Biên',
        'Đồng Nai', 'Đồng Tháp', 'Gia Lai', 'Hà Giang', 'Hà Nam', 'Hà Nội', 'Hà Tĩnh',
        'Hải Dương', 'Hải Phòng', 'Hậu Giang', 'Hòa Bình', 'Hưng Yên', 'Huế',
        'Khánh Hòa', 'Kiên Giang', 'Kon Tum', 'Lai Châu', 'Lâm Đồng', 'Lạng Sơn',
        'Lào Cai', 'Long An', 'Nam Định', 'Nghệ An', 'Ninh Bình', 'Ninh Thuận',
        'Phú Thọ', 'Phú Yên', 'Quảng Bình', 'Quảng Nam', 'Quảng Ngãi', 'Quảng Ninh',
        'Quảng Trị', 'Sóc Trăng', 'Sơn La', 'Tây Ninh', 'Thái Bình', 'Thái Nguyên',
        'Thanh Hóa', 'Thừa Thiên Huế', 'Tiền Giang', 'TP. Hồ Chí Minh', 'Trà Vinh',
        'Tuyên Quang', 'Vĩnh Long', 'Vĩnh Phúc', 'Yên Bái',
    ];

    public static function normalize(?string $text): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower(Str::ascii($text ?? ''))));
    }

    public function detect(?string $address): string
    {
        $address = self::normalize($address);
        $address = preg_replace('/\s+(viet nam|vietnam)$/', '', $address);
        $names = collect(self::PROVINCES)->sortByDesc(fn ($name) => strlen(self::normalize($name)));
        foreach ($names as $name) {
            $needle = self::normalize($name);
            if ($name === 'TP. Hồ Chí Minh') $needle = 'ho chi minh';
            if (preg_match('/(?:^| )'.preg_quote($needle, '/').'$/', $address)) return $name;
        }
        if (preg_match('/(?:^| )(tp hcm|tphcm|hcm|sai gon)$/', $address)) return 'TP. Hồ Chí Minh';

        return 'Chưa xác định';
    }
}
