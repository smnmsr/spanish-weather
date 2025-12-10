<script setup lang="ts">
import {
    HoverCard,
    HoverCardContent,
    HoverCardTrigger,
} from '@/components/ui/hover-card';

import githubIconRaw from '../../icons/github.svg?raw';

const githubIcon = githubIconRaw.replace(
    '<path d="',
    '<path fill="currentColor" d="',
);
</script>

<template>
    <div class="fixed top-6 right-8 z-50">
        <HoverCard>
            <HoverCardTrigger as-child>
                <a
                    href="https://github.com/smnmsr/spanish-weather"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="Projekt auf GitHub ansehen"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-primary/30 bg-primary text-primary-foreground shadow-lg transition-colors hover:bg-primary/90"
                >
                    <span
                        class="h-6 w-6 text-primary-foreground"
                        aria-hidden="true"
                        v-html="githubIcon"
                    />
                </a>
            </HoverCardTrigger>
            <HoverCardContent
                class="w-72 text-left"
                :side-offset="8"
                side="bottom"
                align="end"
            >
                <div class="flex items-center gap-3">
                    <div>
                        <div class="font-semibold">Spanish Weather</div>
                        <div
                            class="mb-2 text-sm text-slate-600 dark:text-slate-400"
                        >
                            Open Source Projekt zur Visualisierung und Analyse
                            von Wetterdaten aus Spanien. Schau dir den Code auf
                            GitHub an!
                        </div>
                        <a
                            href="https://github.com/smnmsr/spanish-weather"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-sm font-medium text-primary hover:underline"
                        >
                            Zum GitHub-Projekt
                        </a>
                    </div>
                </div>
            </HoverCardContent>
        </HoverCard>
    </div>
</template>
