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
    locations: Location[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Locations',
        href: '/dashboard/locations',
    },
];

const deleteLocation = (id: number) => {
    if (confirm('Are you sure you want to delete this location?')) {
        router.delete(`/dashboard/locations/${id}`);
    }
};
</script>

<template>
    <Head title="Locations Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">Locations Management</h1>
                    <p class="text-muted-foreground">Manage locations within zones</p>
                </div>
                <Link href="/dashboard/locations/create">
                    <Button>Add New Location</Button>
                </Link>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Locations</CardTitle>
                    <CardDescription>List of all locations in the system</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left p-2">ID</th>
                                    <th class="text-left p-2">Location Name</th>
                                    <th class="text-left p-2">Address</th>
                                    <th class="text-left p-2">Zone</th>
                                    <th class="text-left p-2">City</th>
                                    <th class="text-left p-2">Created</th>
                                    <th class="text-left p-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="location in locations" :key="location.id" class="border-b hover:bg-muted/50">
                                    <td class="p-2">{{ location.id }}</td>
                                    <td class="p-2 font-medium">{{ location.name }}</td>
                                    <td class="p-2">{{ location.address }}</td>
                                    <td class="p-2">{{ location.zone?.name || 'N/A' }}</td>
                                    <td class="p-2">{{ location.zone?.city?.name || 'N/A' }}</td>
                                    <td class="p-2">{{ new Date(location.created_at).toLocaleDateString() }}</td>
                                    <td class="p-2">
                                        <div class="flex gap-2">
                                            <Link :href="`/dashboard/locations/${location.id}`">
                                                <Button variant="outline" size="sm">View</Button>
                                            </Link>
                                            <Link :href="`/dashboard/locations/${location.id}/edit`">
                                                <Button variant="outline" size="sm">Edit</Button>
                                            </Link>
                                            <Button 
                                                variant="destructive" 
                                                size="sm"
                                                @click="deleteLocation(location.id)"
                                            >
                                                Delete
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="locations.length === 0">
                                    <td colspan="7" class="p-8 text-center text-muted-foreground">
                                        No locations found. <Link href="/dashboard/locations/create" class="text-primary hover:underline">Create your first location</Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>