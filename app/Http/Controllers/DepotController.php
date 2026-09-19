<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use Illuminate\Http\Request;

class DepotController extends Controller
{
    /**
     * Admin: tüm depoları listeler. Müşteri: sadece kendi deposu.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $depots = $user->isAdmin()
            ? Depot::with('activeLicense')->paginate(20)
            : Depot::with('activeLicense')->where('id', $user->depot_id)->get();

        return view('depots.index', compact('depots'));
    }

    /**
     * Müşteri panelinde GLN + İTS şifresi salt-okunur gösterilir.
     */
    public function show(Request $request, Depot $depot)
    {
        $user = $request->user();

        if (! $user->isAdmin() && $user->depot_id !== $depot->id) {
            abort(403);
        }

        $changeRequests = $depot->changeRequests()->latest()->get();

        return view('depots.show', compact('depot', 'changeRequests'));
    }

    /**
     * Admin: yeni depo (müşteri) kaydı oluşturma formu.
     */
    public function create()
    {
        return view('depots.create');
    }

    /**
     * Admin: yeni depo (müşteri) kaydı oluşturur.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'company_title' => ['required', 'string', 'max:255'],
            'authorized_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'gln_number' => ['required', 'string', 'max:20', 'unique:depots,gln_number'],
            'its_password' => ['required', 'string', 'min:6'],
            'status' => ['required', 'in:active,passive'],
        ]);

        $depot = Depot::create($data);

        return redirect()->route('depots.show', $depot)->with('status', 'Depo oluşturuldu.');
    }

    /**
     * Sadece admin GLN/şifre bilgisini günceller — müşteri tarafında
     * bu uç noktaya erişim yoktur (bkz. routes/web.php, role:admin).
     */
    public function update(Request $request, Depot $depot)
    {
        $data = $request->validate([
            'company_title' => ['required', 'string', 'max:255'],
            'authorized_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'gln_number' => ['required', 'string', 'max:20', 'unique:depots,gln_number,'.$depot->id],
            'its_password' => ['nullable', 'string', 'min:6'],
            'status' => ['required', 'in:active,passive'],
        ]);

        if (empty($data['its_password'])) {
            unset($data['its_password']);
        }

        $depot->update($data);

        return back()->with('status', 'Depo bilgileri güncellendi.');
    }
}
