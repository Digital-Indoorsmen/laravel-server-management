<script setup>
import { IconBellOff } from "@tabler/icons-vue";

const props = defineProps({
    site: Object,
});
</script>

<template>
    <section class="card border border-base-300 bg-base-100 shadow-sm">
        <div class="card-body items-center text-center py-16 gap-4">
            <div class="bg-base-200 p-4 rounded-full">
                <IconBellOff class="h-12 w-12 text-base-content/20" />
            </div>
            <div>
                <h3 class="text-xl font-medium">Site Notifications</h3>
                <p class="text-sm text-base-content/60 max-w-sm mx-auto">
                    Configure Slack, Discord, or Email notifications for deployment events and health check failures.
                </p>
            </div>
            <p class="text-xs text-base-content/40 uppercase tracking-widest font-bold mt-4">Coming Soon</p>
        </div>
    </section>
</template>
