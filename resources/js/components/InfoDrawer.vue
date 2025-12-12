<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Drawer,
    DrawerContent,
    DrawerDescription,
    DrawerHeader,
    DrawerTitle,
} from '@/components/ui/drawer';
import { AlertTriangle } from 'lucide-vue-next';
import { computed, type Component } from 'vue';

interface Props {
    open?: boolean;
    title: string;
    description?: string;
    triggerLabel?: string;
    icon?: Component;
}

const props = withDefaults(defineProps<Props>(), {
    open: false,
    description: '',
    triggerLabel: 'Info',
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const isOpen = computed({
    get: () => props.open,
    set: (value: boolean) => emit('update:open', value),
});

const TriggerIcon = computed(() => props.icon ?? AlertTriangle);

const setOpen = (value: boolean) => {
    isOpen.value = value;
};
</script>

<template>
    <div class="flex items-center">
        <slot name="trigger" :open="isOpen" :set-open="setOpen">
            <Button
                variant="ghost"
                size="icon"
                class="h-9 w-9 rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-700"
                @click="setOpen(true)"
                :aria-label="triggerLabel"
            >
                <TriggerIcon class="h-5 w-5" />
            </Button>
        </slot>

        <Drawer v-model:open="isOpen">
            <DrawerContent>
                <div class="mx-auto w-full max-w-md px-6 py-6">
                    <DrawerHeader class="space-y-2 text-left">
                        <DrawerTitle class="text-lg font-semibold">
                            {{ title }}
                        </DrawerTitle>
                        <DrawerDescription v-if="description">
                            {{ description }}
                        </DrawerDescription>
                    </DrawerHeader>

                    <div class="space-y-4">
                        <slot />
                    </div>
                </div>
            </DrawerContent>
        </Drawer>
    </div>
</template>
