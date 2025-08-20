<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

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
        title: 'Create',
        href: '/dashboard/cities/create',
    },
];

const form = useForm({
    name: '',
    state: '',
    country: '',
});

const submit = () => {
    form.post('/dashboard/cities', {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Create City" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div>
                <h1 class="text-2xl font-bold">Create New City</h1>
                <p class="text-muted-foreground">Add a new city to the system</p>
            </div>

            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>City Information</CardTitle>
                    <CardDescription>Enter the details for the new city</CardDescription>
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
                                {{ form.processing ? 'Creating...' : 'Create City' }}
                            </Button>
                            <Button type="button" variant="outline" @click="$inertia.visit('/dashboard/cities')">
                                Cancel
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>