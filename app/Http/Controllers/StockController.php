<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use App\Models\ItsStock;
use Illuminate\Http\Request;

/**
 * İTS stok tablosu: her sabah 06:00'da çekilen son stok görüntüsü.
 */
class StockController extends Controller
{
    public function index(Request $request)
    {
        [$depot, $depots] = $this->resolveDepot($request);
        $stocks = $depot ? $this->latestStocks($depot) : collect();

        return view('stocks.index', compact('depot', 'depots', 'stocks'));
    }

    public function export(Request $request)
    {
        [$depot] = $this->resolveDepot($request);
        abort_unless($depot, 404);

        $stocks = $this->latestStocks($depot);
        $date = optional($stocks->first())->stock_date?->format('d.m.Y') ?? now()->subDay()->format('d.m.Y');

        return response(ItsStock::toCsv($stocks), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"its-stok-{$date}.csv\"",
        ]);
    }

    /**
     * Admin ?depot= ile depo seçer; müşteri sadece kendi deposunu görür.
     */
    protected function resolveDepot(Request $request): array
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            abort_unless($user->depot, 403);

            return [$user->depot, collect()];
        }

        $depots = Depot::orderBy('company_title')->get();

        return [$request->filled('depot') ? $depots->firstWhere('id', $request->integer('depot')) : null, $depots];
    }

    protected function latestStocks(Depot $depot)
    {
        $latestDate = $depot->itsStocks()->max('stock_date');

        return $latestDate
            ? $depot->itsStocks()->where('stock_date', $latestDate)->orderBy('product_name')->get()
            : collect();
    }
}
