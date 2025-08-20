<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

interface City {
    id: number;
    name: string;
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
        title: 'Zones',
        href: '/dashboard/zones',
    },
    {
        title: 'Create',
        href: '/dashboard/zones/create',
    },
];

const form = useForm({
    name: '',
    city_id: '',
});

const submit = () => {
    form.post('/dashboard/zones', {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Create Zone" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div>
                <h1 class="text-2xl font-bold">Create New Zone</h1>
                <p class="text-muted-foreground">Add a new zone to a city</p>
            </div>

            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>Zone Information</CardTitle>
                    <CardDescription>Enter the details for the new zone</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2">
                            <Label for="name">Zone Name *</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Enter zone name"
                                :class="{ 'border-destructive': form.errors.name }"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="city_id">City *</Label>
                            <select
                                id="city_id"
                                v-model="form.city_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-destructive': form.errors.city_id }"
                            >
                                <option value="">Select a city</option>
                                <option v-for="city in cities" :key="city.id" :value="city.id">
                                    {{ city.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.city_id" />
                        </div>

                        <div class="flex gap-4">
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Creating...' : 'Create Zone' }}
                            </Button>
                            <Button type="button" variant="outline" @click="$inertia.visit('/dashboard/zones')">
                                Cancel
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>