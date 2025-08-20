<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    public function index(): Response
    {
        $locations = Location::with(['zone.city'])
            ->orderBy('name')
            ->get();

        return Inertia::render('dashboard/locations/Index', [
            'locations' => $locations
        ]);
    }

    public function create(): Response
    {
        $zones = Zone::with('city')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $types = [
            'H' => 'Hotel',
            'B' => 'Bus Station',
            'F' => 'Ferry',
            'R' => 'Restaurant',
            'A' => 'Airport'
        ];

        return Inertia::render('dashboard/locations/Create', [
            'zones' => $zones,
            'types' => $types
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'zone_id' => 'required|exists:zones,id',
            'type' => 'required|string|max:10',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'active' => 'boolean'
        ]);

        Location::create($validated);

        return redirect()->route('dashboard.locations.index')
            ->with('success', 'Location created successfully.');
    }

    public function show(Location $location): Response
    {
        $location->load(['zone.city']);

        return Inertia::render('dashboard/locations/Show', [
            'location' => $location
        ]);
    }

    public function edit(Location $location): Response
    {
        $zones = Zone::with('city')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $types = [
            'H' => 'Hotel',
            'B' => 'Bus Station',
            'F' => 'Ferry',
            'R' => 'Restaurant',
            'A' => 'Airport'
        ];

        return Inertia::render('dashboard/locations/Edit', [
            'location' => $location,
            'zones' => $zones,
            'types' => $types
        ]);
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'zone_id' => 'required|exists:zones,id',
            'type' => 'required|string|max:10',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'active' => 'boolean'
        ]);

        $location->update($validated);

        return redirect()->route('dashboard.locations.index')
            ->with('success', 'Location updated successfully.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $location->delete();

        return redirect()->route('dashboard.locations.index')
            ->with('success', 'Location deleted successfully.');
    }
}
