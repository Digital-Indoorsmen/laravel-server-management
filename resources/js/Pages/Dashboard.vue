<script setup>
import { computed } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import ServerStatus from "@/Components/UI/ServerStatus.vue";
import SELinuxAlert from "@/Components/UI/SELinuxAlert.vue";
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

const props = defineProps({
    servers: {
        type: Array,
        default: () => [],
    },
    systemStats: {
        type: Array,
        default: () => [],
    },
    services: {
        type: Array,
        default: () => [],
    },
    security: {
        type: Object,
        default: () => ({
            selinux_mode: "Unknown",
            firewall_active: false,
            firewall_services: [],
        }),
    },
    uptime: {
        type: String,
        default: "unknown",
    },
});

const pendingServers = computed(() => {
    return props.servers.filter((s) => !s.setup_completed_at);
});

const activeServers = computed(() => {
    return props.servers.filter((s) => !!s.setup_completed_at);
});

const systemStatsWithMeta = computed(() => {
    return props.systemStats.map((stat) => {
        const metadata = {
            "CPU Load": {
                icon: CpuChipIcon,
                color: "text-blue-500",
            },
            "RAM Usage": {
                icon: ViewColumnsIcon,
                color: "text-green-500",
            },
            "Disk Space": {
                icon: CircleStackIcon,
                color: "text-purple-500",
            },
            "Swap Usage": {
                icon: BoltIcon,
                color: "text-orange-500",
            },
        }[stat.name] ?? {
            icon: ServerIcon,
            color: "text-base-content",
        };

        return {
            ...stat,
            icon: metadata.icon,
            color: metadata.color,
        };
    });
});

const servicesWithIcons = computed(() => {
    return props.services.map((service) => {
        const icon = {
            "Nginx Web Server": GlobeAltIcon,
            "Caddy Web Server": GlobeAltIcon,
            "PHP-FPM": ServerIcon,
            Firewalld: ShieldCheckIcon,
            Supervisor: ViewColumnsIcon,
        }[service.name] ?? ServerIcon;

        return {
            ...service,
            icon,
            serviceKey: service.key ?? service.name.toLowerCase().replace(/\s+/g, "-"),
        };
    });
});

const firewallServices = computed(() => {
    return props.security.firewall_services?.length
        ? props.security.firewall_services.join(", ")
        : "none";
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

const updateWebServer = (id, webServer) => {
    useForm({
        web_server: webServer,
    }).patch(route("servers.web-server.update", { server: id }), {
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
                        Monitoring real host resources and service health.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span
                        class="text-xs font-mono opacity-50 bg-base-300 px-2 py-1 rounded"
                        >UPTIME: {{ uptime }}</span
                    >
                    <button
                        class="btn btn-sm btn-ghost border-base-300"
                        id="refresh-probes-button"
                        @click="() => window.location.reload()"
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
                                <span class="badge badge-sm badge-outline uppercase">
                                    {{ server.web_server || "nginx" }}
                                </span>
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

                            <div class="flex flex-col gap-2">
                                <div class="form-control">
                                    <label class="label py-0">
                                        <span class="label-text text-xs">Web Server</span>
                                    </label>
                                    <select
                                        class="select select-xs select-bordered"
                                        :value="server.web_server || 'nginx'"
                                        @change="(event) => updateWebServer(server.id, event.target.value)"
                                    >
                                        <option value="nginx">Nginx</option>
                                        <option value="caddy">Caddy</option>
                                    </select>
                                </div>

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
                    v-for="stat in systemStatsWithMeta"
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
                                    Number(stat.value) > 80
                                        ? 'progress-error'
                                        : Number(stat.value) > 60
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
                            v-for="service in servicesWithIcons"
                            :key="service.name"
                            :service-key="service.serviceKey"
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
                            v-if="security.selinux_mode !== 'Enforcing'"
                            :message="`SELinux mode is ${security.selinux_mode}.`"
                            context="Expected mode is Enforcing for hardened production hosts."
                        />

                        <div class="card border p-4" :class="{
                            'bg-success/10 border-success/20': security.firewall_active,
                            'bg-error/10 border-error/20': !security.firewall_active,
                        }">
                            <div class="flex items-center gap-2" :class="{
                                'text-success': security.firewall_active,
                                'text-error': !security.firewall_active,
                            }">
                                <ShieldCheckIcon class="h-5 w-5" />
                                <span class="font-bold text-sm">Firewall Status</span>
                            </div>
                            <p class="text-xs mt-1 opacity-80">
                                Firewalld is
                                {{
                                    security.firewall_active
                                        ? "active"
                                        : "not active"
                                }}.
                                Services: {{ firewallServices }}.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
