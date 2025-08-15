<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

import { useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    price: '',
    description: '',
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Create a Product',
        href: '/products/create',
    },
];

const handleSubmit = () => {
    form.post(route('products.store'), {
        onSuccess: () => {
            form.reset();
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
        },
    });
};
</script>

<template>
    <Head title="Create a Product" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <form @submit.prevent="handleSubmit" class="space-y-4">
                <div class="w-8/12 space-y-4">
                    <div class="space-y-2">
                        <Label for="name">Product Name</Label>
                        <Input v-model="form.name" type="text" id="name" name="name" required />
                        <div class="text-sm text-red-600" v-if="form.errors.name">{{ form.errors.name }}</div>
                    </div>
                    <div class="space-y-2">
                        <Label for="description">Product Description</Label>
                        <Input v-model="form.description" as="textarea" id="description" name="description" required />
                        <div class="text-sm text-red-600" v-if="form.errors.description">{{ form.errors.description }}</div>
                    </div>
                    <div class="space-y-2">
                        <Label for="price">Product Price</Label>
                        <Input v-model="form.price" type="number" id="price" name="price" required />
                        <div class="text-sm text-red-600" v-if="form.errors.price">{{ form.errors.price }}</div>
                    </div>
                    <div class="space-y-2">
                        <Button type="submit">Create Product</Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
