<script setup>
defineProps({
    status: {
        type: String,
        required: true,
        validator: (value) =>
            ["online", "offline", "error", "maintenance"].includes(value),
    },
    label: String,
});

const statusMap = {
    online: { color: "success", text: "Online" },
    offline: { color: "neutral", text: "Offline" },
    error: { color: "error", text: "Error" },
    maintenance: { color: "warning", text: "Maintenance" },
};
</script>

<template>
    <div class="flex items-center gap-2">
        <span class="relative flex h-3 w-3">
            <span
                class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                :class="`bg-${statusMap[status].color}`"
                v-if="status === 'online'"
            ></span>
            <span
                class="relative inline-flex rounded-full h-3 w-3"
                :class="`bg-${statusMap[status].color}`"
            ></span>
        </span>
        <span class="text-sm font-medium">
            {{ label || statusMap[status].text }}
        </span>
    </div>
</template>
