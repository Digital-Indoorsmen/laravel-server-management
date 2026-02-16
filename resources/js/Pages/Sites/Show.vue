<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { GlobeAltIcon } from '@heroicons/vue/24/outline';

defineProps({
    site: Object,
});
</script>

<template>
    <Head :title="site.domain" />

    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center gap-4">
                <Link :href="route('servers.sites.index', site.server.id)" class="btn btn-circle btn-ghost btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-base-content flex items-center gap-2">
                        <GlobeAltIcon class="h-8 w-8 text-primary" />
                        {{ site.domain }}
                    </h1>
                    <p class="text-base-content/60">
                        Hosted on {{ site.server.name }}.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Details Card -->
                <div class="card bg-base-100 border border-base-300 shadow-sm">
                    <div class="card-body">
                        <h2 class="card-title text-sm uppercase tracking-wider opacity-60">Site Details</h2>
                        <div class="space-y-4 mt-2">
                            <div>
                                <span class="block text-xs font-bold uppercase opacity-50">Status</span>
                                <span class="badge" :class="{
                                    'badge-success': site.status === 'active',
                                    'badge-warning': site.status === 'creating',
                                    'badge-error': site.status === 'error'
                                }">{{ site.status }}</span>
                            </div>
                            <div>
                                <span class="block text-xs font-bold uppercase opacity-50">Document Root</span>
                                <span class="font-mono text-sm bg-base-200 px-2 py-1 rounded">{{ site.document_root }}</span>
                            </div>
                            <div>
                                <span class="block text-xs font-bold uppercase opacity-50">System User</span>
                                <span class="font-mono text-sm">{{ site.system_user }}</span>
                            </div>
                            <div>
                                <span class="block text-xs font-bold uppercase opacity-50">PHP Version</span>
                                <span class="font-mono text-sm">{{ site.php_version }}</span>
                            </div>
                            <div>
                                <span class="block text-xs font-bold uppercase opacity-50">App Type</span>
                                <span class="capitalize">{{ site.app_type }}</span>
                            </div>
                            <div>
                                <span class="block text-xs font-bold uppercase opacity-50">Web Server</span>
                                <span class="uppercase">{{ site.web_server || site.server.web_server || 'nginx' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions / Config (Placeholder for future tasks) -->
                <div class="space-y-6">
                    <div class="card bg-base-100 border border-base-300 shadow-sm">
                        <div class="card-body">
                            <h2 class="card-title text-sm uppercase tracking-wider opacity-60">Environment</h2>
                            <p class="text-sm opacity-70">Manage environment variables (.env).</p>
                            <div class="card-actions justify-end">
                                <Link :href="route('sites.workspace.overview', site.id)" class="btn btn-sm btn-primary">Open Workspace</Link>
                                <Link :href="route('sites.env', site.id)" class="btn btn-sm btn-outline">Edit .env</Link>
                            </div>
                        </div>
                    </div>
                     <div class="card bg-base-100 border border-base-300 shadow-sm">
                        <div class="card-body">
                            <h2 class="card-title text-sm uppercase tracking-wider opacity-60">Danger Zone</h2>
                            <div class="card-actions">
                                <Link
                                    :href="route('sites.destroy', site.id)"
                                    method="delete"
                                    as="button"
                                    class="btn btn-sm btn-error btn-outline w-full"
                                    :onBefore="() => confirm('Are you sure you want to delete this site? This action cannot be undone.')"
                                >
                                    Delete Site
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
