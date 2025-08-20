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
    airport: Airport;
}

const { airport } = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Airports',
        href: '/dashboard/airports',
    },
    {
        title: airport.name,
        href: `/dashboard/airports/${airport.id}`,
    },
];

const deleteAirport = () => {
    if (confirm('Are you sure you want to delete this airport?')) {
        router.delete(`/dashboard/airports/${airport.id}`, {
            onSuccess: () => router.visit('/dashboard/airports'),
        });
    }
};
</script>

<template>
    <Head :title="`Airport: ${airport.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">{{ airport.name }}</h1>
                    <p class="text-muted-foreground">Airport details and information</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="`/dashboard/airports/${airport.id}/edit`">
                        <Button variant="outline">Edit Airport</Button>
                    </Link>
                    <Button variant="destructive" @click="deleteAirport">
                        Delete Airport
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Airport Information</CardTitle>
                        <CardDescription>Basic details about this airport</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Airport Name</label>
                            <p class="text-lg font-medium">{{ airport.name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Airport Code</label>
                            <p class="text-lg font-mono">{{ airport.code }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">City</label>
                            <div class="text-lg">
                                <Link :href="`/dashboard/cities/${airport.city.id}`" class="text-primary hover:underline">
                                    {{ airport.city.name }}
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
                            <p class="text-lg font-mono">{{ airport.id }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Created At</label>
                            <p class="text-lg">{{ new Date(airport.created_at).toLocaleString() }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Last Updated</label>
                            <p class="text-lg">{{ new Date(airport.updated_at).toLocaleString() }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>