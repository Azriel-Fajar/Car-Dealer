<?php

namespace Tests\Feature\Dashboard;

use App\Enums\CarStatus;
use App\Enums\OptionGroup;
use App\Models\Car;
use App\Models\CarColor;
use App\Models\CarImage;
use App\Models\CarOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CarMediaTest extends TestCase
{
    use RefreshDatabase;

    private const REVERT_MESSAGE = 'Mobil dikembalikan ke draft karena tidak punya foto/warna.';

    private User $admin;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = User::factory()->admin()->create();
        $this->car = Car::factory()->create(['status' => CarStatus::Draft]);
    }

    private function editUrl(string $tab, ?Car $car = null): string
    {
        return route('dashboard.cars.edit', ['car' => $car ?? $this->car, 'tab' => $tab]);
    }

    private function storedImage(string $folder): string
    {
        return (string) UploadedFile::fake()->image('seed.png')->store($folder, 'public');
    }

    private function svgDisguisedAsPng(): UploadedFile
    {
        $tmp = (string) tempnam(sys_get_temp_dir(), 'svg');
        file_put_contents($tmp, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        return new UploadedFile($tmp, 'x.png', null, null, true);
    }

    // Colors

    public function test_color_store_update_and_delete_with_file_cleanup(): void
    {
        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.colors.store', $this->car), [
                'name' => 'Attitude Black',
                'hex' => '#1A1A1A',
                'price_delta' => 5_000_000,
                'image' => UploadedFile::fake()->image('black.png'),
            ])
            ->assertRedirect($this->editUrl('colors'))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Warna ditambahkan.']);

        $color = CarColor::query()->sole();
        $oldPath = (string) $color->image_path;

        $this->assertSame($this->car->id, $color->car_id);
        $this->assertSame('#1A1A1A', $color->hex);
        $this->assertSame(5_000_000, $color->price_delta);
        $this->assertStringStartsWith('car-colors/', $oldPath);
        Storage::disk('public')->assertExists($oldPath);

        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.colors.update', [$this->car, $color]), [
                '_method' => 'PUT',
                'name' => 'Super White',
                'hex' => '#FFFFFF',
                'price_delta' => -2_000_000,
                'image' => UploadedFile::fake()->image('white.png'),
            ])
            ->assertRedirect($this->editUrl('colors'))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Warna diperbarui.']);

        $color->refresh();

        $this->assertSame('Super White', $color->name);
        $this->assertSame(-2_000_000, $color->price_delta);
        $this->assertNotSame($oldPath, $color->image_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists((string) $color->image_path);

        // Updating without a new file keeps the current one.
        $this->actingAs($this->admin)
            ->put(route('dashboard.cars.colors.update', [$this->car, $color]), [
                'name' => 'Super White',
                'hex' => '#FFFFFF',
                'price_delta' => 0,
            ])
            ->assertSessionHasNoErrors();

        $currentPath = (string) $color->image_path;
        $this->assertSame($currentPath, $color->refresh()->image_path);

        $this->actingAs($this->admin)
            ->delete(route('dashboard.cars.colors.destroy', [$this->car, $color]))
            ->assertRedirect($this->editUrl('colors'))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Warna dihapus.']);

        $this->assertModelMissing($color);
        Storage::disk('public')->assertMissing($currentPath);
    }

    public function test_color_validation_rejects_invalid_hex_duplicate_name_and_out_of_range_delta(): void
    {
        CarColor::factory()->create(['car_id' => $this->car->id, 'name' => 'Merah']);

        foreach (['red', '#FFF', '#GGGGGG', 'FFFFFF', '#FFFFFFF'] as $hex) {
            $this->actingAs($this->admin)
                ->post(route('dashboard.cars.colors.store', $this->car), [
                    'name' => 'Biru', 'hex' => $hex, 'price_delta' => 0,
                ])
                ->assertSessionHasErrors('hex');
        }

        // Same name on the same car is a validation error, not a 500.
        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.colors.store', $this->car), [
                'name' => 'Merah', 'hex' => '#FF0000', 'price_delta' => 0,
            ])
            ->assertSessionHasErrors('name');

        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.colors.store', $this->car), [
                'name' => 'Biru', 'hex' => '#0000FF', 'price_delta' => -100_000_001,
            ])
            ->assertSessionHasErrors('price_delta');

        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.colors.store', $this->car), [
                'name' => str_repeat('a', 41), 'hex' => '#0000FF', 'price_delta' => 500_000_001,
            ])
            ->assertSessionHasErrors(['name', 'price_delta']);

        $this->assertSame(1, CarColor::query()->count());

        // The same name is fine on another car, and when a color keeps its own name.
        $other = Car::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.colors.store', $other), [
                'name' => 'Merah', 'hex' => '#FF0000', 'price_delta' => 0,
            ])
            ->assertSessionHasNoErrors();

        $color = CarColor::query()->where('car_id', $this->car->id)->sole();

        $this->actingAs($this->admin)
            ->put(route('dashboard.cars.colors.update', [$this->car, $color]), [
                'name' => 'Merah', 'hex' => '#EE0000', 'price_delta' => 0,
            ])
            ->assertSessionHasNoErrors();
    }

    // Options

    public function test_option_store_grouped_with_enum_validation(): void
    {
        foreach (OptionGroup::cases() as $group) {
            $this->actingAs($this->admin)
                ->post(route('dashboard.cars.options.store', $this->car), [
                    'group' => $group->value,
                    'name' => "Opsi {$group->value}",
                    'description' => 'Keterangan singkat.',
                    'price' => 7_500_000,
                    'image' => UploadedFile::fake()->image('opt.png'),
                ])
                ->assertRedirect($this->editUrl('options'))
                ->assertSessionHasNoErrors()
                ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Opsi ditambahkan.']);
        }

        $this->assertSame(
            ['interior', 'package', 'wheels'],
            CarOption::query()->orderBy('group')->get()->map(fn (CarOption $option) => $option->group->value)->all(),
        );
        $option = CarOption::query()->where('group', OptionGroup::Wheels)->sole();
        $this->assertSame(7_500_000, $option->price);
        $this->assertStringStartsWith('car-options/', (string) $option->image_path);

        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.options.store', $this->car), [
                'group' => 'spoiler',
                'name' => '',
                'description' => str_repeat('a', 301),
                'price' => -1,
            ])
            ->assertSessionHasErrors(['group', 'name', 'description', 'price']);

        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.options.store', $this->car), [
                'group' => 'wheels', 'name' => str_repeat('a', 61), 'price' => 500_000_001,
            ])
            ->assertSessionHasErrors(['name', 'price']);

        // A 300-character description fits the column.
        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.options.store', $this->car), [
                'group' => 'interior', 'name' => 'Jok kulit', 'description' => str_repeat('a', 300), 'price' => 0,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(4, CarOption::query()->count());
    }

    public function test_option_update_and_delete_with_file_cleanup(): void
    {
        $oldPath = $this->storedImage('car-options');
        $option = CarOption::factory()->create(['car_id' => $this->car->id, 'image_path' => $oldPath]);

        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.options.update', [$this->car, $option]), [
                '_method' => 'PUT',
                'group' => 'package',
                'name' => 'Paket Sport',
                'description' => null,
                'price' => 25_000_000,
                'image' => UploadedFile::fake()->image('sport.png'),
            ])
            ->assertRedirect($this->editUrl('options'))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Opsi diperbarui.']);

        $option->refresh();

        $this->assertSame(OptionGroup::Package, $option->group);
        $this->assertSame('Paket Sport', $option->name);
        $this->assertNull($option->description);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists((string) $option->image_path);

        $newPath = (string) $option->image_path;

        $this->actingAs($this->admin)
            ->delete(route('dashboard.cars.options.destroy', [$this->car, $option]))
            ->assertRedirect($this->editUrl('options'))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Opsi dihapus.']);

        $this->assertModelMissing($option);
        Storage::disk('public')->assertMissing($newPath);
    }

    // Images

    public function test_images_multi_upload_first_becomes_cover(): void
    {
        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.images.store', $this->car), [
                'images' => [
                    UploadedFile::fake()->image('a.png'),
                    UploadedFile::fake()->image('b.png'),
                    UploadedFile::fake()->image('c.png'),
                ],
            ])
            ->assertRedirect($this->editUrl('images'))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '3 foto diunggah.']);

        $images = $this->car->images()->get();

        $this->assertCount(3, $images);
        $this->assertSame([true, false, false], $images->pluck('is_cover')->all());
        $this->assertSame([0, 1, 2], $images->pluck('sort_order')->all());

        foreach ($images as $image) {
            $this->assertStringStartsWith('cars/', $image->path);
            Storage::disk('public')->assertExists($image->path);
        }

        // A later batch is appended after the existing photos and keeps the current cover.
        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.images.store', $this->car), [
                'images' => [UploadedFile::fake()->image('d.png')],
            ])
            ->assertSessionHasNoErrors();

        $images = $this->car->images()->get();

        $this->assertSame([0, 1, 2, 3], $images->pluck('sort_order')->all());
        $this->assertSame(1, $images->where('is_cover', true)->count());
        $this->assertTrue($images->first()?->is_cover);
    }

    public function test_images_upload_limits_per_request_and_per_car(): void
    {
        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.images.store', $this->car), [
                'images' => array_map(fn (int $i) => UploadedFile::fake()->image("{$i}.png", 10, 10), range(1, 11)),
            ])
            ->assertSessionHasErrors('images');

        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.images.store', $this->car), ['images' => []])
            ->assertSessionHasErrors('images');

        CarImage::factory()->count(18)->create(['car_id' => $this->car->id]);

        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.images.store', $this->car), [
                'images' => array_map(fn (int $i) => UploadedFile::fake()->image("{$i}.png", 10, 10), range(1, 3)),
            ])
            ->assertSessionHasErrors('images');

        $this->assertSame(18, $this->car->images()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.images.store', $this->car), [
                'images' => array_map(fn (int $i) => UploadedFile::fake()->image("{$i}.png", 10, 10), range(1, 2)),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(20, $this->car->images()->count());
    }

    public function test_set_cover_clears_previous(): void
    {
        $first = CarImage::factory()->cover()->create(['car_id' => $this->car->id, 'sort_order' => 0]);
        $second = CarImage::factory()->create(['car_id' => $this->car->id, 'sort_order' => 1]);

        $this->actingAs($this->admin)
            ->patch(route('dashboard.cars.images.cover', [$this->car, $second]))
            ->assertRedirect($this->editUrl('images'))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Cover diperbarui.']);

        $this->assertFalse($first->refresh()->is_cover);
        $this->assertTrue($second->refresh()->is_cover);
    }

    public function test_reorder_saves_order_and_rejects_wrong_id_set(): void
    {
        $a = CarImage::factory()->cover()->create(['car_id' => $this->car->id, 'sort_order' => 0]);
        $b = CarImage::factory()->create(['car_id' => $this->car->id, 'sort_order' => 1]);
        $c = CarImage::factory()->create(['car_id' => $this->car->id, 'sort_order' => 2]);
        $foreign = CarImage::factory()->create();

        $wrongSets = [
            [$a->id, $b->id],
            [$a->id, $b->id, $c->id, $foreign->id],
            [$a->id, $b->id, $foreign->id],
            [$a->id, $a->id, $b->id],
            [$a->id, $b->id, $c->id, $c->id],
            'nope',
        ];

        foreach ($wrongSets as $ids) {
            $this->actingAs($this->admin)
                ->patchJson(route('dashboard.cars.images.order', $this->car), ['ids' => $ids])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('ids');
        }

        $this->assertSame([$a->id, $b->id, $c->id], $this->car->images()->pluck('id')->all());

        $this->actingAs($this->admin)
            ->patch(route('dashboard.cars.images.order', $this->car), ['ids' => [$c->id, $a->id, $b->id]])
            ->assertRedirect($this->editUrl('images'))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Urutan foto disimpan.']);

        $this->assertSame([$c->id, $a->id, $b->id], $this->car->images()->pluck('id')->all());
        $this->assertSame(0, $foreign->refresh()->sort_order);
    }

    public function test_delete_cover_promotes_next_and_deletes_file(): void
    {
        $coverPath = $this->storedImage('cars');
        $cover = CarImage::factory()->cover()->create(['car_id' => $this->car->id, 'path' => $coverPath, 'sort_order' => 0]);
        $later = CarImage::factory()->create(['car_id' => $this->car->id, 'sort_order' => 5]);
        $next = CarImage::factory()->create(['car_id' => $this->car->id, 'sort_order' => 2]);

        $this->actingAs($this->admin)
            ->delete(route('dashboard.cars.images.destroy', [$this->car, $cover]))
            ->assertRedirect($this->editUrl('images'))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Foto dihapus.']);

        $this->assertModelMissing($cover);
        Storage::disk('public')->assertMissing($coverPath);
        $this->assertTrue($next->refresh()->is_cover);
        $this->assertFalse($later->refresh()->is_cover);

        // Deleting a non-cover image leaves the cover alone.
        $this->actingAs($this->admin)
            ->delete(route('dashboard.cars.images.destroy', [$this->car, $later]))
            ->assertSessionHasNoErrors();

        $this->assertTrue($next->refresh()->is_cover);
    }

    // Security & consistency

    public function test_child_of_other_car_returns_404(): void
    {
        $carB = Car::factory()->create();
        $color = CarColor::factory()->create(['car_id' => $carB->id]);
        $option = CarOption::factory()->create(['car_id' => $carB->id]);
        $image = CarImage::factory()->cover()->create(['car_id' => $carB->id]);

        $colorPayload = ['name' => 'Hijau', 'hex' => '#00FF00', 'price_delta' => 0];
        $optionPayload = ['group' => 'wheels', 'name' => 'Velg', 'price' => 0];

        $this->actingAs($this->admin)->put(route('dashboard.cars.colors.update', [$this->car, $color]), $colorPayload)->assertNotFound();
        $this->actingAs($this->admin)->delete(route('dashboard.cars.colors.destroy', [$this->car, $color]))->assertNotFound();
        $this->actingAs($this->admin)->put(route('dashboard.cars.options.update', [$this->car, $option]), $optionPayload)->assertNotFound();
        $this->actingAs($this->admin)->delete(route('dashboard.cars.options.destroy', [$this->car, $option]))->assertNotFound();
        $this->actingAs($this->admin)->patch(route('dashboard.cars.images.cover', [$this->car, $image]))->assertNotFound();
        $this->actingAs($this->admin)->delete(route('dashboard.cars.images.destroy', [$this->car, $image]))->assertNotFound();

        $this->assertModelExists($color);
        $this->assertModelExists($option);
        $this->assertModelExists($image);
        $this->assertNotSame('Hijau', $color->refresh()->name);
    }

    public function test_rejects_svg_and_oversize_images(): void
    {
        $uploads = [
            'svg disguised' => fn () => UploadedFile::fake()->create('x.png', 10, 'image/svg+xml'),
            'svg content as png' => fn () => $this->svgDisguisedAsPng(),
            'oversize' => fn () => UploadedFile::fake()->image('big.png')->size(5 * 1024),
        ];

        foreach ($uploads as $file) {
            $this->actingAs($this->admin)
                ->post(route('dashboard.cars.colors.store', $this->car), [
                    'name' => 'Hitam', 'hex' => '#000000', 'price_delta' => 0, 'image' => $file(),
                ])
                ->assertSessionHasErrors('image');

            $this->actingAs($this->admin)
                ->post(route('dashboard.cars.options.store', $this->car), [
                    'group' => 'wheels', 'name' => 'Velg 18', 'price' => 0, 'image' => $file(),
                ])
                ->assertSessionHasErrors('image');

            $this->actingAs($this->admin)
                ->post(route('dashboard.cars.images.store', $this->car), [
                    'images' => [UploadedFile::fake()->image('ok.png'), $file()],
                ])
                ->assertSessionHasErrors('images.1');
        }

        $this->assertSame(0, CarColor::query()->count());
        $this->assertSame(0, CarOption::query()->count());
        $this->assertSame(0, CarImage::query()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_published_car_reverts_to_draft_when_last_image_deleted(): void
    {
        $car = Car::factory()->create(['status' => CarStatus::Published]);
        CarColor::factory()->create(['car_id' => $car->id]);
        $first = CarImage::factory()->cover()->create(['car_id' => $car->id, 'sort_order' => 0]);
        $last = CarImage::factory()->create(['car_id' => $car->id, 'sort_order' => 1]);

        $this->actingAs($this->admin)
            ->delete(route('dashboard.cars.images.destroy', [$car, $first]))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Foto dihapus.']);

        $this->assertSame(CarStatus::Published, $car->refresh()->status);

        $this->actingAs($this->admin)
            ->delete(route('dashboard.cars.images.destroy', [$car, $last]))
            ->assertRedirect($this->editUrl('images', $car))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => self::REVERT_MESSAGE]);

        $this->assertSame(CarStatus::Draft, $car->refresh()->status);
    }

    public function test_published_car_reverts_to_draft_when_last_color_deleted(): void
    {
        $car = Car::factory()->create(['status' => CarStatus::Published]);
        CarImage::factory()->cover()->create(['car_id' => $car->id]);
        $color = CarColor::factory()->create(['car_id' => $car->id]);

        $this->actingAs($this->admin)
            ->delete(route('dashboard.cars.colors.destroy', [$car, $color]))
            ->assertRedirect($this->editUrl('colors', $car))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => self::REVERT_MESSAGE]);

        $this->assertSame(CarStatus::Draft, $car->refresh()->status);

        // A draft car losing its last color stays a draft with the normal toast.
        $draftColor = CarColor::factory()->create(['car_id' => $this->car->id]);

        $this->actingAs($this->admin)
            ->delete(route('dashboard.cars.colors.destroy', [$this->car, $draftColor]))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Warna dihapus.']);

        $this->assertSame(CarStatus::Draft, $this->car->refresh()->status);
    }

    public function test_trashed_car_children_are_not_editable(): void
    {
        $color = CarColor::factory()->create(['car_id' => $this->car->id]);
        $this->car->delete();

        $this->actingAs($this->admin)
            ->post(route('dashboard.cars.colors.store', $this->car), ['name' => 'Biru', 'hex' => '#0000FF', 'price_delta' => 0])
            ->assertNotFound();
        $this->actingAs($this->admin)
            ->delete(route('dashboard.cars.colors.destroy', [$this->car, $color]))
            ->assertNotFound();
    }

    public function test_sales_cannot_manage_car_media(): void
    {
        $sales = User::factory()->sales()->create();
        $color = CarColor::factory()->create(['car_id' => $this->car->id]);
        $image = CarImage::factory()->cover()->create(['car_id' => $this->car->id]);

        $this->actingAs($sales)->post(route('dashboard.cars.colors.store', $this->car), [])->assertForbidden();
        $this->actingAs($sales)->delete(route('dashboard.cars.colors.destroy', [$this->car, $color]))->assertForbidden();
        $this->actingAs($sales)->post(route('dashboard.cars.options.store', $this->car), [])->assertForbidden();
        $this->actingAs($sales)->post(route('dashboard.cars.images.store', $this->car), [])->assertForbidden();
        $this->actingAs($sales)->patch(route('dashboard.cars.images.cover', [$this->car, $image]))->assertForbidden();
        $this->actingAs($sales)->patch(route('dashboard.cars.images.order', $this->car), ['ids' => [$image->id]])->assertForbidden();
        $this->actingAs($sales)->delete(route('dashboard.cars.images.destroy', [$this->car, $image]))->assertForbidden();

        $this->assertModelExists($color);
        $this->assertModelExists($image);
    }
}
