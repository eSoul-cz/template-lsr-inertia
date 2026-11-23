<script setup lang="ts">
import { AppButton } from '@/design-system';
import { Head } from '@inertiajs/vue3';

// The server also sends the layout props (errors, notices, flashMessages); they are not rendered here.
defineOptions({ inheritAttrs: false });

defineProps<{
    appName: string;
}>();
</script>

<template>
    <Head title="Home" />
    <main class="mx-auto flex min-h-dvh max-w-2xl flex-col items-start justify-center gap-4 p-8">
        <h1 class="text-3xl font-semibold tracking-tight">{{ appName || 'LSR app' }}</h1>
        <p class="text-muted-foreground">Latte, LSR, RoadRunner and Inertia (Vue 3) are running. Edit assets/js/pages/Home.vue to get started.</p>
        <AppButton as="a" href="https://inertiajs.com" target="_blank" rel="noreferrer">Inertia docs</AppButton>
    </main>
</template>
