<?php

namespace App\Http\Controllers;

use App\Models\PtsPaketi;
use App\Services\Its\ItsClient;
use App\Support\Gs1Parser;
use App\Support\ItsHata;
use App\Support\PtsXmlParser;
use Illuminate\Http\Request;

class DurumController extends Controller
{
    public function form()
    {
        return view('durum.index', ['sonuc' => null, 'hatalar' => [], 'baslik' => null]);
    }

    public function sorgula(Request $request, ItsClient $its)
    {
        $request->validate(['kodlar' => 'required|string|max:50000']);
        $urunler = []; $hatalar = [];
        foreach (preg_split('/\r\n|\r|\n/', $request->input('kodlar')) as $kod) {
            $kod = trim($kod);
            if ($kod === '') { continue; }
            try {
                $k = Gs1Parser::parse($kod);
                if (! $k['seri']) { throw new \InvalidArgumentException('Seri no (21) yok.'); }
                $urunler[] = ['gtin' => $k['gtin'], 'sn' => $k['seri'], 'bn' => $k['lot'], 'xd' => $k['skt']];
            } catch (\InvalidArgumentException $e) {
                $hatalar[] = mb_substr($kod, 0, 60).' → '.$e->getMessage();
            }
        }

        return $this->goster($request, $its, $urunler, $hatalar, null);
    }

    /** PTS paketindeki ürünleri İTS'te doğrular (en fazla 1000 ürün). */
    public function paket(Request $request, PtsPaketi $paket, ItsClient $its)
    {
        abort_unless($paket->tenant_id === $request->user()->tenant_id, 404);
        $urunler = [];
        foreach (json_decode($paket->xml, true) ?: [] as $xml) {
            try { $urunler = array_merge($urunler, PtsXmlParser::parse((string) $xml)['urunler']); } catch (\InvalidArgumentException) {}
        }
        $hatalar = [];
        if (count($urunler) > 1000) {
            $hatalar[] = 'Pakette '.count($urunler).' ürün var; yalnızca ilk 1000 tanesi sorgulandı.';
            $urunler = array_slice($urunler, 0, 1000);
        }

        return $this->goster($request, $its, $urunler, $hatalar, 'PTS paketi '.$paket->transfer_id);
    }

    private function goster(Request $request, ItsClient $its, array $urunler, array $hatalar, ?string $baslik)
    {
        $sonuc = null;
        if ($urunler) {
            try {
                $sonuc = array_map(fn ($r) => $r + ['aciklama' => ItsHata::basarili($r['uc']) ? 'Sorun yok' : (ItsHata::aciklama($r['uc']) ?? 'Bilinmeyen kod')], $its->durumSorgula($request->user()->tenant, $urunler));
            } catch (\Throwable $e) {
                $hatalar[] = $e->getMessage();
            }
        }

        return view('durum.index', ['sonuc' => $sonuc, 'hatalar' => $hatalar, 'baslik' => $baslik]);
    }
}
