<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import SiteWorkspaceLayout from '@/Components/Sites/SiteWorkspaceLayout.vue';

const props = defineProps({
    site: {
        type: Object,
        required: true,
    },
    workspace: {
        type: Object,
        required: true,
    },
    deployments: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    branch: 'main',
});

const triggerDeployment = () => {
    form.post(route('sites.workspace.deployments.store', props.site.id), {
        preserveScroll: true,
    });
};

const statusBadgeClass = (status) => {
    if (status === 'succeeded') {
        return 'badge-success';
    }

    if (status === 'failed') {
        return 'badge-error';
    }

    if (status === 'running') {
        return 'badge-info';
    }

    return 'badge-warning';
};
</script>

<template>
    <SiteWorkspaceLayout :site="site" :workspace="workspace">
        <div class="grid gap-6">
            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body">
                    <h2 class="card-title">Trigger Deployment</h2>
                    <form class="grid gap-4 md:grid-cols-[1fr_auto] md:items-end" @submit.prevent="triggerDeployment">
                        <label class="form-control w-full">
                            <span class="label-text">Branch</span>
                            <input
                                v-model="form.branch"
                                type="text"
                                class="input input-bordered"
                                placeholder="main"
                                autocomplete="off"
                            >
                            <span v-if="form.errors.branch" class="label-text-alt text-error">{{ form.errors.branch }}</span>
                        </label>
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            <span v-if="form.processing" class="loading loading-spinner" />
                            Deploy Now
                        </button>
                    </form>
                </div>
            </div>

            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body">
                    <h2 class="card-title">Deployment History</h2>

                    <div v-if="deployments.data.length === 0" class="alert alert-info">
                        <span>No deployments yet. Trigger your first deployment from this page.</span>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Branch</th>
                                    <th>Commit</th>
                                    <th>Actor</th>
                                    <th>Triggered</th>
                                    <th />
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="deployment in deployments.data" :key="deployment.id">
                                    <td>
                                        <span class="badge" :class="statusBadgeClass(deployment.status)">{{ deployment.status }}</span>
                                    </td>
                                    <td class="font-mono text-xs">{{ deployment.branch }}</td>
                                    <td class="font-mono text-xs">{{ deployment.commit_hash || 'pending' }}</td>
                                    <td>{{ deployment.actor?.name || 'System' }}</td>
                                    <td>{{ deployment.created_at }}</td>
                                    <td class="text-right">
                                        <Link
                                            :href="route('sites.workspace.deployments.show', [site.id, deployment.id])"
                                            class="btn btn-sm btn-outline"
                                        >
                                            View Log
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <template v-for="link in deployments.links" :key="`${link.label}-${link.url}`">
                            <span
                                v-if="!link.url"
                                class="btn btn-sm btn-ghost pointer-events-none opacity-50"
                                v-html="link.label"
                            />
                            <Link
                                v-else
                                :href="link.url"
                                class="btn btn-sm"
                                :class="link.active ? 'btn-primary' : 'btn-ghost'"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </SiteWorkspaceLayout>
</template>
