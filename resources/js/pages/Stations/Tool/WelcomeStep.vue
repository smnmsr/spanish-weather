<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs: BreadcrumbItemType[];
}

defineProps<Props>();

const emit = defineEmits<{
    (e: 'go-to-map'): void;
}>();
</script>

<template>
    <section
        class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-b from-slate-50 to-white p-8 dark:from-slate-950 dark:to-slate-900"
    >
        <div class="max-w-2xl text-center">
            <h1 class="mb-6 text-5xl font-bold">
                Willkommen zum Stations-Tool
            </h1>
            <p class="mb-8 text-lg text-slate-600 dark:text-slate-400">
                Dieses Tool hilft Ihnen, Wetterstationen aus dem AEMET-Netzwerk
                auszuwählen und relevante Variablen zu speichern. Wählen Sie die
                Stationen aus, die für Ihre Analyse wichtig sind.
            </p>
            <Button size="lg" @click="emit('go-to-map')">
                Weiter zur Karte
            </Button>
        </div>
    </section>
</template>
