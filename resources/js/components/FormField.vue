<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';

interface Props {
    id: string;
    label: string;
    modelValue: string;
    type?: string;
    placeholder?: string;
    error?: string;
    required?: boolean;
    step?: string;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'text',
    placeholder: '',
    required: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const updateValue = (event: Event) => {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', target.value);
};
</script>

<template>
    <div class="space-y-2">
        <Label :for="id">
            {{ label }}
            <span v-if="required" class="text-destructive">*</span>
        </Label>
        <Input
            :id="id"
            :value="modelValue"
            :type="type"
            :placeholder="placeholder"
            :step="step"
            :class="{ 'border-destructive': error }"
            @input="updateValue"
        />
        <InputError :message="error" />
    </div>
</template>