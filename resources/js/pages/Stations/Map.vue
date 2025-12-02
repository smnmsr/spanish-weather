<script setup lang="ts">
import StationsMap from '@/components/StationsMap.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { ref } from 'vue';

interface Station {
    id: string | null;
    name: string;
    lat: string | number;
    lon: string | number;
    provincia?: string | null;
}

interface Props {
    stations: Station[];
}

defineProps<Props>();

const breadcrumbs = ref<BreadcrumbItemType[]>([
    { title: 'Home', href: '/' },
    { title: 'Stations Map' },
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <h1 class="text-xl font-semibold">AEMET Stations Map</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                Initial map with all available stations. Functionality will
                improve over time.
            </p>
            <StationsMap :stations="stations" class="mt-4" />
        </div>
    </AppLayout>
</template>
