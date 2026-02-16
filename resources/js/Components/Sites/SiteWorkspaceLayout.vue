<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    site: {
        type: Object,
        required: true,
    },
    workspace: {
        type: Object,
        required: true,
    },
});

const tabs = [
    { key: 'overview', label: 'Overview', routeName: 'sites.workspace.overview' },
    { key: 'deployments', label: 'Deployments', routeName: 'sites.workspace.deployments' },
    { key: 'processes', label: 'Processes', routeName: 'sites.workspace.processes' },
    { key: 'commands', label: 'Commands', routeName: 'sites.workspace.commands' },
    { key: 'network', label: 'Network', routeName: 'sites.workspace.network' },
    { key: 'observe', label: 'Observe', routeName: 'sites.workspace.observe' },
    { key: 'domains', label: 'Domains', routeName: 'sites.workspace.domains' },
    { key: 'settings', label: 'Settings', routeName: 'sites.workspace.settings' },
];
</script>

<template>
    <Head :title="`${site.domain} · ${workspace.title}`" />

    <AppLayout>
        <div class="space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <Link :href="route('servers.sites.index', site.server.id)" class="btn btn-circle btn-ghost btn-sm" aria-label="Back to server sites">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                            </svg>
                        </Link>
                        <h1 class="text-2xl font-bold tracking-tight text-base-content">
                            {{ site.domain }}
                        </h1>
                    </div>
                    <p class="text-sm text-base-content/70">
                        {{ workspace.description }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="badge badge-outline">Server: {{ site.server.name }}</span>
                    <span class="badge badge-outline">IP: {{ site.server.ip_address }}</span>
                    <span class="badge badge-outline">Site ID: {{ site.id }}</span>
                </div>
            </div>

            <div role="tablist" aria-label="Site workspace tabs" class="tabs tabs-boxed w-full gap-1 overflow-x-auto bg-base-100 p-2">
                <Link
                    v-for="tab in tabs"
                    :key="tab.key"
                    role="tab"
                    :href="route(tab.routeName, site.id)"
                    :class="[
                        'tab min-w-max whitespace-nowrap transition',
                        workspace.activeTab === tab.key ? 'tab-active' : '',
                    ]"
                >
                    {{ tab.label }}
                </Link>
            </div>

            <slot />
        </div>
    </AppLayout>
</template>
