<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

interface Zone {
    id: number;
    name: string;
    city_id: number;
    city?: {
        id: number;
        name: string;
    };
    created_at: string;
    updated_at: string;
}

interface Props {
    zones: Zone[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Zones',
        href: '/dashboard/zones',
    },
];

const deleteZone = (id: number) => {
    if (confirm('Are you sure you want to delete this zone?')) {
        router.delete(`/dashboard/zones/${id}`);
    }
};
</script>

<template>
    <Head title="Zones Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">Zones Management</h1>
                    <p class="text-muted-foreground">Manage zones within cities</p>
                </div>
                <Link href="/dashboard/zones/create">
                    <Button>Add New Zone</Button>
                </Link>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Zones</CardTitle>
                    <CardDescription>List of all zones in the system</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left p-2">ID</th>
                                    <th class="text-left p-2">Zone Name</th>
                                    <th class="text-left p-2">City</th>
                                    <th class="text-left p-2">Created</th>
                                    <th class="text-left p-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="zone in zones" :key="zone.id" class="border-b hover:bg-muted/50">
                                    <td class="p-2">{{ zone.id }}</td>
                                    <td class="p-2 font-medium">{{ zone.name }}</td>
                                    <td class="p-2">{{ zone.city?.name || 'N/A' }}</td>
                                    <td class="p-2">{{ new Date(zone.created_at).toLocaleDateString() }}</td>
                                    <td class="p-2">
                                        <div class="flex gap-2">
                                            <Link :href="`/dashboard/zones/${zone.id}`">
                                                <Button variant="outline" size="sm">View</Button>
                                            </Link>
                                            <Link :href="`/dashboard/zones/${zone.id}/edit`">
                                                <Button variant="outline" size="sm">Edit</Button>
                                            </Link>
                                            <Button 
                                                variant="destructive" 
                                                size="sm"
                                                @click="deleteZone(zone.id)"
                                            >
                                                Delete
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="zones.length === 0">
                                    <td colspan="5" class="p-8 text-center text-muted-foreground">
                                        No zones found. <Link href="/dashboard/zones/create" class="text-primary hover:underline">Create your first zone</Link>
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