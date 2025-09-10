<script setup lang="ts">
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

interface Option {
    value: string | number;
    label: string;
}

interface Props {
    id: string;
    label: string;
    modelValue: string | number;
    options: Option[];
    placeholder?: string;
    error?: string;
    required?: boolean;
}

withDefaults(defineProps<Props>(), {
    placeholder: 'Select an option',
    required: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: string | number];
}>();

const updateValue = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    emit('update:modelValue', target.value);
};
</script>

<template>
    <div class="space-y-2">
        <Label :for="id">
            {{ label }}
            <span v-if="required" class="text-destructive">*</span>
        </Label>
        <select
            :id="id"
            :value="modelValue"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            :class="{ 'border-destructive': error }"
            @change="updateValue"
        >
            <option value="">{{ placeholder }}</option>
            <option v-for="option in options" :key="option.value" :value="option.value">
                {{ option.label }}
            </option>
        </select>
        <InputError :message="error" />
    </div>
</template>