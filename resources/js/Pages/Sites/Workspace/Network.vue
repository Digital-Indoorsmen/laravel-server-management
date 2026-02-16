<script setup>
import { Link } from "@inertiajs/vue3";
import SiteWorkspaceLayout from "@/Components/Sites/SiteWorkspaceLayout.vue";

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
                <h2 class="card-title">Network Rules Pending</h2>
                <p class="text-sm text-base-content/70">
                    Redirects and site-level security controls will be
                    configured in this section.
                </p>
                <div class="card-actions justify-end gap-2">
                    <Link
                        :href="route('sites.workspace.domains', site.id)"
                        class="btn btn-sm btn-outline"
                        >Manage Domains</Link
                    >
                    <Link
                        :href="route('sites.workspace.settings', site.id)"
                        class="btn btn-sm btn-primary"
                        >Open Settings</Link
                    >
                </div>
            </div>
        </div>
    </SiteWorkspaceLayout>
</template>
