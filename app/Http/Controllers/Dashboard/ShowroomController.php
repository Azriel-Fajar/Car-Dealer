<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ShowroomRequest;
use App\Models\Showroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowroomController extends Controller
{
    public function index(Request $request): Response
    {
        $q = $request->string('q')->trim()->toString();

        $items = Showroom::query()
            ->withCount(['cars' => fn ($query) => $query->withTrashed()])
            ->when($q !== '', fn ($query) => $query->where(
                fn ($query) => $query->where('name', 'like', "%{$q}%")->orWhere('city', 'like', "%{$q}%")
            ))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('dashboard/showrooms/index', [
            'items' => $items,
            'filters' => ['q' => $q === '' ? null : $q],
        ]);
    }

    public function store(ShowroomRequest $request): RedirectResponse
    {
        Showroom::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Showroom ditambahkan.']);

        return $this->backToIndex();
    }

    public function update(ShowroomRequest $request, Showroom $showroom): RedirectResponse
    {
        $showroom->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Showroom diperbarui.']);

        return $this->backToIndex();
    }

    /**
     * Cars keep existing: the cars.showroom_id foreign key is nullOnDelete (soft-deleted cars included).
     */
    public function destroy(Showroom $showroom): RedirectResponse
    {
        $showroom->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Showroom dihapus.']);

        return $this->backToIndex();
    }

    /**
     * Return to the list the admin came from, keeping its search and page.
     */
    private function backToIndex(): RedirectResponse
    {
        return back(fallback: route('dashboard.showrooms.index'));
    }
}
