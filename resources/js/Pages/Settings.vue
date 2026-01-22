<script setup>
import { useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    Cog6ToothIcon,
    CommandLineIcon,
    CheckCircleIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
    preferences: Object,
});

const form = useForm({
    package_manager: props.preferences?.package_manager || "bun",
});

const submit = () => {
    form.patch(route("settings.update"), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto space-y-8">
            <!-- Header -->
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-base-content flex items-center gap-2"
                >
                    <Cog6ToothIcon class="h-8 w-8 text-primary" />
                    User Settings
                </h1>
                <p class="text-base-content/60">
                    Manage your personal preferences and toolkit configuration.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Sidebar Info -->
                <div class="space-y-4">
                    <h2 class="text-lg font-bold">General Preferences</h2>
                    <p class="text-sm text-base-content/60">
                        Configure how the panel interacts with your server
                        environment. These settings are specific to your
                        account.
                    </p>
                </div>

                <!-- Settings Form -->
                <div class="md:col-span-2 space-y-6">
                    <form
                        @submit.prevent="submit"
                        class="card bg-base-100 border border-base-300 shadow-sm"
                    >
                        <div class="card-body gap-6">
                            <!-- Package Manager Selection -->
                            <div class="form-control w-full">
                                <label class="label">
                                    <span
                                        class="label-text font-bold flex items-center gap-2"
                                    >
                                        <CommandLineIcon
                                            class="h-4 w-4 text-primary"
                                        />
                                        Default Package Manager
                                    </span>
                                </label>
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2"
                                >
                                    <!-- Bun Option -->
                                    <label
                                        class="p-4 border-2 rounded-xl cursor-pointer transition-all flex items-center gap-4"
                                        :class="
                                            form.package_manager === 'bun'
                                                ? 'border-primary bg-primary/5'
                                                : 'border-base-300 bg-base-200/50 hover:border-base-content/20'
                                        "
                                    >
                                        <input
                                            type="radio"
                                            v-model="form.package_manager"
                                            value="bun"
                                            class="radio radio-primary radio-sm"
                                            id="package-manager-bun"
                                        />
                                        <div class="flex-grow">
                                            <div class="font-bold">Bun</div>
                                            <div class="text-xs opacity-60">
                                                High-performance runtime &
                                                toolset
                                            </div>
                                        </div>
                                        <CheckCircleIcon
                                            v-if="
                                                form.package_manager === 'bun'
                                            "
                                            class="h-6 w-6 text-primary"
                                        />
                                    </label>

                                    <!-- NPM Option -->
                                    <label
                                        class="p-4 border-2 rounded-xl cursor-pointer transition-all flex items-center gap-4"
                                        :class="
                                            form.package_manager === 'npm'
                                                ? 'border-primary bg-primary/5'
                                                : 'border-base-300 bg-base-200/50 hover:border-base-content/20'
                                        "
                                    >
                                        <input
                                            type="radio"
                                            v-model="form.package_manager"
                                            value="npm"
                                            class="radio radio-primary radio-sm"
                                            id="package-manager-npm"
                                        />
                                        <div class="flex-grow">
                                            <div class="font-bold">NPM</div>
                                            <div class="text-xs opacity-60">
                                                Standard Node.js package manager
                                            </div>
                                        </div>
                                        <CheckCircleIcon
                                            v-if="
                                                form.package_manager === 'npm'
                                            "
                                            class="h-6 w-6 text-primary"
                                        />
                                    </label>
                                </div>
                                <label class="label mt-2">
                                    <span
                                        class="label-text-alt text-base-content/60 italic"
                                    >
                                        * This will determine which CLI commands
                                        are generated by the panel.
                                    </span>
                                </label>
                            </div>

                            <div class="divider"></div>

                            <!-- Form Actions -->
                            <div class="card-actions justify-end">
                                <transition
                                    enter-active-class="transition ease-out duration-300"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-100"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95"
                                >
                                    <span
                                        v-if="form.recentlySuccessful"
                                        class="text-sm text-success flex items-center gap-1 font-medium mr-4"
                                    >
                                        <CheckCircleIcon class="h-5 w-5" />
                                        Saved successfully
                                    </span>
                                </transition>

                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                    :disabled="form.processing || !form.isDirty"
                                    id="save-settings-button"
                                >
                                    <span
                                        v-if="form.processing"
                                        class="loading loading-spinner"
                                    ></span>
                                    Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
