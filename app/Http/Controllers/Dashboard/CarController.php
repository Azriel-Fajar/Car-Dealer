<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\CarBadge;
use App\Enums\CarCondition;
use App\Enums\CarStatus;
use App\Enums\FuelType;
use App\Enums\OptionGroup;
use App\Enums\Transmission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\CarRequest;
use App\Models\Brand;
use App\Models\Car;
use App\Models\Category;
use App\Models\Showroom;
use App\Support\CarSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CarController extends Controller
{
    private const TABS = ['info', 'specs', 'colors', 'options', 'images', 'hotspots'];

    public function index(Request $request): Response
    {
        $q = $request->string('q')->trim()->toString();
        $brand = $request->integer('brand') ?: null;
        $category = $request->integer('category') ?: null;
        $status = CarStatus::tryFrom($request->string('status')->toString());
        $trashed = $request->query('trashed') === '1';
        $view = $request->query('view') === 'cards' ? 'cards' : 'list';

        $items = Car::query()
            ->with(['brand:id,name', 'category:id,name', 'cover'])
            ->when($trashed, fn ($query) => $query->onlyTrashed())
            ->when($q !== '', fn ($query) => $query->where(
                fn ($query) => $query->where('name', 'like', "%{$q}%")
                    ->orWhereHas('brand', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ))
            ->when($brand, fn ($query) => $query->where('brand_id', $brand))
            ->when($category, fn ($query) => $query->where('category_id', $category))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Car $car) => [
                'id' => $car->id,
                'name' => $car->name,
                'slug' => $car->slug,
                'year' => $car->year,
                'brand_name' => $car->brand->name,
                'category_name' => $car->category->name,
                'base_price' => $car->base_price,
                'stock' => $car->stock,
                'status' => $car->status->value,
                'status_label' => $car->status->label(),
                'badge_label' => $car->badge?->label(),
                'is_featured' => $car->is_featured,
                'cover_url' => $car->cover?->url,
                'is_trashed' => $car->trashed(),
            ]);

        return Inertia::render('dashboard/cars/index', [
            'items' => $items,
            'filters' => [
                'q' => $q === '' ? null : $q,
                'brand' => $brand === null ? null : (string) $brand,
                'category' => $category === null ? null : (string) $category,
                'status' => $status?->value,
                'trashed' => $trashed ? '1' : null,
                'view' => $view,
            ],
            'options' => [
                'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
                'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
                'statuses' => CarStatus::options(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('dashboard/cars/create', [
            'options' => $this->formOptions(),
            'tab' => $this->tab($request),
        ]);
    }

    public function store(CarRequest $request): RedirectResponse
    {
        $car = new Car($this->attributes($request));
        $car->slug = $this->slugFor($car);
        $car->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Mobil ditambahkan. Lanjutkan isi warna & opsi.']);

        return to_route('dashboard.cars.edit', ['car' => $car, 'tab' => 'colors']);
    }

    public function edit(Request $request, Car $car): Response
    {
        $car->load(['brand:id,name', 'images', 'colors', 'options']);

        return Inertia::render('dashboard/cars/edit', [
            'car' => $car,
            'options' => $this->formOptions(),
            'tab' => $this->tab($request),
        ]);
    }

    public function update(CarRequest $request, Car $car): RedirectResponse
    {
        $car->fill($this->attributes($request));

        // The slug is a public URL: only rebuild it when its source fields change.
        if ($car->isDirty(['brand_id', 'name', 'year'])) {
            $car->slug = $this->slugFor($car);
        }

        $car->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Mobil diperbarui.']);

        return back(fallback: route('dashboard.cars.edit', $car));
    }

    public function destroy(Car $car): RedirectResponse
    {
        $car->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Mobil dihapus.']);

        return $this->backToIndex();
    }

    public function restore(Car $car): RedirectResponse
    {
        $car->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Mobil dipulihkan.']);

        return $this->backToIndex();
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(CarRequest $request): array
    {
        $data = $request->validated();

        return [
            ...$data,
            'brand_id' => (int) $data['brand_id'],
            'year' => (int) $data['year'],
            // cars.engine is NOT NULL; an empty field (e.g. some EVs) is stored as ''.
            'engine' => $data['engine'] ?? '',
            'is_featured' => $request->boolean('is_featured'),
        ];
    }

    private function slugFor(Car $car): string
    {
        $brand = Brand::query()->findOrFail($car->brand_id);

        return CarSlug::for($brand->name, $car->name, $car->year, $car->exists ? $car->id : null);
    }

    private function tab(Request $request): string
    {
        $tab = $request->string('tab')->toString();

        return in_array($tab, self::TABS, true) ? $tab : 'info';
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'showrooms' => Showroom::query()->orderBy('name')->get(['id', 'name', 'city']),
            'fuelTypes' => FuelType::options(),
            'transmissions' => Transmission::options(),
            'conditions' => CarCondition::options(),
            'statuses' => CarStatus::options(),
            'badges' => CarBadge::options(),
            'optionGroups' => OptionGroup::options(),
        ];
    }

    /**
     * Return to the list the admin came from, keeping its search, filters and page.
     */
    private function backToIndex(): RedirectResponse
    {
        return back(fallback: route('dashboard.cars.index'));
    }
}
