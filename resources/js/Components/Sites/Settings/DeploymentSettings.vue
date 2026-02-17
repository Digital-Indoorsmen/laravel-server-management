<script setup>
import { useForm } from "@inertiajs/vue3";
import {
    IconRocket,
    IconClock,
    IconCopy,
    IconRefresh,
    IconCircleCheck,
    IconBrandGithub,
    IconExternalLink,
} from "@tabler/icons-vue";

const props = defineProps({
    site: Object,
});

const form = useForm({
    deploy_script: props.site.deploy_script,
    push_to_deploy: props.site.push_to_deploy,
    health_check_enabled: props.site.health_check_enabled,
    github_deployments_enabled: props.site.github_deployments_enabled,
    env_in_deploy_script: props.site.env_in_deploy_script,
});

const submit = () => {
    form.patch(route("sites.workspace.settings.deployments.update", props.site.id), {
        preserveScroll: true,
    });
};

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
};
</script>

<template>
    <div class="space-y-6">
        <!-- Deployment Strategy -->
        <section class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="card-body gap-6">
                <div>
                    <h3 class="text-lg font-medium">Deployment Strategy</h3>
                    <p class="text-sm text-base-content/60">
                        Configure how your site should be deployed.
                    </p>
                </div>

                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-4">
                        <input
                            v-model="form.push_to_deploy"
                            type="checkbox"
                            class="toggle toggle-primary"
                            @change="submit"
                        />
                        <div>
                            <span class="label-text font-medium">Quick Deploy</span>
                            <p class="text-sm text-base-content/50">
                                Automatically deploy your site when you push to your Git repository.
                            </p>
                        </div>
                    </label>
                </div>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-medium">Deploy Script</span>
                    </label>
                    <textarea
                        v-model="form.deploy_script"
                        class="textarea textarea-bordered font-mono h-64 text-sm bg-base-200"
                        placeholder="#!/bin/bash
git pull origin main..."
                    ></textarea>
                    <label class="label cursor-pointer justify-start gap-2 mt-2">
                        <input
                            v-model="form.env_in_deploy_script"
                            type="checkbox"
                            class="checkbox checkbox-sm checkbox-primary"
                        />
                        <span class="label-text text-sm">Make .env variables available to deploy script</span>
                    </label>
                </div>

                <div class="card-actions justify-end border-t border-base-300 pt-6">
                    <button
                        @click="submit"
                        class="btn btn-primary"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="loading loading-spinner"></span>
                        Save Deploy Script
                    </button>
                </div>
            </div>
        </section>

        <!-- Deployment Hook -->
        <section class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="card-body gap-6">
                <div>
                    <h3 class="text-lg font-medium">Deployment Hook</h3>
                    <p class="text-sm text-base-content/60">
                        Trigger a deployment from an external service using this unique URL.
                    </p>
                </div>

                <div class="form-control">
                    <div class="join">
                        <input
                            type="text"
                            readonly
                            :value="route('sites.deploy.webhook', { token: site.deploy_hook_url || 'missing' })"
                            class="input input-bordered join-item w-full text-sm"
                        />
                        <button
                            @click="copyToClipboard(route('sites.deploy.webhook', { token: site.deploy_hook_url || 'missing' }))"
                            class="btn btn-neutral join-item"
                            title="Copy to clipboard"
                        >
                            <IconCopy class="h-4 w-4" />
                        </button>
                        <button
                            class="btn btn-ghost join-item border-base-300"
                            title="Regenerate URL"
                        >
                            <IconRefresh class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div class="alert alert-info py-2">
                    <IconRocket class="h-4 w-4" />
                    <span class="text-sm italic">Looking for zero downtime deployments? Check out our guide.</span>
                    <IconExternalLink class="h-4 w-4 ml-auto" />
                </div>
            </div>
        </section>

        <!-- Additional Settings -->
        <section class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="card-body gap-4">
                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-4">
                        <input
                            v-model="form.health_check_enabled"
                            type="checkbox"
                            class="toggle toggle-primary"
                            @change="submit"
                        />
                        <div>
                            <span class="label-text font-medium">Health Checks</span>
                            <p class="text-sm text-base-content/50">
                                Verify your site is responding after a successful deployment.
                            </p>
                        </div>
                    </label>
                </div>

                <div class="divider my-0"></div>

                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-4">
                        <input
                            v-model="form.github_deployments_enabled"
                            type="checkbox"
                            class="toggle toggle-primary"
                            @change="submit"
                        />
                        <div>
                            <span class="label-text font-medium">GitHub Deployments</span>
                            <p class="text-sm text-base-content/50">
                                Link deployments back to your GitHub repository status.
                            </p>
                        </div>
                    </label>
                </div>
            </div>
        </section>
    </div>
</template>
