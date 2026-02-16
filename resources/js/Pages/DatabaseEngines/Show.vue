<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import {
    CommandLineIcon,
    ChevronLeftIcon,
    ArrowPathIcon,
    CheckCircleIcon,
    XCircleIcon,
} from "@heroicons/vue/24/outline";
import { onMounted, onUnmounted, ref, watch } from "vue";

const props = defineProps({
    installation: Object,
});

const logContainer = ref(null);
let pollInterval = null;

const scrollToBottom = () => {
    if (logContainer.value) {
        logContainer.value.scrollTop = logContainer.value.scrollHeight;
    }
};

const startPolling = () => {
    if (["queued", "installing"].includes(props.installation.status)) {
        pollInterval = setInterval(() => {
            router.reload({
                only: ["installation"],
                onSuccess: () => scrollToBottom(),
            });
        }, 3000);
    }
};

const stopPolling = () => {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
};

onMounted(() => {
    startPolling();
    scrollToBottom();
});

onUnmounted(() => {
    stopPolling();
});

watch(
    () => props.installation.status,
    (newStatus) => {
        if (!["queued", "installing"].includes(newStatus)) {
            stopPolling();
        }
    },
);

const getStatusBadgeClass = (status) => {
    return {
        "badge-success": status === "active",
        "badge-warning": status === "queued" || status === "installing",
        "badge-error": status === "error",
    };
};
</script>

<template>
    <Head :title="`Installing ${installation.type}`" />

    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="
                            route(
                                'servers.database-engines.index',
                                installation.server_id,
                            )
                        "
                        class="btn btn-sm btn-ghost btn-circle"
                    >
                        <ChevronLeftIcon class="h-6 w-6" />
                    </Link>
                    <div>
                        <h1
                            class="text-2xl font-bold text-base-content flex items-center gap-2"
                        >
                            <CommandLineIcon class="h-8 w-8 text-primary" />
                            <span class="uppercase">{{
                                installation.type
                            }}</span>
                            Installation
                        </h1>
                        <p class="text-base-content/60">
                            {{ installation.server.name }} ({{
                                installation.server.ip_address
                            }})
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span
                        class="badge badge-lg"
                        :class="getStatusBadgeClass(installation.status)"
                    >
                        {{ installation.status }}
                    </span>
                    <button
                        v-if="
                            ['queued', 'installing'].includes(
                                installation.status,
                            )
                        "
                        class="btn btn-ghost btn-sm animate-spin"
                    >
                        <ArrowPathIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <div
                class="card bg-neutral text-neutral-content shadow-xl overflow-hidden"
            >
                <div
                    class="bg-base-300 px-4 py-2 flex items-center justify-between border-b border-base-content/10"
                >
                    <div class="flex items-center gap-2">
                        <div class="flex gap-1.5">
                            <div
                                class="w-2.5 h-2.5 rounded-full bg-error/50"
                            ></div>
                            <div
                                class="w-2.5 h-2.5 rounded-full bg-warning/50"
                            ></div>
                            <div
                                class="w-2.5 h-2.5 rounded-full bg-success/50"
                            ></div>
                        </div>
                        <span class="text-xs font-mono opacity-40 ml-2"
                            >installation.log</span
                        >
                    </div>
                </div>
                <div
                    ref="logContainer"
                    class="p-4 font-mono text-xs h-150 overflow-y-auto"
                >
                    <pre
                        v-if="installation.log"
                        class="whitespace-pre-wrap wrap-break-word leading-relaxed"
                        >{{ installation.log }}</pre
                    >
                    <div
                        v-else
                        class="flex flex-col items-center justify-center h-full opacity-20 italic"
                    >
                        <ArrowPathIcon class="h-10 w-10 mb-4 animate-spin" />
                        Waiting for output...
                    </div>
                </div>
            </div>

            <div
                v-if="installation.status === 'active'"
                class="alert alert-success"
            >
                <CheckCircleIcon class="h-6 w-6" />
                <span
                    >Engine installation completed successfully! Root password
                    has been secured.</span
                >
            </div>

            <div
                v-if="installation.status === 'error'"
                class="alert alert-error"
            >
                <XCircleIcon class="h-6 w-6" />
                <span
                    >Installation failed. Please review the logs above for
                    details.</span
                >
            </div>
        </div>
    </AppLayout>
</template>
