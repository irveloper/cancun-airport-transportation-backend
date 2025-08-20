<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import FormField from '@/components/FormField.vue';
import SelectField from '@/components/SelectField.vue';

interface Zone {
    id: number;
    name: string;
    city?: {
        id: number;
        name: string;
    };
}

interface Location {
    id: number;
    name: string;
    address: string;
    latitude?: number;
    longitude?: number;
    zone_id: number;
    zone?: {
        id: number;
        name: string;
        city?: {
            id: number;
            name: string;
        };
    };
    created_at: string;
    updated_at: string;
}

interface Props {
    location: Location;
    zones: Zone[];
}

const { location, zones } = defineProps<Props>();

const zoneOptions = zones.map(zone => ({
    value: zone.id,
    label: `${zone.name} (${zone.city?.name || 'Unknown City'})`
}));

const typeOptions = [
    { value: 'H', label: 'Hotel' },
    { value: 'B', label: 'Bus Station' },
    { value: 'F', label: 'Ferry' },
    { value: 'R', label: 'Restaurant' },
    { value: 'A', label: 'Airport' }
];

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Locations',
        href: '/dashboard/locations',
    },
    {
        title: location.name,
        href: `/dashboard/locations/${location.id}`,
    },
    {
        title: 'Edit',
        href: `/dashboard/locations/${location.id}/edit`,
    },
];

const form = useForm({
    name: location.name,
    address: location.address,
    latitude: location.latitude?.toString() || '',
    longitude: location.longitude?.toString() || '',
    zone_id: location.zone_id.toString(),
    type: location.type,
});

const submit = () => {
    form.put(`/dashboard/locations/${location.id}`);
};
</script>

<template>
    <Head :title="`Edit Location: ${location.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div>
                <h1 class="text-2xl font-bold">Edit Location: {{ location.name }}</h1>
                <p class="text-muted-foreground">Update the location information</p>
            </div>

            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>Location Information</CardTitle>
                    <CardDescription>Update the details for this location</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <FormField
                            id="name"
                            label="Location Name"
                            v-model="form.name"
                            placeholder="Enter location name"
                            :error="form.errors.name"
                            :required="true"
                        />

                        <FormField
                            id="address"
                            label="Address"
                            v-model="form.address"
                            placeholder="Enter full address"
                            :error="form.errors.address"
                            :required="true"
                        />

                        <div class="grid grid-cols-2 gap-4">
                            <FormField
                                id="latitude"
                                label="Latitude"
                                v-model="form.latitude"
                                type="number"
                                step="any"
                                placeholder="e.g. 40.7128"
                                :error="form.errors.latitude"
                            />

                            <FormField
                                id="longitude"
                                label="Longitude"
                                v-model="form.longitude"
                                type="number"
                                step="any"
                                placeholder="e.g. -74.0060"
                                :error="form.errors.longitude"
                            />
                        </div>

                        <SelectField
                            id="zone_id"
                            label="Zone"
                            v-model="form.zone_id"
                            :options="zoneOptions"
                            placeholder="Select a zone"
                            :error="form.errors.zone_id"
                            :required="true"
                        />

                        <SelectField
                            id="type"
                            label="Location Type"
                            v-model="form.type"
                            :options="typeOptions"
                            placeholder="Select location type"
                            :error="form.errors.type"
                            :required="true"
                        />

                        <div class="flex gap-4">
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Updating...' : 'Update Location' }}
                            </Button>
                            <Button type="button" variant="outline" @click="$inertia.visit(`/dashboard/locations/${location.id}`)">
                                Cancel
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>