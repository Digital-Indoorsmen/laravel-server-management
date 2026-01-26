<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { GlobeAltIcon, PlusIcon } from '@heroicons/vue/24/outline';

defineProps({
    server: Object,
    sites: Array,
});
</script>

<template>
    <Head title="Sites" />

    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-base-content flex items-center gap-2">
                        <GlobeAltIcon class="h-8 w-8 text-primary" />
                        Sites
                    </h1>
                    <p class="text-base-content/60">
                        Manage websites hosted on {{ server.name }} ({{ server.ip_address }}).
                    </p>
                </div>
                <Link
                    :href="route('servers.sites.create', server.id)"
                    class="btn btn-primary"
                >
                    <PlusIcon class="h-5 w-5" />
                    New Site
                </Link>
            </div>

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Domain</th>
                                <th>User</th>
                                <th>App Type</th>
                                <th>PHP</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="site in sites" :key="site.id" class="hover">
                                <td>
                                    <div class="font-bold">{{ site.domain }}</div>
                                    <div class="text-xs opacity-50">{{ site.document_root }}</div>
                                </td>
                                <td>{{ site.system_user }}</td>
                                <td class="capitalize">{{ site.app_type }}</td>
                                <td>{{ site.php_version }}</td>
                                <td>
                                    <span class="badge badge-sm" :class="{
                                        'badge-success': site.status === 'active',
                                        'badge-warning': site.status === 'creating',
                                        'badge-error': site.status === 'error'
                                    }">
                                        {{ site.status }}
                                    </span>
                                </td>
                                <td>
                                    <Link :href="route('sites.show', site.id)" class="btn btn-xs btn-ghost">
                                        Details
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="sites.length === 0">
                                <td colspan="6" class="text-center py-8 text-base-content/50">
                                    No sites found. Create one to get started.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
