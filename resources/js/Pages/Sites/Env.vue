<script setup>
import { useForm, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { GlobeAltIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    site: Object,
    content: String,
});

const form = useForm({
    content: props.content,
});

const submit = () => {
    form.put(route("sites.env.update", props.site.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center gap-4">
                <Link
                    :href="route('sites.show', site.id)"
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
                    <h1
                        class="text-2xl font-bold tracking-tight text-base-content flex items-center gap-2"
                    >
                        <GlobeAltIcon class="h-8 w-8 text-primary" />
                        {{ site.domain }}
                    </h1>
                    <p class="text-base-content/60">
                        Environment Configuration (.env)
                    </p>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <div class="alert alert-warning text-sm shadow-sm mb-4">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="stroke-current shrink-0 h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                        <span
                            >Be careful! Invalid configuration can break your
                            site. Secrets are stored securely on the
                            server.</span
                        >
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="form-control">
                            <textarea
                                v-model="form.content"
                                class="textarea textarea-bordered font-mono text-sm h-96 w-full leading-relaxed"
                                placeholder="APP_NAME=Laravel..."
                                spellcheck="false"
                            ></textarea>
                            <div
                                v-if="form.errors.content"
                                class="text-error text-xs mt-1"
                            >
                                {{ form.errors.content }}
                            </div>
                        </div>

                        <div class="card-actions justify-end">
                            <button
                                type="submit"
                                class="btn btn-primary"
                                :disabled="form.processing"
                            >
                                <span
                                    v-if="form.processing"
                                    class="loading loading-spinner"
                                ></span>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
