<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\BrandRequest;
use App\Models\Brand;
use App\Support\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function index(Request $request): Response
    {
        $q = $request->string('q')->trim()->toString();

        $items = Brand::query()
            ->withCount(['cars' => fn ($query) => $query->withTrashed()])
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('dashboard/brands/index', [
            'items' => $items,
            'filters' => ['q' => $q === '' ? null : $q],
        ]);
    }

    public function store(BrandRequest $request): RedirectResponse
    {
        $name = $request->string('name')->toString();

        Brand::query()->create([
            'name' => $name,
            'slug' => Str::slug($name),
            'logo_path' => $request->hasFile('logo') ? $this->storeLogo($request) : null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Brand ditambahkan.']);

        return $this->backToIndex();
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $name = $request->string('name')->toString();
        $brand->fill(['name' => $name, 'slug' => Str::slug($name)]);
        $oldLogo = null;

        if ($request->hasFile('logo')) {
            $oldLogo = $brand->logo_path;
            $brand->logo_path = $this->storeLogo($request);
        }

        $brand->save();
        Media::delete($oldLogo);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Brand diperbarui.']);

        return $this->backToIndex();
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $carsCount = $brand->cars()->withTrashed()->count();

        if ($carsCount > 0) {
            Inertia::flash('toast', ['type' => 'error', 'message' => "Brand masih dipakai {$carsCount} mobil."]);

            return $this->backToIndex();
        }

        $brand->delete();
        Media::delete($brand->logo_path);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Brand dihapus.']);

        return $this->backToIndex();
    }

    /**
     * Return to the list the admin came from, keeping its search and page.
     */
    private function backToIndex(): RedirectResponse
    {
        return back(fallback: route('dashboard.brands.index'));
    }

    private function storeLogo(BrandRequest $request): string
    {
        return (string) Media::disk()->putFile('brands', $request->file('logo'));
    }
}
