<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CircleStackIcon } from '@heroicons/vue/24/outline';

defineProps({
    databases: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Databases" />

    <AppLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-base-content flex items-center gap-2">
                    <CircleStackIcon class="h-8 w-8 text-primary" />
                    Databases
                </h1>
                <p class="text-base-content/60">
                    Database users and schemas created during site provisioning.
                </p>
            </div>

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Server</th>
                                <th>Site</th>
                                <th>User</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="database in databases" :key="database.id" class="hover">
                                <td class="font-bold">{{ database.name }}</td>
                                <td class="uppercase">{{ database.type }}</td>
                                <td>{{ database.server?.name || 'Unknown' }}</td>
                                <td>{{ database.site?.domain || 'Unknown' }}</td>
                                <td><code class="text-xs">{{ database.username }}</code></td>
                                <td>
                                    <span class="badge badge-sm" :class="{
                                        'badge-success': database.status === 'active',
                                        'badge-warning': database.status === 'creating',
                                        'badge-error': database.status === 'error',
                                    }">
                                        {{ database.status }}
                                    </span>
                                </td>
                                <td>
                                    <Link
                                        v-if="database.site"
                                        :href="route('sites.show', database.site.id)"
                                        class="btn btn-xs btn-ghost"
                                    >
                                        View Site
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="databases.length === 0">
                                <td colspan="7" class="text-center py-8 text-base-content/50">
                                    No databases have been provisioned yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
