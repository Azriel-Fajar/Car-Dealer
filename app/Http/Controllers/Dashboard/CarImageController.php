<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ReorderCarImagesRequest;
use App\Http\Requests\Dashboard\StoreCarImagesRequest;
use App\Models\Car;
use App\Models\CarImage;
use App\Support\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CarImageController extends Controller
{
    public function store(StoreCarImagesRequest $request, Car $car): RedirectResponse
    {
        /** @var list<UploadedFile> $files */
        $files = $request->file('images');
        $hasImages = $car->images()->exists();
        $nextOrder = $hasImages ? (int) $car->images()->reorder()->max('sort_order') + 1 : 0;
        // The first photo of a car becomes its cover.
        $needsCover = ! $car->images()->where('is_cover', true)->exists();

        foreach ($files as $index => $file) {
            $car->images()->create([
                'path' => (string) Media::disk()->putFile('cars', $file),
                'sort_order' => $nextOrder + $index,
                'is_cover' => $needsCover && $index === 0,
            ]);
        }

        return $this->backToTab($car, count($files).' foto diunggah.');
    }

    public function cover(Car $car, CarImage $image): RedirectResponse
    {
        DB::transaction(function () use ($car, $image): void {
            CarImage::query()->whereBelongsTo($car)->where('is_cover', true)->update(['is_cover' => false]);
            $image->update(['is_cover' => true]);
        });

        return $this->backToTab($car, 'Cover diperbarui.');
    }

    public function order(ReorderCarImagesRequest $request, Car $car): RedirectResponse
    {
        DB::transaction(function () use ($request, $car): void {
            foreach ($request->ids() as $position => $id) {
                CarImage::query()->whereBelongsTo($car)->whereKey($id)->update(['sort_order' => $position]);
            }
        });

        return $this->backToTab($car, 'Urutan foto disimpan.');
    }

    public function destroy(Car $car, CarImage $image): RedirectResponse
    {
        DB::transaction(function () use ($car, $image): void {
            $image->delete();

            if ($image->is_cover) {
                $car->images()->orderBy('id')->first()?->update(['is_cover' => true]);
            }
        });

        Media::delete($image->path);

        return $this->backToTab($car, $car->revertToDraftIfIncomplete() ? Car::REVERTED_TO_DRAFT_MESSAGE : 'Foto dihapus.');
    }

    private function backToTab(Car $car, string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return to_route('dashboard.cars.edit', ['car' => $car, 'tab' => 'images']);
    }
}
