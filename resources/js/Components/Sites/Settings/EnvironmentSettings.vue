<script setup>
import { ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import {
    IconEye,
    IconEyeOff,
    IconAlertTriangle,
    IconDeviceFloppy,
    IconBolt,
    IconRefresh,
} from "@tabler/icons-vue";

const props = defineProps({
    site: Object,
    envContent: String,
});

const isBlurred = ref(true);

const form = useForm({
    content: props.envContent || "",
    auto_cache_config: props.site.auto_cache_config,
    auto_restart_queue: props.site.auto_restart_queue,
});

// Update form content if envContent is loaded after initial render (due to lazy prop)
watch(() => props.envContent, (newContent) => {
    if (newContent) {
        form.content = newContent;
    }
});

const submitEnv = () => {
    form.patch(route("sites.workspace.settings.environment.update", props.site.id), {
        preserveScroll: true,
        only: ['site', 'envContent', 'errors'],
    });
};

const toggleBlur = () => {
    isBlurred.ref = !isBlurred.ref;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Environment Variables -->
        <section class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="card-body gap-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-medium">Environment Variables</h3>
                        <p class="text-sm text-base-content/60">
                            Manage the secrets and configuration for your application.
                        </p>
                    </div>
                    <button
                        @click="isBlurred = !isBlurred"
                        class="btn btn-ghost btn-xs gap-2"
                        :class="isBlurred ? 'text-primary' : 'text-base-content/60'"
                    >
                        <IconEye v-if="isBlurred" class="h-4 w-4" />
                        <IconEyeOff v-else class="h-4 w-4" />
                        {{ isBlurred ? 'Reveal' : 'Blur' }}
                    </button>
                </div>

                <div v-if="isBlurred" class="relative group">
                    <div class="absolute inset-0 bg-base-content/5 backdrop-blur-md z-10 rounded-lg border border-base-300 flex items-center justify-center transition-all group-hover:bg-base-content/10">
                         <button @click="isBlurred = false" class="btn btn-primary btn-sm gap-2">
                             <IconEye class="h-4 w-4" />
                             Reveal Secrets
                         </button>
                    </div>
                    <div class="font-mono text-sm bg-base-300/30 p-4 rounded-lg select-none opacity-20">
                        APP_KEY=base64:********************************<br/>
                        DB_PASSWORD=****************<br/>
                        REDIS_PASSWORD=null<br/>
                        MAIL_PASSWORD=****************
                    </div>
                </div>

                <div v-else class="form-control">
                    <textarea
                        v-model="form.content"
                        class="textarea textarea-bordered font-mono h-96 text-sm bg-base-200"
                        placeholder="KEY=VALUE"
                    ></textarea>
                </div>

                <div class="alert alert-warning py-2 rounded-lg">
                    <IconAlertTriangle class="h-4 w-4 shrink-0" />
                    <span class="text-sm">Environment variables often contain sensitive keys. Never share this content.</span>
                </div>

                <div class="card-actions justify-end border-t border-base-300 pt-6">
                    <button
                        @click="submitEnv"
                        class="btn btn-primary"
                        :disabled="form.processing || isBlurred"
                    >
                        <IconDeviceFloppy class="h-4 w-4" />
                        Save Environment
                    </button>
                </div>
            </div>
        </section>

        <!-- Automation -->
        <section class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="card-body gap-4">
                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-4">
                        <input
                            v-model="form.auto_cache_config"
                            type="checkbox"
                            class="toggle toggle-primary"
                            @change="submitEnv"
                        />
                        <div>
                            <span class="label-text font-medium flex items-center gap-2">
                                <IconBolt class="h-4 w-4 text-warning" />
                                Auto Config Cache
                            </span>
                            <p class="text-sm text-base-content/50">
                                Automatically run <code>php artisan config:cache</code> after saving.
                            </p>
                        </div>
                    </label>
                </div>

                <div class="divider my-0"></div>

                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-4">
                        <input
                            v-model="form.auto_restart_queue"
                            type="checkbox"
                            class="toggle toggle-primary"
                            @change="submitEnv"
                        />
                        <div>
                            <span class="label-text font-medium flex items-center gap-2">
                                <IconRefresh class="h-4 w-4 text-info" />
                                Auto Queue Restart
                            </span>
                            <p class="text-sm text-base-content/50">
                                Automatically run <code>php artisan queue:restart</code> after saving.
                            </p>
                        </div>
                    </label>
                </div>
            </div>
        </section>
    </div>
</template>
