<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\CarColorRequest;
use App\Models\Car;
use App\Models\CarColor;
use App\Support\Media;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class CarColorController extends Controller
{
    public function store(CarColorRequest $request, Car $car): RedirectResponse
    {
        $color = $car->colors()->make($this->attributes($request));
        $color->image_path = $request->hasFile('image') ? $this->storeImage($request) : null;
        $color->save();

        return $this->backToTab($car, 'Warna ditambahkan.');
    }

    public function update(CarColorRequest $request, Car $car, CarColor $color): RedirectResponse
    {
        $color->fill($this->attributes($request));
        $oldImage = null;

        if ($request->hasFile('image')) {
            $oldImage = $color->image_path;
            $color->image_path = $this->storeImage($request);
        }

        $color->save();
        Media::delete($oldImage);

        return $this->backToTab($car, 'Warna diperbarui.');
    }

    public function destroy(Car $car, CarColor $color): RedirectResponse
    {
        $color->delete();
        Media::delete($color->image_path);

        return $this->backToTab($car, $car->revertToDraftIfIncomplete() ? Car::REVERTED_TO_DRAFT_MESSAGE : 'Warna dihapus.');
    }

    /**
     * @return array{name: string, hex: string, price_delta: int}
     */
    private function attributes(CarColorRequest $request): array
    {
        return [
            'name' => $request->string('name')->trim()->toString(),
            'hex' => strtoupper($request->string('hex')->toString()),
            'price_delta' => $request->integer('price_delta'),
        ];
    }

    private function storeImage(CarColorRequest $request): string
    {
        return (string) Media::disk()->putFile('car-colors', $request->file('image'));
    }

    private function backToTab(Car $car, string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return to_route('dashboard.cars.edit', ['car' => $car, 'tab' => 'colors']);
    }
}
