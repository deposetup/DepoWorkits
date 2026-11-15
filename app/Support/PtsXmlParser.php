<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;

/**
 * PTS paket XML'i için tolerant okuyucu. Bakanlık şeması elimizdeki kılavuzlarda yok;
 * bu yüzden belirli bir şemaya bağlanmak yerine GTIN + seri no taşıyan düğümler aranır.
 * Hiçbir ürün bulunmazsa yapı özeti (etiket adları) döndürülür ki biçim doğrulanıp okuyucu netleştirilebilsin.
 */
class PtsXmlParser
{
    private const GTIN = ['gtin'];
    private const SN = ['serialnumber', 'sn', 'serialno', 'serial'];
    private const LOT = ['lotnumber', 'batchnumber', 'lot', 'bn', 'batch'];
    private const SKT = ['expirationdate', 'expirydate', 'expiredate', 'xd', 'skt'];
    private const BELGE = ['documentnumber', 'belgeno', 'dn'];
    private const KAYNAK = ['sourcegln', 'fr', 'sender'];
    private const ALICI = ['destinationgln', 'rt', 'receiver'];

    /** @return array{urunler:array<int,array{gtin:string,sn:string,bn:?string,xd:?string}>,belge:?string,kaynak:?string,yapi:array<string,int>} */
    public static function parse(string $xml): array
    {
        if (preg_match('/<!(DOCTYPE|ENTITY)/i', $xml)) {
            throw new InvalidArgumentException('XML güvenlik nedeniyle reddedildi (DOCTYPE/ENTITY içeriyor).');
        }
        $onceki = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $ok = $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($onceki);
        if (! $ok) {
            throw new InvalidArgumentException('Geçerli bir XML değil.');
        }

        $urunler = [];
        $yapi = [];
        $belge = $kaynak = $alici = null;

        foreach ($dom->getElementsByTagName('*') as $el) {
            /** @var DOMElement $el */
            $ad = strtolower($el->localName);
            $yapi[$el->localName] = ($yapi[$el->localName] ?? 0) + 1;
            if (in_array($ad, self::BELGE, true) && $belge === null) { $belge = trim($el->textContent) ?: null; }
            if (in_array($ad, self::ALICI, true) && $alici === null) { $alici = trim($el->textContent) ?: null; }
            if (in_array($ad, self::KAYNAK, true) && $kaynak === null) { $kaynak = trim($el->textContent) ?: null; }

            $alan = self::alanlar($el);
            $gtin = self::bul($alan, self::GTIN);
            if ($gtin === null) { continue; }

            $snler = self::snler($el, $alan);
            $bn = self::bul($alan, self::LOT);
            $xd = self::tarih(self::bul($alan, self::SKT));
            foreach ($snler as $sn) {
                $urunler[] = ['gtin' => str_pad(preg_replace('/\D/', '', $gtin), 14, '0', STR_PAD_LEFT), 'sn' => $sn, 'bn' => $bn, 'xd' => $xd];
            }
        }

        arsort($yapi);

        return ['urunler' => $urunler, 'belge' => $belge, 'kaynak' => $kaynak, 'alici' => $alici, 'yapi' => array_slice($yapi, 0, 40, true)];
    }

    /** Düğümün öznitelikleri ve yaprak çocuklarının metni (küçük harfli ad => değer). */
    private static function alanlar(DOMElement $el): array
    {
        $a = [];
        foreach ($el->attributes as $at) {
            $a[strtolower($at->name)][] = trim($at->value);
        }
        foreach ($el->childNodes as $c) {
            if ($c instanceof DOMElement && ! self::elemanVar($c)) {
                $a[strtolower($c->localName)][] = trim($c->textContent);
            }
        }

        return $a;
    }

    private static function elemanVar(DOMElement $el): bool
    {
        foreach ($el->childNodes as $c) {
            if ($c instanceof DOMElement) { return true; }
        }

        return false;
    }

    private static function bul(array $alan, array $adlar): ?string
    {
        foreach ($adlar as $ad) {
            if (! empty($alan[$ad][0])) { return $alan[$ad][0]; }
        }

        return null;
    }

    /** @return array<int,string> */
    private static function snler(DOMElement $el, array $alan): array
    {
        foreach (self::SN as $ad) {
            if (! empty($alan[$ad])) { return array_values(array_filter($alan[$ad], fn ($v) => $v !== '')); }
        }
        // <serialNumbers><serialNumber>..</serialNumber>… biçimi
        $out = [];
        foreach (self::SN as $ad) {
            foreach ($el->getElementsByTagName('*') as $d) {
                if (strtolower($d->localName) === $ad && ! self::elemanVar($d) && trim($d->textContent) !== '') {
                    $out[] = trim($d->textContent);
                }
            }
        }

        return array_values(array_unique($out));
    }

    private static function tarih(?string $v): ?string
    {
        if ($v === null) { return null; }
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $v, $m)) { return $m[1]; }
        $t = strtotime($v);

        return $t ? date('Y-m-d', $t) : null;
    }
}
