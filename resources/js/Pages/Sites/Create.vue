<script setup>
import { useForm, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    server: Object,
});

const form = useForm({
    domain: "",
    system_user: "",
    app_type: "generic",
    php_version: "8.3",
    create_database: false,
    database_type: "mariadb",
});

const suggestUser = () => {
    if (!form.system_user && form.domain) {
        // Simple suggestion: domain name without extension, limited to 16 chars
        const name = form.domain
            .split(".")[0]
            .toLowerCase()
            .replace(/[^a-z0-9]/g, "");
        form.system_user = name.substring(0, 16);
    }
};

const submit = () => {
    form.post(route("servers.sites.store", props.server.id));
};
</script>

<template>
    <AppLayout>
        <div class="max-w-2xl mx-auto space-y-6">
            <div class="flex items-center gap-4">
                <Link
                    :href="route('servers.sites.index', server.id)"
                    class="btn btn-circle btn-ghost btn-sm"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="size-6"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"
                        />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold flex items-center gap-2">
                        Create New Site
                    </h1>
                    <p class="text-base-content/60">
                        Deploy a new website on {{ server.name }}.
                    </p>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <form @submit.prevent="submit" class="space-y-4">
                        <!-- Domain -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Domain Name</span>
                            </label>
                            <input
                                v-model="form.domain"
                                type="text"
                                placeholder="example.com"
                                class="input input-bordered"
                                :class="{ 'input-error': form.errors.domain }"
                                @blur="suggestUser"
                            />
                            <div
                                v-if="form.errors.domain"
                                class="text-error text-xs mt-1"
                            >
                                {{ form.errors.domain }}
                            </div>
                        </div>

                        <!-- System User -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">System User</span>
                                <span class="label-text-alt"
                                    >Isolated Linux user for this site</span
                                >
                            </label>
                            <input
                                v-model="form.system_user"
                                type="text"
                                placeholder="siteuser"
                                class="input input-bordered"
                                :class="{
                                    'input-error': form.errors.system_user,
                                }"
                            />
                            <div
                                v-if="form.errors.system_user"
                                class="text-error text-xs mt-1"
                            >
                                {{ form.errors.system_user }}
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- App Type -->
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text"
                                        >Application Type</span
                                    >
                                </label>
                                <select
                                    v-model="form.app_type"
                                    class="select select-bordered"
                                >
                                    <option value="generic">
                                        Generic PHP / HTML
                                    </option>
                                    <option value="wordpress">WordPress</option>
                                    <option value="laravel">Laravel</option>
                                </select>
                                <div
                                    v-if="form.errors.app_type"
                                    class="text-error text-xs mt-1"
                                >
                                    {{ form.errors.app_type }}
                                </div>
                            </div>

                            <!-- PHP Version -->
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">PHP Version</span>
                                </label>
                                <select
                                    v-model="form.php_version"
                                    class="select select-bordered"
                                >
                                    <option value="8.5">PHP 8.5</option>
                                    <option value="8.4">PHP 8.4</option>
                                    <option value="8.3">PHP 8.3</option>
                                    <option value="8.2">PHP 8.2</option>
                                    <option value="8.1">PHP 8.1</option>
                                    <option value="7.4">PHP 7.4</option>
                                </select>
                                <div
                                    v-if="form.errors.php_version"
                                    class="text-error text-xs mt-1"
                                >
                                    {{ form.errors.php_version }}
                                </div>
                            </div>
                        </div>

                        <!-- Database Configuration -->
                        <div class="divider">Database</div>

                        <div class="form-control">
                            <label
                                class="label cursor-pointer justify-start gap-4"
                            >
                                <input
                                    type="checkbox"
                                    v-model="form.create_database"
                                    class="checkbox checkbox-primary"
                                />
                                <span class="label-text">Create Database</span>
                            </label>
                        </div>

                        <div v-if="form.create_database" class="form-control">
                            <label class="label">
                                <span class="label-text">Database Type</span>
                            </label>
                            <select
                                v-model="form.database_type"
                                class="select select-bordered"
                            >
                                <option value="mariadb">MariaDB (MySQL)</option>
                                <option value="postgresql">PostgreSQL</option>
                            </select>
                            <div
                                v-if="form.errors.database_type"
                                class="text-error text-xs mt-1"
                            >
                                {{ form.errors.database_type }}
                            </div>
                        </div>

                        <div class="card-actions justify-end mt-6">
                            <button
                                type="submit"
                                class="btn btn-primary"
                                :disabled="form.processing"
                            >
                                <span
                                    v-if="form.processing"
                                    class="loading loading-spinner"
                                ></span>
                                Create Site
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
