<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\CarHotspotRequest;
use App\Models\Car;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class CarHotspotController extends Controller
{
    /**
     * Replace the whole hotspot list of a car.
     */
    public function update(CarHotspotRequest $request, Car $car): RedirectResponse
    {
        $car->update(['hotspots' => $request->hotspots()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Hotspot disimpan.']);

        return to_route('dashboard.cars.edit', ['car' => $car, 'tab' => 'hotspots']);
    }
}
