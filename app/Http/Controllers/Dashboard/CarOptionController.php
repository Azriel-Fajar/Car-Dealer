<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\OptionGroup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\CarOptionRequest;
use App\Models\Car;
use App\Models\CarOption;
use App\Support\Media;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class CarOptionController extends Controller
{
    public function store(CarOptionRequest $request, Car $car): RedirectResponse
    {
        $option = $car->options()->make($this->attributes($request));
        $option->image_path = $request->hasFile('image') ? $this->storeImage($request) : null;
        $option->save();

        return $this->backToTab($car, 'Opsi ditambahkan.');
    }

    public function update(CarOptionRequest $request, Car $car, CarOption $option): RedirectResponse
    {
        $option->fill($this->attributes($request));
        $oldImage = null;

        if ($request->hasFile('image')) {
            $oldImage = $option->image_path;
            $option->image_path = $this->storeImage($request);
        }

        $option->save();
        Media::delete($oldImage);

        return $this->backToTab($car, 'Opsi diperbarui.');
    }

    public function destroy(Car $car, CarOption $option): RedirectResponse
    {
        $option->delete();
        Media::delete($option->image_path);

        return $this->backToTab($car, 'Opsi dihapus.');
    }

    /**
     * @return array{group: OptionGroup, name: string, description: string|null, price: int}
     */
    private function attributes(CarOptionRequest $request): array
    {
        $description = $request->string('description')->trim()->toString();

        return [
            'group' => OptionGroup::from($request->string('group')->toString()),
            'name' => $request->string('name')->trim()->toString(),
            'description' => $description === '' ? null : $description,
            'price' => $request->integer('price'),
        ];
    }

    private function storeImage(CarOptionRequest $request): string
    {
        return (string) Media::disk()->putFile('car-options', $request->file('image'));
    }

    private function backToTab(Car $car, string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return to_route('dashboard.cars.edit', ['car' => $car, 'tab' => 'options']);
    }
}
