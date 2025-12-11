<script setup lang="ts">
interface LegendItem {
    label: string;
    color: string;
    shape?: 'circle' | 'square';
    opacity?: number;
}

interface Props {
    items: LegendItem[];
}

defineProps<Props>();
</script>

<template>
    <div class="mt-4 flex flex-wrap justify-center gap-4 text-sm">
        <div
            v-for="(item, index) in items"
            :key="index"
            class="flex items-center gap-2"
        >
            <div
                :class="[
                    'h-3 w-3',
                    item.shape === 'square' ? 'rounded-sm' : 'rounded-full',
                ]"
                :style="{
                    backgroundColor: item.color,
                    opacity: item.opacity ?? 1,
                }"
            ></div>
            <span>{{ item.label }}</span>
        </div>
    </div>
</template>
