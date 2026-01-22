<script setup>
defineProps({
    label: String,
    value: {
        type: Number,
        required: true,
    },
    max: {
        type: Number,
        default: 100,
    },
    unit: {
        type: String,
        default: "%",
    },
});

const getProgressColor = (percent) => {
    if (percent < 70) return "progress-success";
    if (percent < 90) return "progress-warning";
    return "progress-error";
};
</script>

<template>
    <div class="flex flex-col gap-1 w-full">
        <div class="flex justify-between text-xs font-medium">
            <span>{{ label }}</span>
            <span>{{ value }}{{ unit }}</span>
        </div>
        <progress
            class="progress w-full h-2 rounded-full transition-all duration-500"
            :class="getProgressColor((value / max) * 100)"
            :value="value"
            :max="max"
        ></progress>
    </div>
</template>
