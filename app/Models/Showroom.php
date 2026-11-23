<?php

namespace App\Models;

use Database\Factories\ShowroomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $city
 * @property string $address
 * @property string $phone
 * @property string $lat
 * @property string $lng
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'city', 'address', 'phone', 'lat', 'lng'])]
class Showroom extends Model
{
    /** @use HasFactory<ShowroomFactory> */
    use HasFactory;

    /**
     * @return HasMany<Car, $this>
     */
    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }
}
