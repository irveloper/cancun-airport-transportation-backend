<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

interface City {
    id: number;
    name: string;
    state: string;
    country: string;
    created_at: string;
    updated_at: string;
}

interface Props {
    cities: City[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Cities',
        href: '/dashboard/cities',
    },
];

const deleteCity = (id: number) => {
    if (confirm('Are you sure you want to delete this city?')) {
        router.delete(`/dashboard/cities/${id}`);
    }
};
</script>

<template>
    <Head title="Cities Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">Cities Management</h1>
                    <p class="text-muted-foreground">Manage cities in the system</p>
                </div>
                <Link href="/dashboard/cities/create">
                    <Button>Add New City</Button>
                </Link>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Cities</CardTitle>
                    <CardDescription>List of all cities in the system</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left p-2">ID</th>
                                    <th class="text-left p-2">Name</th>
                                    <th class="text-left p-2">State</th>
                                    <th class="text-left p-2">Country</th>
                                    <th class="text-left p-2">Created</th>
                                    <th class="text-left p-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="city in cities" :key="city.id" class="border-b hover:bg-muted/50">
                                    <td class="p-2">{{ city.id }}</td>
                                    <td class="p-2 font-medium">{{ city.name }}</td>
                                    <td class="p-2">{{ city.state }}</td>
                                    <td class="p-2">{{ city.country }}</td>
                                    <td class="p-2">{{ new Date(city.created_at).toLocaleDateString() }}</td>
                                    <td class="p-2">
                                        <div class="flex gap-2">
                                            <Link :href="`/dashboard/cities/${city.id}`">
                                                <Button variant="outline" size="sm">View</Button>
                                            </Link>
                                            <Link :href="`/dashboard/cities/${city.id}/edit`">
                                                <Button variant="outline" size="sm">Edit</Button>
                                            </Link>
                                            <Button 
                                                variant="destructive" 
                                                size="sm"
                                                @click="deleteCity(city.id)"
                                            >
                                                Delete
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="cities.length === 0">
                                    <td colspan="6" class="p-8 text-center text-muted-foreground">
                                        No cities found. <Link href="/dashboard/cities/create" class="text-primary hover:underline">Create your first city</Link>
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