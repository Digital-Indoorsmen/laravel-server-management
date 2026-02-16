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
                <h2 class="card-title">Site Settings Pending</h2>
                <p class="text-sm text-base-content/70">
                    Framework, runtime defaults, and safe reconfiguration controls will live here.
                </p>
                <div class="card-actions justify-end gap-2">
                    <Link :href="route('sites.env', site.id)" class="btn btn-sm btn-outline">Edit .env</Link>
                    <Link :href="route('sites.workspace.overview', site.id)" class="btn btn-sm btn-primary">Back To Overview</Link>
                </div>
            </div>
        </div>
    </SiteWorkspaceLayout>
</template>
