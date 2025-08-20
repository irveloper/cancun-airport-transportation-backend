<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Zone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ZoneController extends Controller
{
    public function index(): Response
    {
        $zones = Zone::with('city')
            ->orderBy('name')
            ->get();

        return Inertia::render('dashboard/zones/Index', [
            'zones' => $zones
        ]);
    }

    public function create(): Response
    {
        $cities = City::where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('dashboard/zones/Create', [
            'cities' => $cities
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'city_id' => 'required|exists:cities,id',
            'description' => 'nullable|string',
            'active' => 'boolean'
        ]);

        Zone::create($validated);

        return redirect()->route('dashboard.zones.index')
            ->with('success', 'Zone created successfully.');
    }

    public function show(Zone $zone): Response
    {
        $zone->load('city');

        return Inertia::render('dashboard/zones/Show', [
            'zone' => $zone
        ]);
    }

    public function edit(Zone $zone): Response
    {
        $cities = City::where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('dashboard/zones/Edit', [
            'zone' => $zone,
            'cities' => $cities
        ]);
    }

    public function update(Request $request, Zone $zone): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'city_id' => 'required|exists:cities,id',
            'description' => 'nullable|string',
            'active' => 'boolean'
        ]);

        $zone->update($validated);

        return redirect()->route('dashboard.zones.index')
            ->with('success', 'Zone updated successfully.');
    }

    public function destroy(Zone $zone): RedirectResponse
    {
        $zone->delete();

        return redirect()->route('dashboard.zones.index')
            ->with('success', 'Zone deleted successfully.');
    }
}
