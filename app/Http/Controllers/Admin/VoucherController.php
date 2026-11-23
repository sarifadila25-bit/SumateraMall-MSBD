<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mall;
use App\Models\Store;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VoucherController extends Controller
{
    public function index()
    {
        $vouchers = Voucher::with(['mall', 'store'])
            ->latest()
            ->paginate(15);

        return view('admin.vouchers.index', compact('vouchers'));
    }

    public function create()
    {
        $malls = Mall::orderBy('name')->get();
        $stores = Store::orderBy('name')->get();

        return view('admin.vouchers.create', compact('malls', 'stores'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'code' => ['required', 'string', 'max:255', 'unique:vouchers,code'],
            'discount_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'minimum_purchase' => ['nullable', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'quota' => ['nullable', 'integer', 'min:1'],
            'mall_id' => ['nullable', 'exists:malls,id'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'terms' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['code'] = Str::upper($validated['code']);
        $validated['minimum_purchase'] = $validated['minimum_purchase'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');

        Voucher::create($validated);

        return redirect()
            ->route('admin.vouchers.index')
            ->with('success', 'Voucher berhasil dibuat.');
    }

    public function show(Voucher $voucher)
    {
        return redirect()->route('admin.vouchers.edit', $voucher);
    }

    public function edit(Voucher $voucher)
    {
        $malls = Mall::orderBy('name')->get();
        $stores = Store::orderBy('name')->get();

        return view('admin.vouchers.edit', compact('voucher', 'malls', 'stores'));
    }

    public function update(Request $request, Voucher $voucher)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'code' => ['required', 'string', 'max:255', Rule::unique('vouchers', 'code')->ignore($voucher->id)],
            'discount_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'minimum_purchase' => ['nullable', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'quota' => ['nullable', 'integer', 'min:1'],
            'mall_id' => ['nullable', 'exists:malls,id'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'terms' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = Str::upper($validated['code']);
        $validated['minimum_purchase'] = $validated['minimum_purchase'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');

        $voucher->update($validated);

        return redirect()
            ->route('admin.vouchers.index')
            ->with('success', 'Voucher berhasil diperbarui.');
    }

    public function destroy(Voucher $voucher)
    {
        if ($voucher->userVouchers()->exists()) {
            return back()->with(
                'error',
                'Voucher sudah pernah diklaim dan tidak bisa dihapus. Nonaktifkan saja.'
            );
        }

        $voucher->delete();

        return redirect()
            ->route('admin.vouchers.index')
            ->with('success', 'Voucher berhasil dihapus.');
    }
}
