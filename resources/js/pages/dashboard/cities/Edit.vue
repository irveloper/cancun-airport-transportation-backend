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
    {
        title: 'Edit',
        href: `/dashboard/cities/${city.id}/edit`,
    },
];

const form = useForm({
    name: city.name,
    state: city.state,
    country: city.country,
});

const submit = () => {
    form.put(`/dashboard/cities/${city.id}`);
};
</script>

<template>
    <Head :title="`Edit City: ${city.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div>
                <h1 class="text-2xl font-bold">Edit City: {{ city.name }}</h1>
                <p class="text-muted-foreground">Update the city information</p>
            </div>

            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>City Information</CardTitle>
                    <CardDescription>Update the details for this city</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2">
                            <Label for="name">City Name *</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Enter city name"
                                :class="{ 'border-destructive': form.errors.name }"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="state">State/Province *</Label>
                            <Input
                                id="state"
                                v-model="form.state"
                                type="text"
                                placeholder="Enter state or province"
                                :class="{ 'border-destructive': form.errors.state }"
                            />
                            <InputError :message="form.errors.state" />
                        </div>

                        <div class="space-y-2">
                            <Label for="country">Country *</Label>
                            <Input
                                id="country"
                                v-model="form.country"
                                type="text"
                                placeholder="Enter country"
                                :class="{ 'border-destructive': form.errors.country }"
                            />
                            <InputError :message="form.errors.country" />
                        </div>

                        <div class="flex gap-4">
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Updating...' : 'Update City' }}
                            </Button>
                            <Button type="button" variant="outline" @click="$inertia.visit(`/dashboard/cities/${city.id}`)">
                                Cancel
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>