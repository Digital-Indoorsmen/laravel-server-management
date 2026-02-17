<script setup>
import { computed } from "vue";

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
    label: String,
});

const statusMap = {
    active: { color: "success", text: "Active" },
    online: { color: "success", text: "Online" },
    provisioning: { color: "info", text: "Provisioning" },
    creating: { color: "info", text: "Creating" },
    offline: { color: "neutral", text: "Offline" },
    error: { color: "error", text: "Error" },
    maintenance: { color: "warning", text: "Maintenance" },
};

const defaultStatus = { color: "neutral", text: "Unknown" };

const currentStatus = computed(() => {
    return statusMap[props.status] || defaultStatus;
});
</script>

<template>
    <div class="flex items-center gap-2">
        <span class="relative flex h-3 w-3">
            <span
                class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                :class="`bg-${currentStatus.color}`"
                v-if="status === 'online' || status === 'active'"
            ></span>
            <span
                class="relative inline-flex rounded-full h-3 w-3"
                :class="`bg-${currentStatus.color}`"
            ></span>
        </span>
        <span class="text-sm font-medium">
            {{ label || currentStatus.text }}
        </span>
    </div>
</template>
