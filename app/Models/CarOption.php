<?php

namespace App\Models;

use App\Enums\OptionGroup;
use App\Support\Media;
use Database\Factories\CarOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $car_id
 * @property OptionGroup $group
 * @property string $name
 * @property string|null $description
 * @property int $price
 * @property string|null $image_path
 * @property-read string|null $image_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['car_id', 'group', 'name', 'description', 'price', 'image_path'])]
class CarOption extends Model
{
    /** @use HasFactory<CarOptionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = ['image_url'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'group' => OptionGroup::class,
        ];
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => Media::url($this->image_path));
    }

    /**
     * @return BelongsTo<Car, $this>
     */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }
}
