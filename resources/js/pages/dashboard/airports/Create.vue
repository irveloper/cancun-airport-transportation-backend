<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import FormField from '@/components/FormField.vue';
import SelectField from '@/components/SelectField.vue';

interface City {
    id: number;
    name: string;
}

interface Props {
    cities: City[];
}

const { cities } = defineProps<Props>();

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
        title: 'Create',
        href: '/dashboard/airports/create',
    },
];

const form = useForm({
    name: '',
    code: '',
    city_id: '',
});

const cityOptions = cities.map(city => ({
    value: city.id,
    label: city.name
}));

const submit = () => {
    form.post('/dashboard/airports', {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Create Airport" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div>
                <h1 class="text-2xl font-bold">Create New Airport</h1>
                <p class="text-muted-foreground">Add a new airport to the system</p>
            </div>

            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>Airport Information</CardTitle>
                    <CardDescription>Enter the details for the new airport</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <FormField
                            id="name"
                            label="Airport Name"
                            v-model="form.name"
                            placeholder="Enter airport name (e.g., Cancun International Airport)"
                            :error="form.errors.name"
                            :required="true"
                        />

                        <FormField
                            id="code"
                            label="Airport Code"
                            v-model="form.code"
                            placeholder="Enter airport code (e.g., CUN)"
                            :error="form.errors.code"
                            :required="true"
                        />

                        <SelectField
                            id="city_id"
                            label="City"
                            v-model="form.city_id"
                            :options="cityOptions"
                            placeholder="Select a city"
                            :error="form.errors.city_id"
                            :required="true"
                        />

                        <div class="flex gap-4">
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Creating...' : 'Create Airport' }}
                            </Button>
                            <Button type="button" variant="outline" @click="$inertia.visit('/dashboard/airports')">
                                Cancel
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>