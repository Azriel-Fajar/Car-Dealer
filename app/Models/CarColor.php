<?php

namespace App\Models;

use App\Support\Media;
use Database\Factories\CarColorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $car_id
 * @property string $name
 * @property string $hex
 * @property int $price_delta
 * @property string|null $image_path
 * @property-read string|null $image_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['car_id', 'name', 'hex', 'price_delta', 'image_path'])]
class CarColor extends Model
{
    /** @use HasFactory<CarColorFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = ['image_url'];

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
