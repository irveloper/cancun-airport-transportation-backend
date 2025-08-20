<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CityController extends Controller
{
    public function index(): Response
    {
        $cities = City::withCount(['zones', 'locations'])
            ->orderBy('name')
            ->get();

        return Inertia::render('dashboard/cities/Index', [
            'cities' => $cities
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('dashboard/cities/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:cities,name',
            'active' => 'boolean'
        ]);

        City::create($validated);

        return redirect()->route('dashboard.cities.index')
            ->with('success', 'City created successfully.');
    }

    public function show(City $city): Response
    {
        $city->load(['zones', 'locations']);
        $city->loadCount(['zones', 'locations']);

        return Inertia::render('dashboard/cities/Show', [
            'city' => $city
        ]);
    }

    public function edit(City $city): Response
    {
        return Inertia::render('dashboard/cities/Edit', [
            'city' => $city
        ]);
    }

    public function update(Request $request, City $city): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:cities,name,' . $city->id,
            'active' => 'boolean'
        ]);

        $city->update($validated);

        return redirect()->route('dashboard.cities.index')
            ->with('success', 'City updated successfully.');
    }

    public function destroy(City $city): RedirectResponse
    {
        $city->delete();

        return redirect()->route('dashboard.cities.index')
            ->with('success', 'City deleted successfully.');
    }
}
