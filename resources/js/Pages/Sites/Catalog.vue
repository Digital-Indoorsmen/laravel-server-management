<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { GlobeAltIcon } from "@heroicons/vue/24/outline";

defineProps({
    sites: {
        type: Array,
        default: () => [],
    },
    servers: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Sites" />

    <AppLayout>
        <div class="space-y-6">
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-base-content flex items-center gap-2"
                >
                    <GlobeAltIcon class="h-8 w-8 text-primary" />
                    Sites
                </h1>
                <p class="text-base-content/60">
                    All provisioned sites across your managed servers.
                </p>
            </div>

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Domain</th>
                                <th>Server</th>
                                <th>Web Server</th>
                                <th>PHP</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="site in sites"
                                :key="site.id"
                                class="hover"
                            >
                                <td>
                                    <div class="font-bold">
                                        {{ site.domain }}
                                    </div>
                                    <div class="text-xs opacity-60">
                                        {{ site.document_root }}
                                    </div>
                                </td>
                                <td>
                                    <div class="font-semibold">
                                        {{ site.server?.name || "Unknown" }}
                                    </div>
                                    <div class="text-xs opacity-60">
                                        {{ site.server?.ip_address }}
                                    </div>
                                </td>
                                <td class="uppercase">
                                    {{
                                        site.web_server ||
                                        site.server?.web_server ||
                                        "nginx"
                                    }}
                                </td>
                                <td>{{ site.php_version }}</td>
                                <td>
                                    <span
                                        class="badge badge-sm"
                                        :class="{
                                            'badge-success':
                                                site.status === 'active',
                                            'badge-warning':
                                                site.status === 'creating',
                                            'badge-error':
                                                site.status === 'error',
                                        }"
                                    >
                                        {{ site.status }}
                                    </span>
                                </td>
                                <td class="flex gap-2">
                                    <Link
                                        :href="route('sites.show', site.id)"
                                        class="btn btn-xs btn-ghost"
                                    >
                                        Details
                                    </Link>
                                    <Link
                                        :href="
                                            route(
                                                'servers.sites.index',
                                                site.server.id,
                                            )
                                        "
                                        class="btn btn-xs btn-outline"
                                    >
                                        Server Sites
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="sites.length === 0">
                                <td
                                    colspan="6"
                                    class="text-center py-8 text-base-content/50"
                                >
                                    No sites found yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-3" v-if="servers.length > 0">
                <h2 class="text-lg font-bold">Create by server</h2>
                <div class="flex flex-wrap gap-2">
                    <Link
                        v-for="server in servers"
                        :key="server.id"
                        :href="route('servers.sites.create', server.id)"
                        class="btn btn-sm btn-outline"
                    >
                        New site on {{ server.name }}
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
