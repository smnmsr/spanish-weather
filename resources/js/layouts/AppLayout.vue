<script setup lang="ts">
// Full-screen layout - no sidebar, breadcrumbs, or navigation
import AppLayout from '@/layouts/app/AppFullScreenLayout.vue';

// If you need the sidebar layout in the future, uncomment the following:
// import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
// import type { BreadcrumbItemType } from '@/types';

// interface Props {
//     breadcrumbs?: BreadcrumbItemType[];
// }

// withDefaults(defineProps<Props>(), {
//     breadcrumbs: () => [],
// });
</script>

<template>
    <AppLayout>
        <slot />
    </AppLayout>
    <!-- If you need the sidebar layout in the future, use this instead: -->
    <!-- <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout> -->
</template>
