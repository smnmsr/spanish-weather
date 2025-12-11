<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { HelpCircle } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();

const open = ref(false);
</script>

<template>
    <Sheet v-model:open="open">
        <SheetTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="h-8 w-8 rounded-full"
                aria-label="Hilfe anzeigen"
            >
                <HelpCircle class="h-4 w-4" />
            </Button>
        </SheetTrigger>
        <SheetContent side="bottom" class="max-h-[85vh] p-6 sm:p-8">
            <SheetHeader class="mx-auto max-w-2xl">
                <SheetTitle>{{ title }}</SheetTitle>
                <SheetDescription v-if="description">
                    {{ description }}
                </SheetDescription>
            </SheetHeader>
            <div class="mx-auto mt-6 max-h-[60vh] max-w-2xl overflow-auto px-1">
                <slot />
            </div>
        </SheetContent>
    </Sheet>
</template>
