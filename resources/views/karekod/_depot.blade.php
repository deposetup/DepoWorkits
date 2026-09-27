@if (auth()->user()->isAdmin())
    <select name="depot_id" required>
        <option value="">Depo seçiniz</option>
        @foreach ($depots as $item)
            <option value="{{ $item->id }}" @selected(old('depot_id', $depot?->id) == $item->id)>{{ $item->company_title }}</option>
        @endforeach
    </select>
@endif
