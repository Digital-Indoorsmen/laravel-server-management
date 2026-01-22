<script setup>
import { ShieldExclamationIcon } from "@heroicons/vue/24/outline";

defineProps({
    message: String,
    context: String,
});
</script>

<template>
    <div class="alert alert-error shadow-lg border-none bg-error/10 text-error">
        <ShieldExclamationIcon class="h-6 w-6 shrink-0" />
        <div>
            <h3 class="font-bold">SELinux Violation Detected</h3>
            <div class="text-xs">{{ message }}</div>
            <div
                v-if="context"
                class="mt-1 font-mono text-[10px] opacity-70 p-1 bg-error/5 rounded"
            >
                {{ context }}
            </div>
        </div>
        <div class="flex-none">
            <button class="btn btn-sm btn-ghost">Details</button>
        </div>
    </div>
</template>
