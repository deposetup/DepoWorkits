<?php

namespace App\Services\Its;

final class ItsSonuc
{
    /** @param array<int,array{gtin:string,sn:?string,uc:?string,hata:?string}> $urunler */
    public function __construct(
        public readonly bool $basarili,
        public readonly ?string $bildirimId = null,
        public readonly array $urunler = [],
        public readonly ?string $hataKodu = null,
        public readonly ?string $hataMesaji = null,
    ) {}
}
