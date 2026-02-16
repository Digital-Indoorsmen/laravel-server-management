<script setup>
import {
    CheckCircleIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
} from "@heroicons/vue/24/solid";

defineProps({
    name: String,
    status: {
        type: String, // 'running', 'stopped', 'failed', 'starting'
        default: "stopped",
    },
    version: String,
});
</script>

<template>
    <div
        class="flex items-center justify-between p-3 rounded-xl border border-base-300 bg-base-100 hover:border-primary/30 transition-all group"
    >
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-base-200">
                <CheckCircleIcon
                    v-if="status === 'running'"
                    class="h-5 w-5 text-success"
                />
                <XCircleIcon
                    v-else-if="status === 'stopped'"
                    class="h-5 w-5 text-base-content/30"
                />
                <ExclamationTriangleIcon
                    v-else-if="status === 'failed'"
                    class="h-5 w-5 text-error"
                />
                <InformationCircleIcon
                    v-else
                    class="h-5 w-5 text-warning animate-pulse"
                />
            </div>
            <div>
                <div class="font-bold text-sm">{{ name }}</div>
                <div
                    class="text-[10px] opacity-50 uppercase tracking-wider font-semibold"
                >
                    {{ version || "Installed" }}
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span
                class="badge badge-sm font-bold"
                :class="{
                    'badge-success bg-success/20 text-success border-success/20':
                        status === 'running',
                    'badge-ghost opacity-50': status === 'stopped',
                    'badge-error bg-error/20 text-error border-error/20':
                        status === 'failed',
                    'badge-warning bg-warning/20 text-warning border-warning/20':
                        status === 'starting',
                }"
            >
                {{ status }}
            </span>
            <span class="text-[10px] uppercase tracking-wider opacity-40">
                monitor
            </span>
        </div>
    </div>
</template>
