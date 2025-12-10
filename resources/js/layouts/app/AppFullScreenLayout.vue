<script setup lang="ts">
// Simple full-screen layout with no sidebar, breadcrumbs, or navigation
</script>

<template>
    <div
        class="h-screen w-full bg-gradient-to-br from-orange-50 via-amber-100 to-yellow-100 dark:from-blue-950/80 dark:via-cyan-950/70 dark:to-blue-900/60"
    >
        <slot />
    </div>
</template>
