<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

interface Airport {
    id: number;
    name: string;
    code: string;
    city: {
        id: number;
        name: string;
    };
    created_at: string;
    updated_at: string;
}

interface Props {
    airports: Airport[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Airports',
        href: '/dashboard/airports',
    },
];

const deleteAirport = (id: number) => {
    if (confirm('Are you sure you want to delete this airport?')) {
        router.delete(`/dashboard/airports/${id}`);
    }
};
</script>

<template>
    <Head title="Airports Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">Airports Management</h1>
                    <p class="text-muted-foreground">Manage airports in the system</p>
                </div>
                <Link href="/dashboard/airports/create">
                    <Button>Add New Airport</Button>
                </Link>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Airports</CardTitle>
                    <CardDescription>List of all airports in the system</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left p-2">ID</th>
                                    <th class="text-left p-2">Name</th>
                                    <th class="text-left p-2">Code</th>
                                    <th class="text-left p-2">City</th>
                                    <th class="text-left p-2">Created</th>
                                    <th class="text-left p-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="airport in airports" :key="airport.id" class="border-b hover:bg-muted/50">
                                    <td class="p-2">{{ airport.id }}</td>
                                    <td class="p-2 font-medium">{{ airport.name }}</td>
                                    <td class="p-2 font-mono">{{ airport.code }}</td>
                                    <td class="p-2">{{ airport.city.name }}</td>
                                    <td class="p-2">{{ new Date(airport.created_at).toLocaleDateString() }}</td>
                                    <td class="p-2">
                                        <div class="flex gap-2">
                                            <Link :href="`/dashboard/airports/${airport.id}`">
                                                <Button variant="outline" size="sm">View</Button>
                                            </Link>
                                            <Link :href="`/dashboard/airports/${airport.id}/edit`">
                                                <Button variant="outline" size="sm">Edit</Button>
                                            </Link>
                                            <Button 
                                                variant="destructive" 
                                                size="sm"
                                                @click="deleteAirport(airport.id)"
                                            >
                                                Delete
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="airports.length === 0">
                                    <td colspan="6" class="p-8 text-center text-muted-foreground">
                                        No airports found. <Link href="/dashboard/airports/create" class="text-primary hover:underline">Create your first airport</Link>
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