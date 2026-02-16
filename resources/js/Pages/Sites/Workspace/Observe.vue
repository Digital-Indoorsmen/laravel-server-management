<script setup>
import { Link } from '@inertiajs/vue3';
import SiteWorkspaceLayout from '@/Components/Sites/SiteWorkspaceLayout.vue';

defineProps({
    site: {
        type: Object,
        required: true,
    },
    workspace: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <SiteWorkspaceLayout :site="site" :workspace="workspace">
        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="card-body gap-4">
                <h2 class="card-title">Observability Pending</h2>
                <p class="text-sm text-base-content/70">
                    Heartbeats and site activity timeline will be surfaced here.
                </p>
                <div class="card-actions justify-end gap-2">
                    <Link :href="route('sites.workspace.deployments', site.id)" class="btn btn-sm btn-outline">See Deployments</Link>
                    <Link :href="route('sites.workspace.commands', site.id)" class="btn btn-sm btn-primary">See Commands</Link>
                </div>
            </div>
        </div>
    </SiteWorkspaceLayout>
</template>
