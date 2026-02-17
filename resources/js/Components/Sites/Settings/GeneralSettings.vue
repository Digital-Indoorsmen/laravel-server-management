<script setup>
import { useForm, usePage } from "@inertiajs/vue3";
import {
    IconWorld,
    IconTerminal2,
    IconCode,
    IconTag,
    IconNotebook,
    IconFolder,
    IconBrandGithub,
} from "@tabler/icons-vue";

const props = defineProps({
    site: Object,
    phpVersions: Array,
    appTypes: Array,
});

const form = useForm({
    app_type: props.site.app_type,
    php_version: props.site.php_version,
    tags: props.site.tags || [],
    notes: props.site.notes,
    document_root: props.site.document_root,
    git_repository: props.site.git_repository,
    git_branch: props.site.git_branch,
});

const submit = () => {
    form.patch(route("sites.workspace.settings.general.update", props.site.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="space-y-6">
        <!-- Site Metadata -->
        <section class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="card-body gap-6">
                <div>
                    <h3 class="text-lg font-medium">General Settings</h3>
                    <p class="text-sm text-base-content/60">
                        Update your site's metadata, framework, and PHP version.
                    </p>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-medium"
                                >Framework / App Type</span
                            >
                        </label>
                        <select v-model="form.app_type" class="select select-bordered w-full">
                            <option value="laravel">Laravel</option>
                            <option value="wordpress">WordPress</option>
                            <option value="generic">Generic PHP</option>
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-medium"
                                >PHP Version</span
                            >
                        </label>
                        <select v-model="form.php_version" class="select select-bordered w-full">
                            <option v-for="version in phpVersions" :key="version" :value="version">
                                PHP {{ version }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-medium">Tags</span>
                    </label>
                    <input
                        type="text"
                        placeholder="e.g. production, staging"
                        class="input input-bordered w-full"
                        :value="form.tags.join(', ')"
                        @input="form.tags = $event.target.value.split(',').map(s => s.trim()).filter(s => s)"
                    />
                    <label class="label">
                        <span class="label-text-alt text-base-content/50"
                            >Comma-separated tags for organization.</span
                        >
                    </label>
                </div>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-medium">Notes</span>
                    </label>
                    <textarea
                        v-model="form.notes"
                        class="textarea textarea-bordered h-24"
                        placeholder="Add some notes about this site..."
                    ></textarea>
                </div>

                <div class="card-actions justify-end border-t border-base-300 pt-6">
                    <button
                        @click="submit"
                        class="btn btn-primary"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="loading loading-spinner"></span>
                        Update Settings
                    </button>
                </div>
            </div>
        </section>

        <!-- Directories -->
        <section class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="card-body gap-6">
                <div>
                    <h3 class="text-lg font-medium">Directories</h3>
                    <p class="text-sm text-base-content/60">
                        Configure the paths for your site content on the server.
                    </p>
                </div>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-medium">Document Root</span>
                    </label>
                    <div class="join w-full">
                        <span class="join-item flex items-center bg-base-200 px-4 text-sm text-base-content/60 border border-base-300 border-r-0">
                            /home/{{ site.system_user }}/
                        </span>
                        <input
                            v-model="form.document_root"
                            type="text"
                            class="input input-bordered join-item w-full"
                        />
                    </div>
                    <label class="label">
                        <span class="label-text-alt text-base-content/50"
                            >The absolute path where your site files are located.</span
                        >
                    </label>
                </div>

                <div class="card-actions justify-end border-t border-base-300 pt-6">
                    <button
                        @click="submit"
                        class="btn btn-primary"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="loading loading-spinner"></span>
                        Update Directories
                    </button>
                </div>
            </div>
        </section>

        <!-- Source Control -->
        <section class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="card-body gap-6">
                <div>
                    <h3 class="text-lg font-medium">Source Control</h3>
                    <p class="text-sm text-base-content/60">
                        Update the Git repository and branch associated with this site.
                    </p>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-medium">Repository</span>
                        </label>
                        <div class="join">
                            <span class="join-item flex items-center bg-base-200 px-4 border border-base-300 border-r-0">
                                <IconBrandGithub class="h-4 w-4" />
                            </span>
                            <input
                                v-model="form.git_repository"
                                type="text"
                                placeholder="organization/repository"
                                class="input input-bordered join-item w-full"
                            />
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-medium">Branch</span>
                        </label>
                        <input
                            v-model="form.git_branch"
                            type="text"
                            class="input input-bordered w-full"
                        />
                    </div>
                </div>

                <div class="card-actions justify-end border-t border-base-300 pt-6">
                    <button
                        @click="submit"
                        class="btn btn-primary"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="loading loading-spinner"></span>
                        Update Source Control
                    </button>
                </div>
            </div>
        </section>
    </div>
</template>
