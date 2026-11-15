<?php

namespace App\Support;

use DOMDocument;
use RuntimeException;
use ZipArchive;

/**
 * Giden PTS paketi (XML + zip). Biçim, İTS'ten alınan gerçek PTS paketlerinin (transfer, sürüm 1.4) birebir aynısıdır:
 * transfer > sourceGLN, destinationGLN, actionType=S, shipTo, documentNumber, documentDate, note, version, carrier(carrierLabel, containerType)
 * > productList(GTIN, lotNumber, expirationDate) > serialNumber*.
 */
class PtsXmlYazici
{
    private const XSI = 'http://www.w3.org/2001/XMLSchema-instance';

    /** @param array<int,array{gtin:string,sn:string,bn?:?string,xd?:?string}> $kalemler */
    public static function xml(string $kaynakGln, string $aliciGln, string $belgeNo, array $kalemler, string $carrierLabel): string
    {
        $d = new DOMDocument('1.0', 'utf-8');
        $d->formatOutput = true;
        $kok = $d->createElement('transfer');
        $kok->setAttributeNS(self::XSI, 'xsi:noNamespaceSchemaLocation', 'its.gov.tr/packageTransfer.xsd');
        $d->appendChild($kok);
        $metin = function (string $ad, string $deger) use ($d, $kok) {
            $e = $d->createElement($ad);
            $e->appendChild($d->createTextNode($deger));
            $kok->appendChild($e);
        };
        $metin('sourceGLN', $kaynakGln);
        $metin('destinationGLN', $aliciGln);
        $metin('actionType', 'S');
        $metin('shipTo', $aliciGln);
        $metin('documentNumber', $belgeNo);
        $metin('documentDate', now()->toDateString());
        $kok->appendChild($d->createElement('note'));
        $metin('version', '1.4');

        $tasiyici = $d->createElement('carrier');
        $tasiyici->setAttribute('carrierLabel', $carrierLabel);
        $tasiyici->setAttribute('containerType', 'S');
        $kok->appendChild($tasiyici);

        // Aynı GTIN + parti + SKT'li ürünler tek productList altında toplanır.
        $gruplar = [];
        foreach ($kalemler as $k) {
            $gruplar[$k['gtin'].'|'.($k['bn'] ?? '').'|'.substr((string) ($k['xd'] ?? ''), 0, 10)][] = $k;
        }
        foreach ($gruplar as $grup) {
            $ilk = $grup[0];
            $pl = $d->createElement('productList');
            $pl->setAttribute('GTIN', $ilk['gtin']);
            if (! empty($ilk['bn'])) { $pl->setAttribute('lotNumber', $ilk['bn']); }
            if (! empty($ilk['xd'])) { $pl->setAttribute('expirationDate', substr($ilk['xd'], 0, 10)); }
            foreach ($grup as $k) {
                $sn = $d->createElement('serialNumber');
                $sn->appendChild($d->createTextNode($k['sn']));
                $pl->appendChild($sn);
            }
            $tasiyici->appendChild($pl);
        }

        return $d->saveXML();
    }

    /** SSCC (taşıyıcı etiketi): "00" + 17 hane (uzantı + GLN'in ilk 12 hanesi + 4 hane sıra) + GS1 mod-10 kontrol hanesi. */
    public static function carrierLabel(string $gln, int $sira): string
    {
        $sira = max(0, $sira) % 100000;
        $govde = (string) intdiv($sira, 10000).substr($gln, 0, 12).str_pad((string) ($sira % 10000), 4, '0', STR_PAD_LEFT);
        $toplam = 0;
        foreach (array_reverse(str_split($govde)) as $i => $h) {
            $toplam += ((int) $h) * ($i % 2 === 0 ? 3 : 1);
        }

        return '00'.$govde.((10 - $toplam % 10) % 10);
    }

    /** XML'i tek dosyalı zip'e koyar, zip baytlarını döndürür. */
    public static function zip(string $xml, string $dosyaAdi = 'paket.xml'): string
    {
        $gecici = tempnam(sys_get_temp_dir(), 'ptsg');
        $zip = new ZipArchive();
        if ($zip->open($gecici, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Zip oluşturulamadı.');
        }
        $zip->addFromString($dosyaAdi, $xml);
        $zip->close();
        $bayt = file_get_contents($gecici);
        @unlink($gecici);

        return $bayt;
    }
}
