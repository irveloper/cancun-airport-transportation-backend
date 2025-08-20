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

interface Airport {
    id: number;
    name: string;
    code: string;
    city_id: number;
    city: {
        id: number;
        name: string;
    };
    created_at: string;
    updated_at: string;
}

interface Props {
    airport: Airport;
    cities: City[];
}

const { airport, cities } = defineProps<Props>();

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
    {
        title: 'Edit',
        href: `/dashboard/airports/${airport.id}/edit`,
    },
];

const form = useForm({
    name: airport.name,
    code: airport.code,
    city_id: airport.city_id.toString(),
});

const cityOptions = cities.map(city => ({
    value: city.id,
    label: city.name
}));

const submit = () => {
    form.put(`/dashboard/airports/${airport.id}`);
};
</script>

<template>
    <Head :title="`Edit Airport: ${airport.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div>
                <h1 class="text-2xl font-bold">Edit Airport: {{ airport.name }}</h1>
                <p class="text-muted-foreground">Update the airport information</p>
            </div>

            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>Airport Information</CardTitle>
                    <CardDescription>Update the details for this airport</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <FormField
                            id="name"
                            label="Airport Name"
                            v-model="form.name"
                            placeholder="Enter airport name"
                            :error="form.errors.name"
                            :required="true"
                        />

                        <FormField
                            id="code"
                            label="Airport Code"
                            v-model="form.code"
                            placeholder="Enter airport code"
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
                                {{ form.processing ? 'Updating...' : 'Update Airport' }}
                            </Button>
                            <Button type="button" variant="outline" @click="$inertia.visit(`/dashboard/airports/${airport.id}`)">
                                Cancel
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>