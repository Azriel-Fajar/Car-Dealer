<?php

namespace App\Models;

use App\Enums\CarBadge;
use App\Enums\CarCondition;
use App\Enums\CarStatus;
use App\Enums\FuelType;
use App\Enums\Transmission;
use Database\Factories\CarFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $brand_id
 * @property int $category_id
 * @property int|null $showroom_id
 * @property string $name
 * @property string $slug
 * @property int $year
 * @property int $base_price
 * @property FuelType $fuel_type
 * @property Transmission $transmission
 * @property string $engine
 * @property int $power_hp
 * @property int $torque_nm
 * @property string|null $accel_0_100
 * @property int $top_speed
 * @property int|null $range_km
 * @property int $seats
 * @property int $mileage_km
 * @property CarCondition $condition
 * @property int $stock
 * @property CarBadge|null $badge
 * @property CarStatus $status
 * @property string|null $description
 * @property array<int, array{x: float, y: float, title: string, description: string}>|null $hotspots
 * @property bool $is_featured
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Brand $brand
 * @property-read Category $category
 * @property-read Showroom|null $showroom
 * @property-read CarImage|null $cover
 */
#[Fillable([
    'brand_id', 'category_id', 'showroom_id', 'name', 'slug', 'year', 'base_price',
    'fuel_type', 'transmission', 'engine', 'power_hp', 'torque_nm', 'accel_0_100',
    'top_speed', 'range_km', 'seats', 'mileage_km', 'condition', 'stock', 'badge',
    'status', 'description', 'hotspots', 'is_featured',
])]
class Car extends Model
{
    /** @use HasFactory<CarFactory> */
    use HasFactory, SoftDeletes;

    public const REVERTED_TO_DRAFT_MESSAGE = 'Mobil dikembalikan ke draft karena tidak punya foto/warna.';

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'fuel_type' => FuelType::class,
            'transmission' => Transmission::class,
            'condition' => CarCondition::class,
            'badge' => CarBadge::class,
            'status' => CarStatus::class,
            'hotspots' => 'array',
            'is_featured' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Showroom, $this>
     */
    public function showroom(): BelongsTo
    {
        return $this->belongsTo(Showroom::class);
    }

    /**
     * @return HasMany<CarImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(CarImage::class)->orderBy('sort_order');
    }

    /**
     * @return HasOne<CarImage, $this>
     */
    public function cover(): HasOne
    {
        return $this->hasOne(CarImage::class)->ofMany(['id' => 'max'], fn (Builder $query) => $query->where('is_cover', true));
    }

    /**
     * @return HasMany<CarColor, $this>
     */
    public function colors(): HasMany
    {
        return $this->hasMany(CarColor::class);
    }

    /**
     * @return HasMany<CarOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(CarOption::class);
    }

    /**
     * A published car must keep at least one photo and one color; when it loses
     * the last of either it goes back to draft. Returns whether it was reverted.
     */
    public function revertToDraftIfIncomplete(): bool
    {
        if ($this->status !== CarStatus::Published || ($this->images()->exists() && $this->colors()->exists())) {
            return false;
        }

        $this->update(['status' => CarStatus::Draft]);

        return true;
    }

    /**
     * @param  Builder<Car>  $query
     * @return Builder<Car>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CarStatus::Published);
    }
}
