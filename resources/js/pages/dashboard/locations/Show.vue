<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

interface Location {
    id: number;
    name: string;
    address: string;
    latitude?: number;
    longitude?: number;
    zone_id: number;
    type: string;
    zone?: {
        id: number;
        name: string;
        city?: {
            id: number;
            name: string;
        };
    };
    created_at: string;
    updated_at: string;
}

interface Props {
    location: Location;
}

const { location } = defineProps<Props>();

const getLocationTypeName = (type: string) => {
    const types: Record<string, string> = {
        'H': 'Hotel',
        'B': 'Bus Station',
        'F': 'Ferry',
        'R': 'Restaurant',
        'A': 'Airport'
    };
    return types[type] || type;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Locations',
        href: '/dashboard/locations',
    },
    {
        title: location.name,
        href: `/dashboard/locations/${location.id}`,
    },
];

const deleteLocation = () => {
    if (confirm('Are you sure you want to delete this location?')) {
        router.delete(`/dashboard/locations/${location.id}`, {
            onSuccess: () => router.visit('/dashboard/locations'),
        });
    }
};
</script>

<template>
    <Head :title="`Location: ${location.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">{{ location.name }}</h1>
                    <p class="text-muted-foreground">Location details and information</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="`/dashboard/locations/${location.id}/edit`">
                        <Button variant="outline">Edit Location</Button>
                    </Link>
                    <Button variant="destructive" @click="deleteLocation">
                        Delete Location
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Location Information</CardTitle>
                        <CardDescription>Basic details about this location</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Location Name</label>
                            <p class="text-lg font-medium">{{ location.name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Address</label>
                            <p class="text-lg">{{ location.address }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Zone</label>
                            <div class="text-lg">
                                <Link :href="`/dashboard/zones/${location.zone?.id}`" class="text-primary hover:underline">
                                    {{ location.zone?.name || 'N/A' }}
                                </Link>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">City</label>
                            <div class="text-lg">
                                <Link :href="`/dashboard/cities/${location.zone?.city?.id}`" class="text-primary hover:underline">
                                    {{ location.zone?.city?.name || 'N/A' }}
                                </Link>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Location Type</label>
                            <p class="text-lg">{{ getLocationTypeName(location.type) }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Geographic Information</CardTitle>
                        <CardDescription>Coordinates and location data</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Latitude</label>
                            <p class="text-lg font-mono">{{ location.latitude || 'Not set' }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Longitude</label>
                            <p class="text-lg font-mono">{{ location.longitude || 'Not set' }}</p>
                        </div>
                        <div v-if="location.latitude && location.longitude">
                            <label class="text-sm font-medium text-muted-foreground">Maps</label>
                            <div class="mt-2">
                                <a 
                                    :href="`https://www.google.com/maps/search/?api=1&query=${location.latitude},${location.longitude}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-primary hover:underline"
                                >
                                    View on Google Maps ↗
                                </a>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="md:col-span-2">
                    <CardHeader>
                        <CardTitle>System Information</CardTitle>
                        <CardDescription>Timestamps and metadata</CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">ID</label>
                            <p class="text-lg font-mono">{{ location.id }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Created At</label>
                            <p class="text-lg">{{ new Date(location.created_at).toLocaleString() }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Last Updated</label>
                            <p class="text-lg">{{ new Date(location.updated_at).toLocaleString() }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>