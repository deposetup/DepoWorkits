<?php

namespace App\Support;

use DOMDocument;
use RuntimeException;
use ZipArchive;

/**
 * Giden PTS paketi (XML + zip). UYARI: Bakanlık XML şeması elimizdeki kılavuzlarda yok (docs/acik-sorular.md #9).
 * Etiket adları PtsXmlParser'ın okuduğu biçimle aynıdır; gerçek bir örnek alınınca yalnız bu sınıf düzeltilir.
 */
class PtsXmlYazici
{
    /** @param array<int,array{gtin:string,sn:string,bn?:?string,xd?:?string}> $kalemler */
    public static function xml(string $kaynakGln, string $aliciGln, string $belgeNo, array $kalemler): string
    {
        $d = new DOMDocument('1.0', 'UTF-8');
        $d->formatOutput = true;
        $kok = $d->createElement('transfer');
        $d->appendChild($kok);
        $kok->appendChild($d->createElement('sourceGLN', $kaynakGln));
        $kok->appendChild($d->createElement('destinationGLN', $aliciGln));
        $kok->appendChild($d->createElement('documentNumber', htmlspecialchars($belgeNo, ENT_XML1)));
        $kok->appendChild($d->createElement('documentDate', now()->toDateString()));
        $liste = $d->createElement('productList');
        $kok->appendChild($liste);
        foreach ($kalemler as $k) {
            $p = $d->createElement('product');
            $p->appendChild($d->createElement('GTIN', $k['gtin']));
            if (! empty($k['bn'])) { $p->appendChild($d->createElement('lotNumber', htmlspecialchars($k['bn'], ENT_XML1))); }
            if (! empty($k['xd'])) { $p->appendChild($d->createElement('expirationDate', substr($k['xd'], 0, 10))); }
            $p->appendChild($d->createElement('serialNumber', htmlspecialchars($k['sn'], ENT_XML1)));
            $liste->appendChild($p);
        }

        return $d->saveXML();
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
