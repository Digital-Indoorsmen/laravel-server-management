<script setup>
import { computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import ServerStatus from "@/Components/UI/ServerStatus.vue";
import SELinuxAlert from "@/Components/UI/SELinuxAlert.vue";
import ResourceUsage from "@/Components/UI/ResourceUsage.vue";
import ServiceItem from "@/Components/UI/ServiceItem.vue";
import {
    ServerIcon,
    ShieldCheckIcon,
    CpuChipIcon,
    ViewColumnsIcon,
    CircleStackIcon,
    BoltIcon,
    GlobeAltIcon,
    KeyIcon,
} from "@heroicons/vue/24/outline";

// Local system health metrics (semi-hardcoded for now as placeholders for real probes)
const systemStats = [
    {
        name: "CPU Load",
        value: "45",
        icon: CpuChipIcon,
        color: "text-blue-500",
        unit: "%",
    },
    {
        name: "RAM Usage",
        value: "62",
        icon: ViewColumnsIcon,
        color: "text-green-500",
        unit: "%",
    },
    {
        name: "Disk Space",
        value: "82",
        icon: CircleStackIcon,
        color: "text-purple-500",
        unit: "%",
    },
    {
        name: "Network",
        value: "1.2",
        icon: BoltIcon,
        color: "text-orange-500",
        unit: "Gbps",
    },
];

const services = [
    {
        name: "Nginx Web Server",
        status: "running",
        version: "1.24.0",
        icon: GlobeAltIcon,
    },
    {
        name: "PHP 8.4 FPM",
        status: "running",
        version: "8.4.17",
        icon: ServerIcon,
    },
    {
        name: "MariaDB Database",
        status: "running",
        version: "10.11.6",
        icon: CircleStackIcon,
    },
    {
        name: "Redis Cache",
        status: "stopped",
        version: "7.2.4",
        icon: BoltIcon,
    },
];

const props = defineProps({
    servers: Array,
});

const pendingServers = computed(() => {
    return props.servers.filter((s) => !s.setup_completed_at);
});

const activeServers = computed(() => {
    return props.servers.filter((s) => !!s.setup_completed_at);
});

const getSetupCommand = (token) => {
    const url = route("setup.script", { token }, true);
    return `curl -sSL ${url} | sudo bash`;
};

const testConnection = (id) => {
    useForm({}).post(route("servers.test", { server: id }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout>
        <div class="space-y-8">
            <!-- Page Header -->
            <div
                class="flex flex-col md:flex-row md:items-end justify-between gap-4"
            >
                <div>
                    <h1
                        class="text-2xl font-bold tracking-tight text-base-content flex items-center gap-2"
                    >
                        <ServerIcon class="h-8 w-8 text-primary" />
                        Local Server Health
                    </h1>
                    <p class="text-base-content/60">
                        Monitoring AlmaLinux system resources and primary
                        services.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span
                        class="text-xs font-mono opacity-50 bg-base-300 px-2 py-1 rounded"
                        >UPTIME: 14d 2h 15m</span
                    >
                    <button
                        class="btn btn-sm btn-ghost border-base-300"
                        id="refresh-probes-button"
                    >
                        Refresh Probes
                    </button>
                </div>
            </div>

            <!-- Active Servers Management -->
            <div v-if="activeServers.length > 0" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold">Active Servers</h2>
                </div>
                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
                >
                    <div
                        v-for="server in activeServers"
                        :key="server.id"
                        class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden"
                    >
                        <div class="p-4 space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="bg-primary/10 p-2 rounded-lg">
                                        <ServerIcon
                                            class="h-5 w-5 text-primary"
                                        />
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="font-bold truncate">
                                            {{ server.name }}
                                        </h3>
                                        <p class="text-xs opacity-60 font-mono">
                                            {{ server.ip_address }}
                                        </p>
                                    </div>
                                </div>
                                <ServerStatus :status="server.status" />
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <span class="badge badge-sm badge-outline">{{
                                    server.os_version || "Detecting..."
                                }}</span>
                                <span
                                    v-if="server.ssh_key"
                                    class="badge badge-sm badge-success badge-outline gap-1"
                                >
                                    <KeyIcon class="h-3 w-3" />
                                    {{ server.ssh_key.name }}
                                </span>
                                <span
                                    v-else
                                    class="badge badge-sm badge-error badge-outline gap-1"
                                >
                                    <KeyIcon class="h-3 w-3" />
                                    No Key
                                </span>
                            </div>

                            <div class="divider my-0"></div>

                            <div class="flex justify-end gap-2">
                                <button
                                    class="btn btn-xs btn-outline btn-primary"
                                    @click="testConnection(server.id)"
                                    :disabled="!server.ssh_key"
                                >
                                    Test Connection
                                </button>
                                <Link
                                    class="btn btn-xs btn-outline"
                                    :href="route('servers.sites.index', server.id)"
                                >
                                    Manage
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Servers / Provisioning Alerts -->
            <div
                v-if="pendingServers && pendingServers.length > 0"
                class="space-y-4"
            >
                <div
                    v-for="server in pendingServers"
                    :key="server.id"
                    class="card bg-warning/10 border border-warning/20 shadow-sm overflow-hidden"
                >
                    <div
                        class="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4"
                    >
                        <div>
                            <div class="flex items-center gap-2 text-warning">
                                <span class="relative flex h-2 w-2">
                                    <span
                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-warning opacity-75"
                                    ></span>
                                    <span
                                        class="relative inline-flex rounded-full h-2 w-2 bg-warning"
                                    ></span>
                                </span>
                                <h3 class="font-bold text-sm">
                                    Server Provisioning Required:
                                    {{ server.name }}
                                </h3>
                            </div>
                            <p class="text-xs mt-1 opacity-70">
                                Run the following command on your fresh
                                AlmaLinux/Rocky Linux server to begin
                                installation.
                            </p>
                        </div>
                        <div class="flex-1 max-w-2xl">
                            <div class="join w-full">
                                <input
                                    readonly
                                    :value="getSetupCommand(server.setup_token)"
                                    class="input input-sm input-bordered join-item w-full font-mono text-[10px] bg-base-200"
                                />
                                <button
                                    class="btn btn-sm btn-primary join-item"
                                    @click="
                                        (e) => {
                                            navigator.clipboard.writeText(
                                                getSetupCommand(
                                                    server.setup_token,
                                                ),
                                            );
                                            e.target.innerText = 'Copied!';
                                            setTimeout(
                                                () =>
                                                    (e.target.innerText =
                                                        'Copy'),
                                                2000,
                                            );
                                        }
                                    "
                                >
                                    Copy
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Stats Grid -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="stat in systemStats"
                    :key="stat.name"
                    class="stats shadow bg-base-100 border border-base-300"
                >
                    <div class="stat">
                        <div class="stat-figure" :class="stat.color">
                            <component :is="stat.icon" class="h-6 w-6" />
                        </div>
                        <div
                            class="stat-title text-xs font-bold uppercase tracking-wider"
                        >
                            {{ stat.name }}
                        </div>
                        <div
                            class="stat-value text-2xl flex items-baseline gap-1"
                        >
                            {{ stat.value }}
                            <span class="text-xs font-medium opacity-50">{{
                                stat.unit
                            }}</span>
                        </div>
                        <div class="stat-desc mt-2">
                            <progress
                                class="progress w-full h-1"
                                :class="
                                    parseFloat(stat.value) > 80
                                        ? 'progress-error'
                                        : parseFloat(stat.value) > 60
                                          ? 'progress-warning'
                                          : 'progress-success'
                                "
                                :value="stat.value"
                                max="100"
                            ></progress>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Services Column -->
                <div class="lg:col-span-2 space-y-4">
                    <h2 class="text-lg font-bold flex items-center gap-2">
                        <ViewColumnsIcon class="h-5 w-5 text-primary" />
                        System Services
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <ServiceItem
                            v-for="service in services"
                            :key="service.name"
                            v-bind="service"
                        />
                    </div>
                </div>

                <!-- Alerts + Security Column -->
                <div class="space-y-4">
                    <h2 class="text-lg font-bold flex items-center gap-2">
                        <ShieldCheckIcon class="h-5 w-5 text-primary" />
                        Security & SELinux
                    </h2>
                    <div class="space-y-3">
                        <SELinuxAlert
                            message="Nginx process attempted to connect to unauthorized port 8095."
                            context="avc: denied { name_connect } for pid=1234 comm='nginx' dest=8095 scontext=system_u:system_r:httpd_t:s0 tcontext=system_u:object_r:http_port_t:s0 tclass=tcp_socket"
                        />

                        <div
                            class="card bg-success/10 border border-success/20 p-4"
                        >
                            <div class="flex items-center gap-2 text-success">
                                <ShieldCheckIcon class="h-5 w-5" />
                                <span class="font-bold text-sm"
                                    >Firewall Status</span
                                >
                            </div>
                            <p class="text-xs mt-1 opacity-80">
                                Firewalld is active with 4 open ports in the
                                'public' zone.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
