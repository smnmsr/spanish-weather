<script setup lang="ts">
import ProjectInfoHoverCard from '@/components/ProjectInfoHoverCard.vue';
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
    <ProjectInfoHoverCard />
    <section class="flex min-h-screen flex-col items-center justify-center p-8">
        <div class="max-w-2xl text-center">
            <h1 class="mb-6 text-5xl font-bold">¡Hola Meteo!</h1>
            <p class="mb-8 text-lg text-slate-600 dark:text-slate-400">
                Entdecke Spaniens Wettervielfalt in Sekunden: Finde
                Wetterstationen auf der Karte, sichere genau die Variablen, die
                du brauchst, und starte direkt in die Auswertung.
            </p>
            <Button size="lg" @click="emit('go-to-map')">
                Auf zur interaktiven Karte
            </Button>
        </div>
    </section>
</template>
