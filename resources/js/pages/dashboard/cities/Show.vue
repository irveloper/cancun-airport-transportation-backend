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
    city: City;
}

const { city } = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Cities',
        href: '/dashboard/cities',
    },
    {
        title: city.name,
        href: `/dashboard/cities/${city.id}`,
    },
];

const deleteCity = () => {
    if (confirm('Are you sure you want to delete this city?')) {
        router.delete(`/dashboard/cities/${city.id}`, {
            onSuccess: () => router.visit('/dashboard/cities'),
        });
    }
};
</script>

<template>
    <Head :title="`City: ${city.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">{{ city.name }}</h1>
                    <p class="text-muted-foreground">City details and information</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="`/dashboard/cities/${city.id}/edit`">
                        <Button variant="outline">Edit City</Button>
                    </Link>
                    <Button variant="destructive" @click="deleteCity">
                        Delete City
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>City Information</CardTitle>
                        <CardDescription>Basic details about this city</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">City Name</label>
                            <p class="text-lg font-medium">{{ city.name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">State/Province</label>
                            <p class="text-lg">{{ city.state }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Country</label>
                            <p class="text-lg">{{ city.country }}</p>
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
                            <p class="text-lg font-mono">{{ city.id }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Created At</label>
                            <p class="text-lg">{{ new Date(city.created_at).toLocaleString() }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Last Updated</label>
                            <p class="text-lg">{{ new Date(city.updated_at).toLocaleString() }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>