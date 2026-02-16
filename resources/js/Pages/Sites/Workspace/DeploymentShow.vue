<script setup>
import { Link } from "@inertiajs/vue3";
import SiteWorkspaceLayout from "@/Components/Sites/SiteWorkspaceLayout.vue";

const props = defineProps({
    site: {
        type: Object,
        required: true,
    },
    workspace: {
        type: Object,
        required: true,
    },
    deployment: {
        type: Object,
        required: true,
    },
});

const statusBadgeClass = (status) => {
    if (status === "succeeded") {
        return "badge-success";
    }

    if (status === "failed") {
        return "badge-error";
    }

    if (status === "running") {
        return "badge-info";
    }

    return "badge-warning";
};
</script>

<template>
    <SiteWorkspaceLayout :site="site" :workspace="workspace">
        <div class="grid gap-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold">
                    Deployment {{ deployment.id }}
                </h2>
                <Link
                    :href="route('sites.workspace.deployments', site.id)"
                    class="btn btn-sm btn-outline"
                    >Back To History</Link
                >
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-base-content/70">Status</span>
                            <span
                                class="badge"
                                :class="statusBadgeClass(deployment.status)"
                                >{{ deployment.status }}</span
                            >
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-base-content/70">Branch</span>
                            <span class="font-mono">{{
                                deployment.branch
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-base-content/70">Commit</span>
                            <span class="font-mono">{{
                                deployment.commit_hash || "pending"
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-base-content/70">Actor</span>
                            <span>{{
                                deployment.actor?.name || "System"
                            }}</span>
                        </div>
                    </div>
                </div>

                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-base-content/70">Queued</span>
                            <span>{{ deployment.created_at }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-base-content/70">Started</span>
                            <span>{{
                                deployment.started_at || "not started"
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-base-content/70">Finished</span>
                            <span>{{
                                deployment.finished_at || "pending"
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-base-content/70"
                                >Triggered Via</span
                            >
                            <span class="uppercase">{{
                                deployment.triggered_via
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title text-base">STDOUT</h3>
                        <pre
                            class="max-h-96 overflow-auto rounded bg-base-200 p-3 text-xs"
                            >{{
                                deployment.stdout || "No output captured."
                            }}</pre
                        >
                    </div>
                </div>
                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title text-base">STDERR</h3>
                        <pre
                            class="max-h-96 overflow-auto rounded bg-base-200 p-3 text-xs"
                            >{{
                                deployment.stderr || "No errors captured."
                            }}</pre
                        >
                    </div>
                </div>
            </div>
        </div>
    </SiteWorkspaceLayout>
</template>
