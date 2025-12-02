<script setup lang="ts">
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

const emit = defineEmits<{
    (e: 'remove', id: string): void;
}>();
</script>

<template>
    <div
        class="rounded-lg border border-slate-200 bg-white p-6 shadow-lg dark:border-slate-800 dark:bg-slate-900"
    >
        <h3 class="mb-4 text-xl font-semibold">Ausgewählte Stationen</h3>

        <div
            v-if="stations.length === 0"
            class="text-center text-slate-500 dark:text-slate-400"
        >
            <p class="text-sm">Keine Stationen ausgewählt</p>
            <p class="mt-2 text-xs">
                Klicken Sie auf die Marker auf der Karte, um Stationen
                auszuwählen.
            </p>
        </div>

        <div v-else class="max-h-[60vh] space-y-2 overflow-y-auto">
            <div
                v-for="station in stations"
                :key="station.id ?? ''"
                class="group flex items-start justify-between rounded-md border border-slate-200 bg-slate-50 p-3 transition-colors hover:border-blue-300 hover:bg-blue-50 dark:border-slate-700 dark:bg-slate-800 dark:hover:border-blue-700 dark:hover:bg-slate-700"
            >
                <div class="flex-1">
                    <p class="font-medium text-slate-900 dark:text-slate-100">
                        {{ station.name }}
                    </p>
                    <p
                        v-if="station.provincia"
                        class="text-sm text-slate-600 dark:text-slate-400"
                    >
                        {{ station.provincia }}
                    </p>
                </div>
                <button
                    @click="emit('remove', station.id!)"
                    class="ml-2 rounded p-1 text-slate-400 transition-colors hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400"
                    title="Entfernen"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
