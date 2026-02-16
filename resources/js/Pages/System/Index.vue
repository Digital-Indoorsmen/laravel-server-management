<script setup>
import { computed } from "vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import ServiceItem from "@/Components/UI/ServiceItem.vue";
import {
    BoltIcon,
    CircleStackIcon,
    CpuChipIcon,
    ServerIcon,
    ShieldCheckIcon,
    ViewColumnsIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
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
    canManageServices: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const controlForm = useForm({});

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

const firewallServices = computed(() => {
    return props.security.firewall_services?.length
        ? props.security.firewall_services.join(", ")
        : "none";
});

const servicesWithKeys = computed(() => {
    return props.services.map((service) => ({
        ...service,
        serviceKey: service.key ?? service.name.toLowerCase().replace(/\s+/g, "-"),
    }));
});

const runServiceAction = (serviceKey, action) => {
    controlForm.post(route("system.services.control", { service: serviceKey, action }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="System" />

    <AppLayout>
        <div class="space-y-6">
            <div v-if="page.props.flash?.success" class="alert alert-success">
                <span>{{ page.props.flash.success }}</span>
            </div>

            <div v-if="page.props.errors?.service_control" class="alert alert-error">
                <span>{{ page.props.errors.service_control }}</span>
            </div>

            <div class="flex items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-base-content flex items-center gap-2">
                        <ServerIcon class="h-8 w-8 text-primary" />
                        System
                    </h1>
                    <p class="text-base-content/60">
                        Live operating system and service status for this panel host.
                    </p>
                </div>
                <span class="text-xs font-mono opacity-50 bg-base-300 px-2 py-1 rounded">UPTIME: {{ uptime }}</span>
            </div>

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
                        <div class="stat-title text-xs font-bold uppercase tracking-wider">
                            {{ stat.name }}
                        </div>
                        <div class="stat-value text-2xl flex items-baseline gap-1">
                            {{ stat.value }}
                            <span class="text-xs font-medium opacity-50">{{ stat.unit }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex items-center justify-between gap-2">
                        <h2 class="text-lg font-bold flex items-center gap-2">
                        <ViewColumnsIcon class="h-5 w-5 text-primary" />
                        Services
                        </h2>
                        <span v-if="!canManageServices" class="text-xs opacity-60">
                            Read-only: service controls require sudo access.
                        </span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <ServiceItem
                            v-for="service in servicesWithKeys"
                            :key="service.name"
                            :service-key="service.serviceKey"
                            :controls-enabled="canManageServices"
                            @control="(action) => runServiceAction(service.serviceKey, action)"
                            v-bind="service"
                        />
                    </div>
                </div>

                <div class="space-y-4">
                    <h2 class="text-lg font-bold flex items-center gap-2">
                        <ShieldCheckIcon class="h-5 w-5 text-primary" />
                        Security
                    </h2>
                    <div class="card border p-4" :class="{
                        'bg-success/10 border-success/20': security.firewall_active,
                        'bg-error/10 border-error/20': !security.firewall_active,
                    }">
                        <p class="text-sm">
                            SELinux mode: <span class="font-bold">{{ security.selinux_mode }}</span>
                        </p>
                        <p class="text-sm mt-2">
                            Firewall: <span class="font-bold">{{ security.firewall_active ? "active" : "inactive" }}</span>
                        </p>
                        <p class="text-xs mt-2 opacity-80">
                            Allowed services: {{ firewallServices }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
