<script setup lang="ts">
// Simple full-screen layout with no sidebar, breadcrumbs, or navigation
</script>

<template>
    <div
        class="h-screen w-full bg-gradient-to-b from-slate-50 to-white dark:from-slate-950 dark:to-slate-900"
    >
        <slot />
    </div>
</template>
