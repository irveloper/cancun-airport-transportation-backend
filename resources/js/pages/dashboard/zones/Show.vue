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
    zone: Zone;
}

const { zone } = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Zones',
        href: '/dashboard/zones',
    },
    {
        title: zone.name,
        href: `/dashboard/zones/${zone.id}`,
    },
];

const deleteZone = () => {
    if (confirm('Are you sure you want to delete this zone?')) {
        router.delete(`/dashboard/zones/${zone.id}`, {
            onSuccess: () => router.visit('/dashboard/zones'),
        });
    }
};
</script>

<template>
    <Head :title="`Zone: ${zone.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">{{ zone.name }}</h1>
                    <p class="text-muted-foreground">Zone details and information</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="`/dashboard/zones/${zone.id}/edit`">
                        <Button variant="outline">Edit Zone</Button>
                    </Link>
                    <Button variant="destructive" @click="deleteZone">
                        Delete Zone
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Zone Information</CardTitle>
                        <CardDescription>Basic details about this zone</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Zone Name</label>
                            <p class="text-lg font-medium">{{ zone.name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">City</label>
                            <div class="text-lg">
                                <Link :href="`/dashboard/cities/${zone.city?.id}`" class="text-primary hover:underline">
                                    {{ zone.city?.name || 'N/A' }}
                                </Link>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>System Information</CardTitle>
                        <CardDescription>Timestamps and metadata</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">ID</label>
                            <p class="text-lg font-mono">{{ zone.id }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Created At</label>
                            <p class="text-lg">{{ new Date(zone.created_at).toLocaleString() }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Last Updated</label>
                            <p class="text-lg">{{ new Date(zone.updated_at).toLocaleString() }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>