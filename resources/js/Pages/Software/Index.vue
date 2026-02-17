<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import {
    CommandLineIcon,
    PlayIcon,
    ArrowUpCircleIcon,
    CheckCircleIcon,
    CpuChipIcon,
} from "@heroicons/vue/24/outline";

import { onMounted, ref } from "vue";
import { useConfirmation } from "@/Stores/useConfirmation";

const props = defineProps({
    server: Object,
    installations: Array,
    availableSoftware: Array,
    installedSoftware: Object,
});

const form = useForm({
    type: "",
    version: "",
    action: "install",
});

const selectedVersions = ref({});
const confirmation = useConfirmation();

onMounted(() => {
    props.availableSoftware.forEach((item) => {
        selectedVersions.value[item.id] =
            item.versions[item.versions.length - 1];
    });
});

const install = async (type) => {
    const version = selectedVersions.value[type];
    const item = props.availableSoftware.find((s) => s.id === type);
    const confirmed = await confirmation.ask({
        title: `Install ${item.name}`,
        message: `Are you sure you want to install ${item.name} v${version}?`,
        type: "primary",
    });

    if (confirmed) {
        form.type = type;
        form.version = version;
        form.action = "install";
        form.post(route("servers.software.store", props.server.id));
    }
};

const upgrade = async (type) => {
    const item = props.availableSoftware.find((s) => s.id === type);
    const confirmed = await confirmation.ask({
        title: `Upgrade ${item.name}`,
        message: `Are you sure you want to upgrade ${item.name}? This will run system updates and restart the service.`,
        type: "warning",
    });

    if (confirmed) {
        form.type = type;
        form.action = "upgrade";
        form.post(route("servers.software.store", props.server.id));
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
    <Head title="Server Software" />

    <AppLayout>
        <div class="space-y-6">
            <div>
                <h1
                    class="text-2xl font-bold text-base-content flex items-center gap-2"
                >
                    <CpuChipIcon class="h-8 w-8 text-primary" />
                    Server Software
                </h1>
                <p class="text-base-content/60">
                    Manage system software and runtimes on {{ server.name }}.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div
                    v-for="item in availableSoftware"
                    :key="item.id"
                    class="card bg-base-100 border border-base-300 shadow-sm"
                >
                    <div class="card-body">
                        <div class="flex items-center justify-between">
                            <h2 class="card-title">{{ item.name }}</h2>
                            <div class="flex gap-1 flex-wrap justify-end">
                                <template
                                    v-if="item.installed_versions.length > 0"
                                >
                                    <span
                                        v-for="v in item.installed_versions"
                                        :key="v"
                                        class="badge badge-success gap-1"
                                    >
                                        <CheckCircleIcon class="h-3 w-3" />
                                        v{{ v }}
                                    </span>
                                </template>
                                <span v-else class="badge badge-soft"
                                    >Not Installed</span
                                >
                            </div>
                        </div>

                        <div class="form-control w-full max-w-xs mt-2">
                            <label class="label py-1">
                                <span class="label-text-alt"
                                    >Version to Install</span
                                >
                            </label>
                            <select
                                v-model="selectedVersions[item.id]"
                                class="select select-bordered select-sm w-full"
                            >
                                <option
                                    v-for="v in item.versions"
                                    :key="v"
                                    :value="v"
                                >
                                    {{ item.name }} {{ v }}
                                    <template v-if="item.versionNotes && item.versionNotes[v]">
                                        - {{ item.versionNotes[v] }}
                                    </template>
                                </option>
                            </select>
                        </div>

                        <p class="text-sm opacity-70 py-2">
                            <template v-if="item.multiVersion && item.installed_versions.length > 0">
                                Multiple versions can be installed simultaneously. Sites can use different versions.
                            </template>
                            <template v-else>
                                Manage and provision {{ item.name }} on this server.
                            </template>
                        </p>

                        <div class="card-actions justify-end mt-4">
                            <button
                                @click="install(item.id)"
                                class="btn btn-primary btn-sm"
                                :disabled="form.processing"
                            >
                                <PlayIcon class="h-4 w-4" />
                                {{ item.multiVersion && item.installed_versions.length > 0 
                                    ? 'Install Additional Version' 
                                    : 'Install' }}
                            </button>

                            <button
                                v-if="item.upgradeAvailable"
                                @click="upgrade(item.id)"
                                class="btn btn-outline btn-xs gap-1"
                                :disabled="form.processing"
                            >
                                <ArrowUpCircleIcon class="h-3.5 w-3.5" />
                                Upgrade
                            </button>
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
                                    <th>Software</th>
                                    <th>Version</th>
                                    <th>Action</th>
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
                                    <td class="font-mono text-xs opacity-70">
                                        {{
                                            install.version
                                                ? `v${install.version}`
                                                : "-"
                                        }}
                                    </td>
                                    <td
                                        class="capitalize text-xs font-mono opacity-70"
                                    >
                                        {{ install.action }}
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
                                                    'software-installations.show',
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
                                        colspan="7"
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
