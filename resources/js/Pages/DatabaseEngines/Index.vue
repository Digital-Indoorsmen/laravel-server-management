<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import {
    CircleStackIcon,
    CommandLineIcon,
    PlayIcon,
    CheckCircleIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
    server: Object,
    installations: Array,
    availableEngines: Array,
    installedEngines: Object,
});

const form = useForm({
    type: "",
});

const install = (type) => {
    if (confirm(`Are you sure you want to install ${type}?`)) {
        form.type = type;
        form.post(route("servers.database-engines.store", props.server.id));
    }
};

const getStatusBadgeClass = (status) => {
    return {
        "badge-success": status === "active",
        "badge-warning": status === "queued" || status === "installing",
        "badge-error": status === "error",
    };
};
</script>

<template>
    <Head title="Database Engines" />

    <AppLayout>
        <div class="space-y-6">
            <div>
                <h1
                    class="text-2xl font-bold text-base-content flex items-center gap-2"
                >
                    <CircleStackIcon class="h-8 w-8 text-primary" />
                    Database Engines
                </h1>
                <p class="text-base-content/60">
                    Manage database software on {{ server.name }}.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div
                    v-for="engine in availableEngines"
                    :key="engine.id"
                    class="card bg-base-100 border border-base-300 shadow-sm"
                >
                    <div class="card-body">
                        <div class="flex items-center justify-between">
                            <h2 class="card-title">{{ engine.name }}</h2>
                            <span
                                v-if="installedEngines[engine.id]"
                                class="badge badge-success gap-1"
                            >
                                <CheckCircleIcon class="h-4 w-4" />
                                Installed
                            </span>
                            <span v-else class="badge badge-soft"
                                >Not Installed</span
                            >
                        </div>

                        <p class="text-sm opacity-70 py-2">
                            Manage and provision {{ engine.name }} databases on
                            this server.
                        </p>

                        <div class="card-actions justify-end mt-4">
                            <button
                                v-if="!installedEngines[engine.id]"
                                @click="install(engine.id)"
                                class="btn btn-primary btn-sm"
                                :disabled="form.processing"
                            >
                                <PlayIcon class="h-4 w-4" />
                                Install {{ engine.name }}
                            </button>
                            <div v-else class="flex flex-col items-end gap-1">
                                <span class="text-xs opacity-50"
                                    >Installed on
                                    {{
                                        installedEngines[engine.id].installed_at
                                    }}</span
                                >
                                <button class="btn btn-ghost btn-xs" disabled>
                                    Re-install
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <h2 class="text-xl font-bold flex items-center gap-2">
                    <CommandLineIcon class="h-6 w-6" />
                    Installation History
                </h2>

                <div class="card bg-base-100 border border-base-300 shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="table table-zebra">
                            <thead>
                                <tr>
                                    <th>Engine</th>
                                    <th>Status</th>
                                    <th>Started At</th>
                                    <th>Finished At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="install in installations"
                                    :key="install.id"
                                >
                                    <td class="font-medium uppercase">
                                        {{ install.type }}
                                    </td>
                                    <td>
                                        <span
                                            class="badge badge-sm"
                                            :class="
                                                getStatusBadgeClass(
                                                    install.status,
                                                )
                                            "
                                        >
                                            {{ install.status }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ install.started_at || "Queued" }}
                                    </td>
                                    <td>{{ install.finished_at || "-" }}</td>
                                    <td>
                                        <Link
                                            :href="
                                                route(
                                                    'database-engine-installations.show',
                                                    install.id,
                                                )
                                            "
                                            class="btn btn-xs btn-ghost"
                                        >
                                            Logs
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="installations.length === 0">
                                    <td
                                        colspan="5"
                                        class="text-center py-8 opacity-50 italic"
                                    >
                                        No installation history found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
