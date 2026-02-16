<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { UserCircleIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AppLayout>
        <div class="max-w-3xl space-y-6">
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-base-content flex items-center gap-2"
                >
                    <UserCircleIcon class="h-8 w-8 text-primary" />
                    Profile
                </h1>
                <p class="text-base-content/60">
                    Signed-in account details for this control panel.
                </p>
            </div>

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body gap-5">
                    <div>
                        <p
                            class="text-xs uppercase tracking-wide text-base-content/50"
                        >
                            Name
                        </p>
                        <p class="text-lg font-semibold">{{ user.name }}</p>
                    </div>

                    <div>
                        <p
                            class="text-xs uppercase tracking-wide text-base-content/50"
                        >
                            Email
                        </p>
                        <p class="text-lg font-semibold">{{ user.email }}</p>
                    </div>

                    <div>
                        <p
                            class="text-xs uppercase tracking-wide text-base-content/50"
                        >
                            Joined
                        </p>
                        <p class="text-lg font-semibold">
                            {{ new Date(user.created_at).toLocaleString() }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2 pt-2">
                        <Link
                            :href="route('settings.index')"
                            class="btn btn-primary btn-sm"
                            >Open Settings</Link
                        >
                        <Link
                            :href="route('dashboard')"
                            class="btn btn-outline btn-sm"
                            >Back to Dashboard</Link
                        >
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
