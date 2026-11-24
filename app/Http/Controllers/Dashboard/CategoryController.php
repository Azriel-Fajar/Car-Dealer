<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $q = $request->string('q')->trim()->toString();

        $items = Category::query()
            ->withCount(['cars' => fn ($query) => $query->withTrashed()])
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('dashboard/categories/index', [
            'items' => $items,
            'filters' => ['q' => $q === '' ? null : $q],
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $name = $request->string('name')->toString();

        Category::query()->create(['name' => $name, 'slug' => Str::slug($name)]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori ditambahkan.']);

        return $this->backToIndex();
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $name = $request->string('name')->toString();

        $category->update(['name' => $name, 'slug' => Str::slug($name)]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori diperbarui.']);

        return $this->backToIndex();
    }

    public function destroy(Category $category): RedirectResponse
    {
        $carsCount = $category->cars()->withTrashed()->count();

        if ($carsCount > 0) {
            Inertia::flash('toast', ['type' => 'error', 'message' => "Kategori masih dipakai {$carsCount} mobil."]);

            return $this->backToIndex();
        }

        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori dihapus.']);

        return $this->backToIndex();
    }

    /**
     * Return to the list the admin came from, keeping its search and page.
     */
    private function backToIndex(): RedirectResponse
    {
        return back(fallback: route('dashboard.categories.index'));
    }
}
